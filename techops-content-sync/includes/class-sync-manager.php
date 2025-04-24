<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync Manager Class
 * 
 * Orchestrates the entire sync process from repository download to package installation.
 */
class Sync_Manager {
    private $github_api;
    private $repository_handler;
    private $package_detector;
    private $package_installer;
    private $sync_history;
    private $settings;

    /**
     * Constructor
     * 
     * @param GitHub_API_Handler $github_api GitHub API handler instance
     * @param Repository_Handler $repository_handler Repository handler instance
     * @param Package_Detector $package_detector Package detector instance
     * @param Package_Installer $package_installer Package installer instance
     * @param Sync_History $sync_history Sync history instance
     * @param Settings $settings Settings instance
     */
    public function __construct(
        $github_api,
        $repository_handler,
        $package_detector,
        $package_installer,
        $sync_history,
        $settings
    ) {
        $this->github_api = $github_api;
        $this->repository_handler = $repository_handler;
        $this->package_detector = $package_detector;
        $this->package_installer = $package_installer;
        $this->sync_history = $sync_history;
        $this->settings = $settings;
    }

    /**
     * Start sync process
     * 
     * @param string $repository_url Repository URL
     * @param string $branch Branch name
     * @param array $packages Packages to sync
     * @return array|WP_Error Sync result or error
     */
    public function start_sync($repository_url, $branch, $packages) {
        try {
            // Create sync record
            $sync_id = $this->sync_history->create_sync_record($repository_url, $branch);
            
            // Download and extract repository
            $result = $this->repository_handler->download_and_extract($repository_url, $branch);
            if (is_wp_error($result)) {
                $this->sync_history->update_sync_status($sync_id, 'failed', $result->get_error_message());
                return $result;
            }

            $repo_path = $result['path'];
            $temp_dir = $result['temp_dir'];

            // Detect packages in repository
            $detected = $this->package_detector->detect_packages($repo_path);
            
            // Process each requested package
            foreach ($packages as $package) {
                $this->process_package($package, $detected, $repo_path, $sync_id);
            }

            // Clean up
            $this->repository_handler->cleanup($temp_dir);
            
            // Update final status
            $this->sync_history->update_sync_status($sync_id, 'completed');

            return [
                'sync_id' => $sync_id,
                'status' => 'completed'
            ];

        } catch (\Exception $e) {
            if (isset($sync_id)) {
                $this->sync_history->update_sync_status($sync_id, 'failed', $e->getMessage());
            }
            return new \WP_Error('sync_failed', $e->getMessage());
        }
    }

    /**
     * Process a single package
     * 
     * @param array $package Package to process
     * @param array $detected_packages Detected packages
     * @param string $repo_path Repository path
     * @param string $sync_id Sync ID
     */
    private function process_package($package, $detected_packages, $repo_path, $sync_id) {
        // Find package in detected packages
        $package_info = $this->find_package($package, $detected_packages);
        if (is_wp_error($package_info)) {
            $this->sync_history->add_package_status(
                $sync_id,
                $package['name'],
                $package['type'],
                'failed',
                $package_info->get_error_message()
            );
            return;
        }

        // Validate compatibility
        $compatibility = $this->package_detector->validate_compatibility($package_info);
        if (!$compatibility['compatible']) {
            $this->sync_history->add_package_status(
                $sync_id,
                $package['name'],
                $package['type'],
                'failed',
                implode(' ', $compatibility['messages'])
            );
            return;
        }

        // Install package
        $source_path = $repo_path . $package_info['path'];
        $result = $this->package_installer->install_package(
            array_merge($package, $package_info),
            $source_path,
            $sync_id
        );

        if (is_wp_error($result)) {
            $this->sync_history->add_package_status(
                $sync_id,
                $package['name'],
                $package['type'],
                'failed',
                $result->get_error_message()
            );
        }
    }

    /**
     * Find package in detected packages
     * 
     * @param array $package Package to find
     * @param array $detected_packages Detected packages
     * @return array|WP_Error Package info or error
     */
    private function find_package($package, $detected_packages) {
        $type_key = $package['type'] === 'plugin' ? 'plugins' : 'themes';
        
        foreach ($detected_packages[$type_key] as $detected) {
            if ($detected['name'] === $package['name']) {
                return $detected;
            }
        }

        return new \WP_Error(
            'package_not_found',
            sprintf('Package "%s" of type "%s" not found in repository', $package['name'], $package['type'])
        );
    }

    /**
     * Get sync status
     * 
     * @param string $sync_id Sync ID
     * @return array Sync status
     */
    public function get_sync_status($sync_id) {
        $sync_details = $this->sync_history->get_sync_details($sync_id);
        if (empty($sync_details)) {
            return [
                'status' => 'not_found',
                'message' => 'Sync record not found'
            ];
        }

        $packages = $sync_details['packages'];
        $total = count($packages);
        $completed = 0;
        $failed = 0;

        foreach ($packages as $package) {
            if ($package['status'] === 'completed') {
                $completed++;
            } elseif ($package['status'] === 'failed') {
                $failed++;
            }
        }

        return [
            'status' => $sync_details['status'],
            'progress' => [
                'total' => $total,
                'completed' => $completed,
                'failed' => $failed,
                'remaining' => $total - ($completed + $failed)
            ],
            'packages' => $packages
        ];
    }
} 