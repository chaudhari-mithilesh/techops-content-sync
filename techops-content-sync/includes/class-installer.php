<?php
namespace TechOpsContentSync;

class Installer {
    /**
     * Install a plugin or theme from a zip file
     *
     * @param string $zip_path Path to the zip file
     * @param string $type 'plugin' or 'theme'
     * @return array|WP_Error Success response or WP_Error on failure
     */
    public function install_from_zip($zip_path, $type = 'plugin') {
        try {
            if (!file_exists($zip_path)) {
                return new \WP_Error('file_not_found', 'Zip file not found');
            }

            if (!in_array($type, ['plugin', 'theme'])) {
                return new \WP_Error('invalid_type', 'Invalid installation type');
            }

            // Include WordPress upgrade functions
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

            // Create upgrader skin
            $skin = new \WP_Upgrader_Skin();
            
            // Create upgrader
            $upgrader = new \WP_Upgrader($skin);

            // Install the package
            $result = $upgrader->install_package(array(
                'source' => $zip_path,
                'destination' => $type === 'plugin' ? WP_PLUGIN_DIR : get_theme_root(),
                'clear_destination' => true,
                'clear_working' => true,
                'hook_extra' => array()
            ));

            if (is_wp_error($result)) {
                return $result;
            }

            // Clean up the zip file
            unlink($zip_path);

            return array(
                'success' => true,
                'message' => sprintf('%s installed successfully', ucfirst($type))
            );

        } catch (\Exception $e) {
            return new \WP_Error('installation_error', $e->getMessage());
        }
    }

    /**
     * Determine if a zip file contains a plugin or theme
     *
     * @param string $zip_path Path to the zip file
     * @return string|WP_Error 'plugin', 'theme', or WP_Error
     */
    public function detect_type($zip_path) {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($zip_path) !== true) {
                return new \WP_Error('invalid_zip', 'Invalid zip file');
            }

            // Check for plugin header
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('/^[^\/]+\/[^\/]+\.php$/', $filename)) {
                    $content = $zip->getFromIndex($i);
                    if (strpos($content, 'Plugin Name:') !== false) {
                        $zip->close();
                        return 'plugin';
                    }
                }
            }

            // Check for theme
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('/^[^\/]+\/style\.css$/', $filename)) {
                    $content = $zip->getFromIndex($i);
                    if (strpos($content, 'Theme Name:') !== false) {
                        $zip->close();
                        return 'theme';
                    }
                }
            }

            $zip->close();
            return new \WP_Error('unknown_type', 'Could not determine if this is a plugin or theme');

        } catch (\Exception $e) {
            return new \WP_Error('detection_error', $e->getMessage());
        }
    }
} 