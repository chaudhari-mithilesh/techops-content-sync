<?php
namespace TechOpsContentSync;

class Security {
    /**
     * Rate limiting data
     */
    private $rate_limit_option = 'techops_content_sync_rate_limit';
    private $max_requests = 30; // Maximum requests per minute
    private $window_size = 60; // Time window in seconds

    /**
     * Check rate limiting
     */
    public function check_rate_limit($ip) {
        $current_time = time();
        $requests = get_option($this->rate_limit_option, []);
        
        // Clean up old entries
        foreach ($requests as $request_ip => $times) {
            $requests[$request_ip] = array_filter($times, function($time) use ($current_time) {
                return ($current_time - $time) < $this->window_size;
            });
            
            if (empty($requests[$request_ip])) {
                unset($requests[$request_ip]);
            }
        }

        // Check current IP
        if (!isset($requests[$ip])) {
            $requests[$ip] = [];
        }

        $requests[$ip][] = $current_time;
        update_option($this->rate_limit_option, $requests);

        return count($requests[$ip]) <= $this->max_requests;
    }

    /**
     * Validate file path
     */
    public function validate_path($path) {
        // Convert to real path
        $real_path = realpath($path);
        if ($real_path === false) {
            return false;
        }

        // Check if path is within WordPress directory
        $wp_root = realpath(ABSPATH);
        return strpos($real_path, $wp_root) === 0;
    }

    /**
     * Sanitize file/directory name
     */
    public function sanitize_name($name) {
        // Remove any directory traversal attempts
        $name = str_replace(['../', '..\\'], '', $name);
        
        // Remove any non-alphanumeric characters except dashes and underscores
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '', $name);
        
        return $name;
    }

    /**
     * Log security event
     */
    public function log_event($type, $message, $data = []) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log = [
            'time' => current_time('mysql'),
            'type' => $type,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'data' => $data
        ];

        error_log(print_r($log, true));
    }
} 