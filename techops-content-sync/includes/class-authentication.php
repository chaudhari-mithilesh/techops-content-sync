<?php
namespace TechOpsContentSync;

class Authentication {
    /**
     * Authentication method
     */
    private $auth_method = 'basic_auth';
    
    /**
     * Required capability
     */
    private $required_capability = 'manage_options';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize authentication
    }
    
    /**
     * Get authentication method
     */
    public function get_auth_method() {
        return $this->auth_method;
    }
    
    /**
     * Get required capability
     */
    public function get_required_capability() {
        return $this->required_capability;
    }
    
    /**
     * Verify user credentials
     */
    public function verify_credentials($username, $password) {
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return false;
        }
        
        return user_can($user, $this->required_capability);
    }
    
    /**
     * Generate authentication token
     */
    public function generate_token($username, $password) {
        return base64_encode($username . ':' . $password);
    }
    
    /**
     * Verify authentication token
     */
    public function verify_token($token) {
        $credentials = base64_decode($token);
        if ($credentials === false) {
            return false;
        }
        
        list($username, $password) = array_pad(explode(':', $credentials, 2), 2, '');
        
        return $this->verify_credentials($username, $password);
    }
    
    /**
     * Check if current user is authenticated
     */
    public function is_authenticated() {
        return current_user_can($this->required_capability);
    }
} 