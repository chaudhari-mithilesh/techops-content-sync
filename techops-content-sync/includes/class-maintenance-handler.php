<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Maintenance Handler Class
 * 
 * Handles maintenance tasks, cleanup, and monitoring.
 */
class Maintenance_Handler {
    private $temp_dir;
    private $backup_dir;
    private $log_dir;
    private $max_log_age = 30; // days
    private $max_backup_age = 7; // days
    private $max_temp_age = 1; // days

    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/techops-temp';
        $this->backup_dir = $upload_dir['basedir'] . '/techops-backups';
        $this->log_dir = $upload_dir['basedir'] . '/techops-logs';

        // Create necessary directories
        wp_mkdir_p($this->temp_dir);
        wp_mkdir_p($this->backup_dir);
        wp_mkdir_p($this->log_dir);
    }

    /**
     * Run maintenance tasks
     * 
     * @return array Maintenance results
     */
    public function run_maintenance() {
        $results = [
            'temp_cleanup' => $this->cleanup_temp_files(),
            'backup_cleanup' => $this->cleanup_old_backups(),
            'log_cleanup' => $this->cleanup_old_logs(),
            'disk_space' => $this->check_disk_space(),
            'api_status' => $this->check_api_status()
        ];

        $this->log_maintenance_results($results);
        return $results;
    }

    /**
     * Clean up temporary files
     * 
     * @return array Cleanup results
     */
    private function cleanup_temp_files() {
        $deleted = 0;
        $failed = 0;
        $now = time();

        if (is_dir($this->temp_dir)) {
            $iterator = new \DirectoryIterator($this->temp_dir);
            foreach ($iterator as $file) {
                if ($file->isDot()) continue;

                $file_age = ($now - $file->getMTime()) / (60 * 60 * 24); // days
                if ($file_age > $this->max_temp_age) {
                    if ($this->recursive_remove_directory($file->getPathname())) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => $deleted + $failed
        ];
    }

    /**
     * Clean up old backups
     * 
     * @return array Cleanup results
     */
    private function cleanup_old_backups() {
        $deleted = 0;
        $failed = 0;
        $now = time();

        if (is_dir($this->backup_dir)) {
            $iterator = new \DirectoryIterator($this->backup_dir);
            foreach ($iterator as $file) {
                if ($file->isDot()) continue;

                $file_age = ($now - $file->getMTime()) / (60 * 60 * 24); // days
                if ($file_age > $this->max_backup_age) {
                    if ($this->recursive_remove_directory($file->getPathname())) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => $deleted + $failed
        ];
    }

    /**
     * Clean up old logs
     * 
     * @return array Cleanup results
     */
    private function cleanup_old_logs() {
        $deleted = 0;
        $failed = 0;
        $now = time();

        if (is_dir($this->log_dir)) {
            $iterator = new \DirectoryIterator($this->log_dir);
            foreach ($iterator as $file) {
                if ($file->isDot()) continue;

                $file_age = ($now - $file->getMTime()) / (60 * 60 * 24); // days
                if ($file_age > $this->max_log_age) {
                    if (unlink($file->getPathname())) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => $deleted + $failed
        ];
    }

    /**
     * Check disk space
     * 
     * @return array Disk space information
     */
    private function check_disk_space() {
        $upload_dir = wp_upload_dir();
        $total_space = disk_total_space($upload_dir['basedir']);
        $free_space = disk_free_space($upload_dir['basedir']);
        $used_space = $total_space - $free_space;
        $used_percentage = ($used_space / $total_space) * 100;

        return [
            'total' => $this->format_bytes($total_space),
            'free' => $this->format_bytes($free_space),
            'used' => $this->format_bytes($used_space),
            'used_percentage' => round($used_percentage, 2)
        ];
    }

    /**
     * Check API status
     * 
     * @return array API status information
     */
    private function check_api_status() {
        $github_api = new GitHub_API_Handler();
        $rate_limit = $github_api->get_rate_limit();

        return [
            'rate_limit' => is_wp_error($rate_limit) ? null : $rate_limit,
            'status' => is_wp_error($rate_limit) ? 'error' : 'ok',
            'error' => is_wp_error($rate_limit) ? $rate_limit->get_error_message() : null
        ];
    }

    /**
     * Log maintenance results
     * 
     * @param array $results Maintenance results
     */
    private function log_maintenance_results($results) {
        $log_file = $this->log_dir . '/maintenance-' . date('Y-m-d') . '.log';
        $log_entry = sprintf(
            "[%s] Maintenance completed:\n%s\n",
            date('Y-m-d H:i:s'),
            print_r($results, true)
        );
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }

    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted size
     */
    private function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Recursively remove directory
     * 
     * @param string $dir Directory to remove
     * @return bool Whether removal was successful
     */
    private function recursive_remove_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        return rmdir($dir);
    }
} 