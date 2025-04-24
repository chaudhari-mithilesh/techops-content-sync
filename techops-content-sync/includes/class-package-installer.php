<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Package Installer Class
 * 
 * Handles package installation, verification, and rollback.
 */
class Package_Installer {
    private $temp_dir;
    private $backup_dir;
    private $sync_history;

    /**
     * Constructor
     */
    public function __construct() {
        $this->temp_dir = wp_upload_dir()['basedir'] . '/techops-temp';
        $this->backup_dir = wp_upload_dir()['basedir'] . '/techops-backups';
        $this->sync_history = new Sync_History();
        
        // Create necessary directories
        wp_mkdir_p($this->temp_dir);
        wp_mkdir_p($this->backup_dir);
    }

    /**
     * Install package from downloaded path
     * 
     * @param string $package_path Path to downloaded package
     * @param string $package_type Type of package (plugin/theme)
     * @param bool $activate Whether to activate after installation
     * @return array|WP_Error Installation result
     */
    public function install_package($package_path, $package_type, $activate = false) {
        try {
            // Verify package integrity
            if (!$this->verify_package_integrity($package_path)) {
                return new \WP_Error(
                    'package_integrity_failed',
                    'Package integrity check failed',
                    ['status' => 400]
                );
            }

            // Check compatibility
            if (!$this->check_compatibility($package_path, $package_type)) {
                return new \WP_Error(
                    'package_incompatible',
                    'Package is not compatible with current WordPress version',
                    ['status' => 400]
                );
            }

            // Create backup
            $backup_path = $this->create_backup($package_path, $package_type);
            if (is_wp_error($backup_path)) {
                return $backup_path;
            }

            // Install package
            $install_result = $this->perform_installation($package_path, $package_type);
            if (is_wp_error($install_result)) {
                $this->rollback_installation($backup_path, $package_type);
                return $install_result;
            }

            // Activate if requested
            if ($activate) {
                $activation_result = $this->activate_package($install_result['path'], $package_type);
                if (is_wp_error($activation_result)) {
                    $this->rollback_installation($backup_path, $package_type);
                    return $activation_result;
                }
            }

            return [
                'success' => true,
                'path' => $install_result['path'],
                'activated' => $activate,
                'backup_path' => $backup_path
            ];

        } catch (\Exception $e) {
            return new \WP_Error(
                'installation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Verify package integrity
     * 
     * @param string $package_path Path to package
     * @return bool Whether package is valid
     */
    private function verify_package_integrity($package_path) {
        // Check if package exists
        if (!file_exists($package_path)) {
            return false;
        }

        // Check for required files based on package type
        $required_files = [
            'plugin' => ['plugin.php'],
            'theme' => ['style.css']
        ];

        $package_type = $this->detect_package_type($package_path);
        if (!$package_type) {
            return false;
        }

        foreach ($required_files[$package_type] as $file) {
            if (!file_exists($package_path . '/' . $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check package compatibility
     * 
     * @param string $package_path Path to package
     * @param string $package_type Type of package
     * @return bool Whether package is compatible
     */
    private function check_compatibility($package_path, $package_type) {
        $wp_version = get_bloginfo('version');
        
        if ($package_type === 'plugin') {
            $plugin_data = get_plugin_data($package_path . '/plugin.php');
            $requires_wp = $plugin_data['RequiresWP'] ?? '';
        } else {
            $theme = wp_get_theme($package_path);
            $requires_wp = $theme->get('RequiresWP') ?? '';
        }

        if (!empty($requires_wp)) {
            return version_compare($wp_version, $requires_wp, '>=');
        }

        return true;
    }

    /**
     * Create backup of existing package
     * 
     * @param string $package_path Path to package
     * @param string $package_type Type of package
     * @return string|WP_Error Backup path or error
     */
    private function create_backup($package_path, $package_type) {
        $package_name = basename($package_path);
        $backup_path = $this->backup_dir . '/' . $package_name . '-' . time();

        if ($package_type === 'plugin') {
            $source_path = WP_PLUGIN_DIR . '/' . $package_name;
        } else {
            $source_path = get_theme_root() . '/' . $package_name;
        }

        if (!file_exists($source_path)) {
            return $backup_path; // No existing package to backup
        }

        if (!wp_mkdir_p($backup_path)) {
            return new \WP_Error(
                'backup_failed',
                'Could not create backup directory',
                ['status' => 500]
            );
        }

        $this->copy_directory($source_path, $backup_path);
        return $backup_path;
    }

    /**
     * Perform package installation
     * 
     * @param string $package_path Path to package
     * @param string $package_type Type of package
     * @return array|WP_Error Installation result
     */
    private function perform_installation($package_path, $package_type) {
        $package_name = basename($package_path);
        
        if ($package_type === 'plugin') {
            $target_path = WP_PLUGIN_DIR . '/' . $package_name;
        } else {
            $target_path = get_theme_root() . '/' . $package_name;
        }

        if (!wp_mkdir_p($target_path)) {
            return new \WP_Error(
                'installation_failed',
                'Could not create target directory',
                ['status' => 500]
            );
        }

        $this->copy_directory($package_path, $target_path);
        
        return [
            'success' => true,
            'path' => $target_path
        ];
    }

    /**
     * Activate package
     * 
     * @param string $package_path Path to package
     * @param string $package_type Type of package
     * @return bool|WP_Error Activation result
     */
    private function activate_package($package_path, $package_type) {
        if ($package_type === 'plugin') {
            $plugin_file = $package_path . '/' . basename($package_path) . '.php';
            if (!file_exists($plugin_file)) {
                return new \WP_Error(
                    'activation_failed',
                    'Plugin file not found',
                    ['status' => 404]
                );
            }
            activate_plugin($plugin_file);
        } else {
            switch_theme(basename($package_path));
        }
        return true;
    }

    /**
     * Rollback installation
     * 
     * @param string $backup_path Path to backup
     * @param string $package_type Type of package
     * @return bool Whether rollback was successful
     */
    private function rollback_installation($backup_path, $package_type) {
        if (!file_exists($backup_path)) {
            return false;
        }

        $package_name = basename($backup_path, '-' . time());
        
        if ($package_type === 'plugin') {
            $target_path = WP_PLUGIN_DIR . '/' . $package_name;
        } else {
            $target_path = get_theme_root() . '/' . $package_name;
        }

        $this->copy_directory($backup_path, $target_path);
        return true;
    }

    /**
     * Detect package type
     * 
     * @param string $package_path Path to package
     * @return string|false Package type or false if unknown
     */
    private function detect_package_type($package_path) {
        if (file_exists($package_path . '/plugin.php')) {
            return 'plugin';
        }
        if (file_exists($package_path . '/style.css')) {
            return 'theme';
        }
        return false;
    }

    /**
     * Copy directory recursively
     * 
     * @param string $source Source directory
     * @param string $destination Destination directory
     */
    private function copy_directory($source, $destination) {
        $dir = opendir($source);
        wp_mkdir_p($destination);

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $source_file = $source . '/' . $file;
                $dest_file = $destination . '/' . $file;

                if (is_dir($source_file)) {
                    $this->copy_directory($source_file, $dest_file);
                } else {
                    copy($source_file, $dest_file);
                }
            }
        }
        closedir($dir);
    }
} 