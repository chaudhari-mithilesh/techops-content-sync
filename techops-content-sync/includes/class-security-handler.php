<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Handler Class
 * 
 * Handles security checks and validations for packages and installations.
 */
class Security_Handler {
    private $temp_dir;
    private $allowed_file_types = [
        'php', 'css', 'js', 'json', 'txt', 'md', 'png', 'jpg', 'jpeg', 'gif', 'svg'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->temp_dir = wp_upload_dir()['basedir'] . '/techops-temp';
    }

    /**
     * Validate package security
     * 
     * @param string $package_path Path to package
     * @return array|WP_Error Security validation result
     */
    public function validate_package_security($package_path) {
        try {
            // Check file types
            $invalid_files = $this->check_file_types($package_path);
            if (!empty($invalid_files)) {
                return new \WP_Error(
                    'invalid_file_types',
                    'Package contains invalid file types: ' . implode(', ', $invalid_files),
                    ['status' => 400]
                );
            }

            // Check for malware
            $malware_check = $this->check_for_malware($package_path);
            if (is_wp_error($malware_check)) {
                return $malware_check;
            }

            // Check for path traversal
            $path_traversal = $this->check_path_traversal($package_path);
            if (is_wp_error($path_traversal)) {
                return $path_traversal;
            }

            // Check for executable files
            $executable_files = $this->check_executable_files($package_path);
            if (!empty($executable_files)) {
                return new \WP_Error(
                    'executable_files',
                    'Package contains executable files: ' . implode(', ', $executable_files),
                    ['status' => 400]
                );
            }

            return [
                'success' => true,
                'checks' => [
                    'file_types' => true,
                    'malware' => true,
                    'path_traversal' => true,
                    'executable_files' => true
                ]
            ];

        } catch (\Exception $e) {
            return new \WP_Error(
                'security_check_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Check file types in package
     * 
     * @param string $path Directory path
     * @return array List of invalid files
     */
    private function check_file_types($path) {
        $invalid_files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, $this->allowed_file_types)) {
                    $invalid_files[] = $file->getPathname();
                }
            }
        }

        return $invalid_files;
    }

    /**
     * Check for malware patterns
     * 
     * @param string $path Directory path
     * @return bool|WP_Error Whether check passed
     */
    private function check_for_malware($path) {
        $malware_patterns = [
            'eval\s*\(',
            'base64_decode\s*\(',
            'system\s*\(',
            'exec\s*\(',
            'shell_exec\s*\(',
            'passthru\s*\(',
            'popen\s*\(',
            'proc_open\s*\(',
            'curl_exec\s*\(',
            'file_get_contents\s*\(\s*[\'"]https?:\/\/'
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'js'])) {
                $content = file_get_contents($file->getPathname());
                foreach ($malware_patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $content)) {
                        return new \WP_Error(
                            'malware_detected',
                            'Potential malware detected in file: ' . $file->getPathname(),
                            ['status' => 400]
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check for path traversal attempts
     * 
     * @param string $path Directory path
     * @return bool|WP_Error Whether check passed
     */
    private function check_path_traversal($path) {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $content = file_get_contents($file->getPathname());
                if (preg_match('/\.\.\//', $content) || preg_match('/\.\.\\\/', $content)) {
                    return new \WP_Error(
                        'path_traversal',
                        'Potential path traversal detected in file: ' . $file->getPathname(),
                        ['status' => 400]
                    );
                }
            }
        }

        return true;
    }

    /**
     * Check for executable files
     * 
     * @param string $path Directory path
     * @return array List of executable files
     */
    private function check_executable_files($path) {
        $executable_files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['exe', 'bat', 'sh', 'bin'])) {
                    $executable_files[] = $file->getPathname();
                }
            }
        }

        return $executable_files;
    }

    /**
     * Validate installation permissions
     * 
     * @param string $path Installation path
     * @return bool|WP_Error Whether permissions are valid
     */
    public function validate_installation_permissions($path) {
        try {
            // Check if directory is writable
            if (!is_writable(dirname($path))) {
                return new \WP_Error(
                    'permission_denied',
                    'Directory is not writable: ' . dirname($path),
                    ['status' => 403]
                );
            }

            // Check disk space
            $free_space = disk_free_space(dirname($path));
            if ($free_space < 1024 * 1024 * 10) { // Less than 10MB
                return new \WP_Error(
                    'insufficient_space',
                    'Insufficient disk space for installation',
                    ['status' => 500]
                );
            }

            return true;

        } catch (\Exception $e) {
            return new \WP_Error(
                'permission_check_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
} 