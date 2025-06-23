<?php
/**
 * Authentication Class
 *
 * @package TechOpsContentSync
 */

namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Authentication
 */
class Authentication {
    /**
     * Rate limit in requests per minute
     */
    const RATE_LIMIT = 30;

    /**
     * Rate limit window in seconds
     */
    const RATE_LIMIT_WINDOW = 60;

    /**
     * Constructor
     */
    public function __construct() {
        add_filter('rest_authentication_errors', array($this, 'rest_authentication_check'), 20);
    }

    /**
     * Check if the current user has the required capabilities
     *
     * @return bool True if the user has the required capabilities, false otherwise
     */
    public function check_capabilities() {
        return current_user_can('manage_options');
    }

    /**
     * Verify Basic Authentication
     *
     * @param string $username The username to verify
     * @param string $password The password to verify
     * @return bool|WP_User The user object if authentication is successful, false otherwise
     */
    public function verify_basic_auth($username, $password) {
        $user = wp_authenticate_application_password($username, $password);
        return is_wp_error($user) ? false : $user;
    }

    /**
     * Check rate limiting
     *
     * @param string $ip The IP address to check
     * @return bool True if the request is allowed, false if rate limited
     */
    public function check_rate_limit($ip) {
        $transient_key = 'techops_rate_limit_' . md5($ip);
        $requests = get_transient($transient_key);

        if (false === $requests) {
            $requests = array();
        }

        $now = time();
        $window_start = $now - self::RATE_LIMIT_WINDOW;

        // Remove old requests
        $requests = array_filter($requests, function($timestamp) use ($window_start) {
            return $timestamp >= $window_start;
        });

        // Check if we've exceeded the rate limit
        if (count($requests) >= self::RATE_LIMIT) {
            return false;
        }

        // Add the current request
        $requests[] = $now;
        set_transient($transient_key, $requests, self::RATE_LIMIT_WINDOW);

        return true;
    }

    /**
     * REST API authentication check
     *
     * @param WP_Error|true|null $result Authentication result
     * @return WP_Error|true|null Modified authentication result
     */
    public function rest_authentication_check($result) {
        // If another authentication method was already successful, return that
        if (!empty($result)) {
            return $result;
        }

        // Get the authorization header
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

        // Check if it's a Basic Auth header
        if (strpos(strtolower($auth_header), 'basic ') === 0) {
            // Extract the credentials
            $auth = base64_decode(substr($auth_header, 6));
            list($username, $password) = explode(':', $auth);

            // Check rate limiting
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!$this->check_rate_limit($ip)) {
                return new \WP_Error(
                    'rate_limit_exceeded',
                    'Rate limit exceeded. Please try again later.',
                    array('status' => 429)
                );
            }

            // Verify the credentials
            $user = $this->verify_basic_auth($username, $password);
            if ($user) {
                wp_set_current_user($user->ID);
                return true;
            }
        }

        // If we get here, authentication failed
        return new \WP_Error(
            'rest_not_logged_in',
            'You are not currently logged in.',
            array('status' => 401)
        );
    }

    /**
     * Generate a debug token for testing
     *
     * @return string The debug token
     */
    public function generate_debug_token() {
        if (!TECHOPS_CONTENT_SYNC_DEBUG) {
            return '';
        }

        $username = 'admin';
        $password = 's1zZ CIlI 1vBc 9e5n YRML 1kSL';
        return base64_encode($username . ':' . $password);
    }
} 