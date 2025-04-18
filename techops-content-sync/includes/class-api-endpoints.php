<?php
namespace TechOpsContentSync;

class API_Endpoints {
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Debug: Log route registration
        error_log('TechOps Content Sync: Registering REST API routes');

        // List plugins endpoint
        register_rest_route('techops/v1', '/plugins/list', [
            'methods' => 'GET',
            'callback' => [$this, 'list_plugins'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // UPDATED: Activate plugin endpoints - both body and URL parameter versions
        register_rest_route('techops/v1', '/plugins/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_plugin_from_body'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'plugin' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/plugins/activate/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_plugin'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // UPDATED: Deactivate plugin endpoints - both body and URL parameter versions
        register_rest_route('techops/v1', '/plugins/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_plugin_from_body'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'plugin' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/plugins/deactivate/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_plugin'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // UPDATED: Theme endpoints - both activate and deactivate with both methods
        register_rest_route('techops/v1', '/themes/list', [
            'methods' => 'GET',
            'callback' => [$this, 'get_themes_list'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route('techops/v1', '/themes/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_theme_from_body'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'theme' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/themes/activate/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_theme'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/themes/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_theme_from_body'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'theme' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/themes/deactivate/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_theme'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // Download endpoints
        register_rest_route('techops/v1', '/plugins/download/(?P<slug>[^/]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'download_plugin'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/themes/download/(?P<slug>[^/]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'download_theme'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // Debug: Log route registration completion
        error_log('TechOps Content Sync: REST API routes registered successfully');
    }

    /**
     * Check if the current user has permission to access the API
     */
    public function check_permission() {
        error_log('TechOps Content Sync: Starting permission check');
        
        // Get the Authorization header
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        error_log('TechOps Content Sync: Authorization header: ' . $auth_header);
        
        if (empty($auth_header)) {
            error_log('TechOps Content Sync: No Authorization header found');
            return false;
        }
        
        // Check if it's Basic auth
        if (strpos($auth_header, 'Basic ') !== 0) {
            error_log('TechOps Content Sync: Not Basic authentication');
            return false;
        }
        
        // Get the credentials
        $credentials = base64_decode(substr($auth_header, 6));
        if ($credentials === false) {
            error_log('TechOps Content Sync: Invalid base64 encoding');
            return false;
        }
        
        list($username, $password) = array_pad(explode(':', $credentials, 2), 2, '');
        error_log('TechOps Content Sync: Attempting authentication for user: ' . $username);
        
        if (empty($username) || empty($password)) {
            error_log('TechOps Content Sync: Missing username or password');
            return false;
        }
        
        // Authenticate user
        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            error_log('TechOps Content Sync: Authentication failed: ' . $user->get_error_message());
            return false;
        }
        
        // Check if user has required capability
        if (user_can($user, 'manage_options')) {
            error_log('TechOps Content Sync: User has required capability');
            return true;
        }
        
        error_log('TechOps Content Sync: User does not have required capability');
        return false;
    }

    /**
     * Get list of installed plugins
     */
    public function list_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        $formatted_plugins = [];
        foreach ($plugins as $plugin_path => $plugin_data) {
            $slug = explode('/', $plugin_path)[0];
            $formatted_plugins[] = [
                'name' => $plugin_data['Name'],
                'slug' => $slug,
                'version' => $plugin_data['Version'],
                'active' => in_array($plugin_path, $active_plugins),
                'path' => $plugin_path
            ];
        }

        return rest_ensure_response($formatted_plugins);
    }

    /**
     * Get list of installed themes
     */
    public function get_themes_list() {
        $themes = wp_get_themes();
        $active_theme = wp_get_theme();
        
        $formatted_themes = [];
        foreach ($themes as $theme_slug => $theme) {
            $formatted_themes[] = [
                'name' => $theme->get('Name'),
                'slug' => $theme_slug,
                'version' => $theme->get('Version'),
                'active' => ($active_theme->get_stylesheet() === $theme_slug),
                'path' => $theme->get_stylesheet_directory()
            ];
        }

        return rest_ensure_response($formatted_themes);
    }

    /**
     * Download a plugin as ZIP file
     */
    public function download_plugin($request) {
        $slug = $request->get_param('slug');
        $file_handler = new File_Handler();
        
        try {
            $result = $file_handler->create_plugin_zip($slug);
            $zip_file = $result['file'];
            
            if (!file_exists($zip_file)) {
                throw new \Exception('ZIP file not found after creation');
            }
            
            // Get file size
            $file_size = \filesize($zip_file);
            if ($file_size === false) {
                throw new \Exception('Could not determine file size');
            }
            
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers for file download
            \header('Content-Type: application/zip');
            \header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            \header('Content-Length: ' . $file_size);
            \header('Content-Transfer-Encoding: binary');
            \header('Cache-Control: no-cache, must-revalidate');
            \header('Pragma: public');
            
            // Output file contents
            if (!\readfile($zip_file)) {
                throw new \Exception('Failed to read file');
            }
            
            // Clean up
            if (isset($result['cleanup']) && is_callable($result['cleanup'])) {
                $result['cleanup']();
            }
            
            exit;
        } catch (\Exception $e) {
            error_log('TechOps Content Sync: Download failed - ' . $e->getMessage());
            return new \WP_Error(
                'plugin_download_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Download a theme as ZIP file
     */
    public function download_theme($request) {
        $slug = $request->get_param('slug');
        $file_handler = new File_Handler();
        
        try {
            $result = $file_handler->create_theme_zip($slug);
            $zip_file = $result['file'];
            
            if (!file_exists($zip_file)) {
                throw new \Exception('ZIP file not found after creation');
            }
            
            // Get file size
            $file_size = \filesize($zip_file);
            if ($file_size === false) {
                throw new \Exception('Could not determine file size');
            }
            
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers for file download
            \header('Content-Type: application/zip');
            \header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            \header('Content-Length: ' . $file_size);
            \header('Content-Transfer-Encoding: binary');
            \header('Cache-Control: no-cache, must-revalidate');
            \header('Pragma: public');
            
            // Output file contents
            if (!\readfile($zip_file)) {
                throw new \Exception('Failed to read file');
            }
            
            // Clean up
            if (isset($result['cleanup']) && is_callable($result['cleanup'])) {
                $result['cleanup']();
            }
            
            exit;
        } catch (\Exception $e) {
            error_log('TechOps Content Sync: Download failed - ' . $e->getMessage());
            return new \WP_Error(
                'theme_download_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * NEW: Activate a plugin from request body
     * This allows for a more standard REST API approach
     */
    public function activate_plugin_from_body($request) {
        error_log('TechOps Content Sync: Plugin activation request received (body method)');
        
        // Get plugin from request body
        $plugin = $request->get_param('plugin');
        if (empty($plugin)) {
            error_log('TechOps Content Sync: No plugin parameter provided in request body');
            return new \WP_Error(
                'missing_plugin',
                'No plugin identifier provided in request body',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Plugin identifier from body: ' . $plugin);
        
        // Proceed with activation using the common method
        return $this->process_plugin_activation($plugin);
    }

    /**
     * Activate a plugin (from URL parameter)
     */
    public function activate_plugin($request) {
        error_log('TechOps Content Sync: Plugin activation request received (URL method)');
        
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No slug provided in URL');
            return new \WP_Error(
                'missing_slug',
                'No plugin slug provided in URL',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Plugin identifier from URL: ' . $slug);
        
        // Proceed with activation using the common method
        return $this->process_plugin_activation($slug);
    }

    /**
     * Common method to process plugin activation
     * This supports multiple formats: slug, file name, or full path
     */
    private function process_plugin_activation($identifier) {
        if (!function_exists('activate_plugin') || !function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Remove any directory traversal attempts
        $identifier = str_replace(['../', './'], '', $identifier);
        error_log('TechOps Content Sync: Sanitized identifier: ' . $identifier);

        $plugins = get_plugins();
        error_log('TechOps Content Sync: Available plugins: ' . print_r(array_keys($plugins), true));

        // Strategy 1: Direct match with plugin file path
        if (isset($plugins[$identifier])) {
            $plugin_path = $identifier;
            error_log('TechOps Content Sync: Found exact match for plugin path: ' . $plugin_path);
        } 
        // Strategy 2: Match as a directory/slug (e.g., "hello-dolly")
        else {
            $plugin_path = '';
            foreach ($plugins as $path => $data) {
                error_log('TechOps Content Sync: Checking path: ' . $path . ' against identifier: ' . $identifier);
                // Check if path starts with the slug followed by slash
                if (strpos($path, $identifier . '/') === 0) {
                    $plugin_path = $path;
                    error_log('TechOps Content Sync: Found slug match: ' . $plugin_path);
                    break;
                }
                // Check if the filename matches (for single-file plugins like hello.php)
                $parts = explode('/', $path);
                $filename = end($parts);
                if ($filename === $identifier) {
                    $plugin_path = $path;
                    error_log('TechOps Content Sync: Found filename match: ' . $plugin_path);
                    break;
                }
            }
        }

        if (empty($plugin_path)) {
            // Special case for hello.php (Hello Dolly plugin)
            if ($identifier === 'hello.php' && isset($plugins['hello.php'])) {
                $plugin_path = 'hello.php';
                error_log('TechOps Content Sync: Using direct hello.php match');
            } else {
                error_log('TechOps Content Sync: Plugin not found for identifier: ' . $identifier);
                return new \WP_Error(
                    'plugin_not_found',
                    'Plugin not found. Available plugins: ' . implode(', ', array_keys($plugins)),
                    ['status' => 404]
                );
            }
        }

        // Check if plugin is already active
        if (is_plugin_active($plugin_path)) {
            error_log('TechOps Content Sync: Plugin is already active: ' . $plugin_path);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Plugin is already active',
                'plugin' => $plugin_path
            ]);
        }

        // Activate the plugin
        error_log('TechOps Content Sync: Attempting to activate plugin: ' . $plugin_path);
        $result = activate_plugin($plugin_path);
        
        if (is_wp_error($result)) {
            error_log('TechOps Content Sync: Plugin activation failed: ' . $result->get_error_message());
            return new \WP_Error(
                'plugin_activation_failed',
                $result->get_error_message(),
                ['status' => 500]
            );
        }

        error_log('TechOps Content Sync: Plugin activated successfully: ' . $plugin_path);
        return rest_ensure_response([
            'success' => true,
            'message' => 'Plugin activated successfully',
            'plugin' => $plugin_path
        ]);
    }

    /**
     * NEW: Activate a theme from request body
     */
    public function activate_theme_from_body($request) {
        error_log('TechOps Content Sync: Theme activation request received (body method)');
        
        // Get theme from request body
        $theme = $request->get_param('theme');
        if (empty($theme)) {
            error_log('TechOps Content Sync: No theme parameter provided in request body');
            return new \WP_Error(
                'missing_theme',
                'No theme identifier provided in request body',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Theme identifier from body: ' . $theme);
        
        // Proceed with activation using the common method
        return $this->process_theme_activation($theme);
    }

    /**
     * Activate a theme (from URL parameter)
     */
    public function activate_theme($request) {
        error_log('TechOps Content Sync: Theme activation request received (URL method)');
        
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No slug provided in URL');
            return new \WP_Error(
                'missing_slug',
                'No theme slug provided in URL',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Theme identifier from URL: ' . $slug);
        
        // Proceed with activation using the common method
        return $this->process_theme_activation($slug);
    }
    
    /**
     * Common method to process theme activation
     */
    private function process_theme_activation($slug) {
        // Remove any directory traversal attempts
        $slug = str_replace(['../', './'], '', $slug);
        error_log('TechOps Content Sync: Sanitized theme slug: ' . $slug);
        
        $theme = wp_get_theme($slug);

        if (!$theme->exists()) {
            error_log('TechOps Content Sync: Theme not found: ' . $slug);
            return new \WP_Error(
                'theme_not_found',
                'Theme not found',
                ['status' => 404]
            );
        }

        // Check if theme is already active
        if (wp_get_theme()->get_stylesheet() === $slug) {
            error_log('TechOps Content Sync: Theme is already active: ' . $slug);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Theme is already active',
                'theme' => $slug
            ]);
        }

        // Switch to the theme
        error_log('TechOps Content Sync: Attempting to activate theme: ' . $slug);
        switch_theme($slug);
        
        // Verify the switch was successful
        if (wp_get_theme()->get_stylesheet() !== $slug) {
            error_log('TechOps Content Sync: Theme activation failed for: ' . $slug);
            return new \WP_Error(
                'theme_activation_failed',
                'Failed to activate theme',
                ['status' => 500]
            );
        }

        error_log('TechOps Content Sync: Theme activated successfully: ' . $slug);
        return rest_ensure_response([
            'success' => true,
            'message' => 'Theme activated successfully',
            'theme' => $slug
        ]);
    }

    /**
     * NEW: Deactivate a plugin from request body 
     */
    public function deactivate_plugin_from_body($request) {
        error_log('TechOps Content Sync: Plugin deactivation request received (body method)');
        
        // Get plugin from request body
        $plugin = $request->get_param('plugin');
        if (empty($plugin)) {
            error_log('TechOps Content Sync: No plugin parameter provided in request body');
            return new \WP_Error(
                'missing_plugin',
                'No plugin identifier provided in request body',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Plugin identifier from body: ' . $plugin);
        
        // Proceed with deactivation using the common method
        return $this->process_plugin_deactivation($plugin);
    }

    /**
     * Deactivate a plugin (from URL parameter)
     */
    public function deactivate_plugin($request) {
        error_log('TechOps Content Sync: Plugin deactivation request received (URL method)');
        
        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No slug provided in URL');
            return new \WP_Error(
                'missing_slug',
                'No plugin slug provided in URL',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Plugin identifier from URL: ' . $slug);
        
        // Proceed with deactivation using the common method
        return $this->process_plugin_deactivation($slug);
    }
    
    /**
     * Common method to process plugin deactivation
     * This supports multiple formats: slug, file name, or full path
     */
    private function process_plugin_deactivation($identifier) {
        if (!function_exists('deactivate_plugins') || !function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Remove any directory traversal attempts
        $identifier = str_replace(['../', './'], '', $identifier);
        error_log('TechOps Content Sync: Sanitized identifier for deactivation: ' . $identifier);

        $plugins = get_plugins();
        error_log('TechOps Content Sync: Available plugins: ' . print_r(array_keys($plugins), true));

        // Strategy 1: Direct match with plugin file path
        if (isset($plugins[$identifier])) {
            $plugin_path = $identifier;
            error_log('TechOps Content Sync: Found exact match for plugin path: ' . $plugin_path);
        } 
        // Strategy 2: Match as a directory/slug (e.g., "hello-dolly")
        else {
            $plugin_path = '';
            foreach ($plugins as $path => $data) {
                error_log('TechOps Content Sync: Checking path: ' . $path . ' against identifier: ' . $identifier);
                // Check if path starts with the slug followed by slash
                if (strpos($path, $identifier . '/') === 0) {
                    $plugin_path = $path;
                    error_log('TechOps Content Sync: Found slug match: ' . $plugin_path);
                    break;
                }
                // Check if the filename matches (for single-file plugins like hello.php)
                $parts = explode('/', $path);
                $filename = end($parts);
                if ($filename === $identifier) {
                    $plugin_path = $path;
                    error_log('TechOps Content Sync: Found filename match: ' . $plugin_path);
                    break;
                }
            }
        }

        if (empty($plugin_path)) {
            // Special case for hello.php (Hello Dolly plugin)
            if ($identifier === 'hello.php' && isset($plugins['hello.php'])) {
                $plugin_path = 'hello.php';
                error_log('TechOps Content Sync: Using direct hello.php match for deactivation');
            } else {
                error_log('TechOps Content Sync: Plugin not found for identifier: ' . $identifier);
                return new \WP_Error(
                    'plugin_not_found',
                    'Plugin not found. Available plugins: ' . implode(', ', array_keys($plugins)),
                    ['status' => 404]
                );
            }
        }

        // Check if plugin is already inactive
        if (!is_plugin_active($plugin_path)) {
            error_log('TechOps Content Sync: Plugin is already inactive: ' . $plugin_path);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Plugin is already inactive',
                'plugin' => $plugin_path
            ]);
        }

        // Deactivate the plugin
        error_log('TechOps Content Sync: Attempting to deactivate plugin: ' . $plugin_path);
        deactivate_plugins($plugin_path);
        
        // Verify deactivation
        if (is_plugin_active($plugin_path)) {
            error_log('TechOps Content Sync: Plugin deactivation failed for: ' . $plugin_path);
            return new \WP_Error(
                'plugin_deactivation_failed',
                'Failed to deactivate plugin',
                ['status' => 500]
            );
        }

        error_log('TechOps Content Sync: Plugin deactivated successfully: ' . $plugin_path);
        return rest_ensure_response([
            'success' => true,
            'message' => 'Plugin deactivated successfully',
            'plugin' => $plugin_path
        ]);
    }

    /**
     * NEW: Deactivate a theme from request body (switch to default theme)
     */
    public function deactivate_theme_from_body($request) {
        error_log('TechOps Content Sync: Theme deactivation request received (body method)');
        
        // Get theme from request body
        $theme = $request->get_param('theme');
        if (empty($theme)) {
            error_log('TechOps Content Sync: No theme parameter provided in request body');
            return new \WP_Error(
                'missing_theme',
                'No theme identifier provided in request body',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Theme identifier from body: ' . $theme);
        
        // Proceed with deactivation using the common method
        return $this->process_theme_deactivation($theme);
    }

    /**
     * Deactivate a theme (from URL parameter)
     */
    public function deactivate_theme($request) {
        error_log('TechOps Content Sync: Theme deactivation request received (URL method)');
        
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No slug provided in URL');
            return new \WP_Error(
                'missing_slug',
                'No theme slug provided in URL',
                ['status' => 400]
            );
        }
        
        error_log('TechOps Content Sync: Theme identifier from URL: ' . $slug);
        
        // Proceed with deactivation using the common method
        return $this->process_theme_deactivation($slug);
    }
    
    /**
     * Common method to process theme deactivation
     */
    private function process_theme_deactivation($slug) {
        // Remove any directory traversal attempts
        $slug = str_replace(['../', './'], '', $slug);
        error_log('TechOps Content Sync: Sanitized theme slug for deactivation: ' . $slug);
        
        $theme = wp_get_theme($slug);

        if (!$theme->exists()) {
            error_log('TechOps Content Sync: Theme not found: ' . $slug);
            return new \WP_Error(
                'theme_not_found',
                'Theme not found',
                ['status' => 404]
            );
        }

        // Check if theme is already inactive
        if (wp_get_theme()->get_stylesheet() !== $slug) {
            error_log('TechOps Content Sync: Theme is already inactive: ' . $slug);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Theme is already inactive',
                'theme' => $slug
            ]);
        }

        // Get default theme (usually Twenty Twenty-Four or similar)
        $default_theme = wp_get_theme('twentytwentyfour');
        if (!$default_theme->exists()) {
            // Fallback to any available theme
            $themes = wp_get_themes();
            $default_theme = reset($themes);
        }

        // Switch to default theme
        error_log('TechOps Content Sync: Attempting to deactivate theme by switching to: ' . $default_theme->get_stylesheet());
        switch_theme($default_theme->get_stylesheet());
        
        // Verify the switch was successful
        if (wp_get_theme()->get_stylesheet() === $slug) {
            error_log('TechOps Content Sync: Theme deactivation failed for: ' . $slug);
            return new \WP_Error(
                'theme_deactivation_failed',
                'Failed to deactivate theme',
                ['status' => 500]
            );
        }

        error_log('TechOps Content Sync: Theme deactivated successfully: ' . $slug);
        return rest_ensure_response([
            'success' => true,
            'message' => 'Theme deactivated successfully',
            'theme' => $slug,
            'switched_to' => $default_theme->get_stylesheet()
        ]);
    }
}