<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Package Detector Class
 * 
 * Handles detection and validation of WordPress plugins and themes in GitHub repositories.
 */
class Package_Detector {
    /**
     * Detect packages in a repository
     * 
     * @param string $path Path to the repository contents
     * @return array Array of detected packages with their types and details
     */
    public function detect_packages($path) {
        $packages = [
            'plugins' => $this->detect_plugins($path),
            'themes' => $this->detect_themes($path)
        ];

        return $packages;
    }

    /**
     * Detect plugins in a directory
     * 
     * @param string $path Directory path
     * @return array Array of detected plugins
     */
    private function detect_plugins($path) {
        $plugins = [];
        $iterator = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($iterator);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $headers = $this->get_plugin_headers($content);
                
                if (!empty($headers['Plugin Name'])) {
                    $relative_path = str_replace($path, '', $file->getPath());
                    $plugins[] = [
                        'name' => $headers['Plugin Name'],
                        'version' => $headers['Version'] ?? '',
                        'requires' => $headers['Requires at least'] ?? '',
                        'tested' => $headers['Tested up to'] ?? '',
                        'requires_php' => $headers['Requires PHP'] ?? '',
                        'path' => $relative_path,
                        'file' => $file->getFilename(),
                        'headers' => $headers
                    ];
                }
            }
        }

        return $plugins;
    }

    /**
     * Detect themes in a directory
     * 
     * @param string $path Directory path
     * @return array Array of detected themes
     */
    private function detect_themes($path) {
        $themes = [];
        $iterator = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($iterator);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'style.css') {
                $content = file_get_contents($file->getPathname());
                $headers = $this->get_theme_headers($content);
                
                if (!empty($headers['Theme Name'])) {
                    $relative_path = str_replace($path, '', $file->getPath());
                    $themes[] = [
                        'name' => $headers['Theme Name'],
                        'version' => $headers['Version'] ?? '',
                        'requires' => $headers['Requires at least'] ?? '',
                        'tested' => $headers['Tested up to'] ?? '',
                        'requires_php' => $headers['Requires PHP'] ?? '',
                        'path' => $relative_path,
                        'headers' => $headers
                    ];
                }
            }
        }

        return $themes;
    }

    /**
     * Get plugin headers from file content
     * 
     * @param string $content File content
     * @return array Array of plugin headers
     */
    private function get_plugin_headers($content) {
        $default_headers = [
            'Plugin Name' => 'Plugin Name',
            'Plugin URI'  => 'Plugin URI',
            'Version'     => 'Version',
            'Description' => 'Description',
            'Author'      => 'Author',
            'Author URI'  => 'Author URI',
            'Text Domain' => 'Text Domain',
            'Domain Path' => 'Domain Path',
            'Network'     => 'Network',
            'Requires at least' => 'Requires at least',
            'Requires PHP'      => 'Requires PHP',
            'Tested up to'      => 'Tested up to'
        ];

        return $this->get_file_headers($content, $default_headers);
    }

    /**
     * Get theme headers from style.css content
     * 
     * @param string $content File content
     * @return array Array of theme headers
     */
    private function get_theme_headers($content) {
        $default_headers = [
            'Theme Name'  => 'Theme Name',
            'Theme URI'   => 'Theme URI',
            'Version'     => 'Version',
            'Description' => 'Description',
            'Author'      => 'Author',
            'Author URI'  => 'Author URI',
            'Text Domain' => 'Text Domain',
            'Domain Path' => 'Domain Path',
            'Requires at least' => 'Requires at least',
            'Requires PHP'      => 'Requires PHP',
            'Tested up to'      => 'Tested up to'
        ];

        return $this->get_file_headers($content, $default_headers);
    }

    /**
     * Parse file headers
     * 
     * @param string $content File content
     * @param array $default_headers Default headers to look for
     * @return array Array of found headers
     */
    private function get_file_headers($content, $default_headers) {
        $headers = [];
        foreach ($default_headers as $field => $regex) {
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match)
                && $match[1]) {
                $headers[$field] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
            } else {
                $headers[$field] = '';
            }
        }
        return $headers;
    }

    /**
     * Validate package compatibility
     * 
     * @param array $package Package information
     * @return array Validation results
     */
    public function validate_compatibility($package) {
        $results = [
            'compatible' => true,
            'messages' => []
        ];

        // Check WordPress version compatibility
        if (!empty($package['requires'])) {
            global $wp_version;
            if (version_compare($wp_version, $package['requires'], '<')) {
                $results['compatible'] = false;
                $results['messages'][] = sprintf(
                    'Requires WordPress version %s or higher. Current version is %s.',
                    $package['requires'],
                    $wp_version
                );
            }
        }

        // Check PHP version compatibility
        if (!empty($package['requires_php'])) {
            if (version_compare(PHP_VERSION, $package['requires_php'], '<')) {
                $results['compatible'] = false;
                $results['messages'][] = sprintf(
                    'Requires PHP version %s or higher. Current version is %s.',
                    $package['requires_php'],
                    PHP_VERSION
                );
            }
        }

        return $results;
    }
} 