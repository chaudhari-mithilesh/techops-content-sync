<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository Handler Class
 * 
 * Handles downloading and extracting GitHub repositories.
 */
class Repository_Handler {
    private $github_api;
    private $temp_dir;

    /**
     * Constructor
     * 
     * @param GitHub_API_Handler $github_api GitHub API handler instance
     */
    public function __construct($github_api) {
        $this->github_api = $github_api;
        $this->temp_dir = WP_CONTENT_DIR . '/techops-temp';
        
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
    }

    /**
     * Download and extract a repository
     * 
     * @param string $repo_url Repository URL
     * @param string $branch Branch name
     * @return array|WP_Error Array with extraction path or WP_Error on failure
     */
    public function download_and_extract($repo_url, $branch) {
        try {
            // Parse repository URL
            $repo_info = $this->github_api->parse_repository_url($repo_url);
            if (is_wp_error($repo_info)) {
                return $repo_info;
            }

            // Generate unique directory name
            $unique_dir = uniqid('repo-');
            $extract_path = $this->temp_dir . '/' . $unique_dir;
            wp_mkdir_p($extract_path);

            // Download repository
            $download_url = sprintf(
                'https://api.github.com/repos/%s/%s/zipball/%s',
                $repo_info['owner'],
                $repo_info['repo'],
                $branch
            );

            $headers = $this->github_api->get_auth_headers();
            $response = wp_remote_get($download_url, [
                'headers' => $headers,
                'timeout' => 60
            ]);

            if (is_wp_error($response)) {
                return $response;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                return new \WP_Error(
                    'download_failed',
                    sprintf('Failed to download repository. Status code: %d', $response_code)
                );
            }

            // Save zip file
            $zip_path = $extract_path . '/repo.zip';
            file_put_contents($zip_path, wp_remote_retrieve_body($response));

            // Extract zip file
            $zip = new \ZipArchive();
            if ($zip->open($zip_path) === true) {
                $zip->extractTo($extract_path);
                $zip->close();
                unlink($zip_path);

                // Get the extracted directory name (GitHub adds a suffix)
                $extracted_dirs = glob($extract_path . '/*', GLOB_ONLYDIR);
                if (empty($extracted_dirs)) {
                    return new \WP_Error('extraction_failed', 'No directories found after extraction');
                }

                return [
                    'path' => $extracted_dirs[0],
                    'temp_dir' => $extract_path
                ];
            } else {
                return new \WP_Error('extraction_failed', 'Failed to extract zip file');
            }
        } catch (\Exception $e) {
            return new \WP_Error('download_extract_failed', $e->getMessage());
        }
    }

    /**
     * Clean up temporary files
     * 
     * @param string $path Path to clean up
     */
    public function cleanup($path) {
        if (empty($path) || !is_dir($path)) {
            return;
        }

        $this->recursive_remove_directory($path);
    }

    /**
     * Recursively remove a directory and its contents
     * 
     * @param string $dir Directory path
     */
    private function recursive_remove_directory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir . '/' . $object;
                    if (is_dir($path)) {
                        $this->recursive_remove_directory($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Clean up old temporary directories
     * 
     * @param int $hours Hours to keep files (default: 24)
     */
    public function cleanup_old_temp_files($hours = 24) {
        if (!is_dir($this->temp_dir)) {
            return;
        }

        $cutoff = time() - ($hours * 3600);
        $dirs = glob($this->temp_dir . '/repo-*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            if (filemtime($dir) < $cutoff) {
                $this->recursive_remove_directory($dir);
            }
        }
    }
} 