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

        // NEW: Install plugin from WordPress.org endpoint
        register_rest_route('techops/v1', '/plugins/install', [
            'methods' => 'POST',
            'callback' => [$this, 'install_plugin_from_wporg'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
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

        // New download endpoints
        register_rest_route('techops/v1', '/plugins/downloads', [
            'methods' => 'POST',
            'callback' => [$this, 'download_plugin_from_source'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'source_url' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/themes/downloads', [
            'methods' => 'POST',
            'callback' => [$this, 'download_theme_from_source'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'source_url' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                ]
            ]
        ]);

        // Register the routes for the tasklist endpoints
        $this->register_tasklist_routes();

        // NEW: Install theme from WordPress.org endpoint
        register_rest_route('techops/v1', '/themes/install', [
            'methods' => 'POST',
            'callback' => [$this, 'install_theme_from_wporg'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // NEW: Update plugin endpoint
        register_rest_route('techops/v1', '/plugins/update', [
            'methods' => 'POST',
            'callback' => [$this, 'update_plugin'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // NEW: Update theme endpoint
        register_rest_route('techops/v1', '/themes/update', [
            'methods' => 'POST',
            'callback' => [$this, 'update_theme'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // NEW: Update plugin to specific version endpoint
        register_rest_route('techops/v1', '/plugins/update-version', [
            'methods' => 'POST',
            'callback' => [$this, 'update_plugin_to_version'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'version' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // NEW: Update theme to specific version endpoint
        register_rest_route('techops/v1', '/themes/update-version', [
            'methods' => 'POST',
            'callback' => [$this, 'update_theme_to_version'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'version' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // NEW: Custom plugin installation endpoint
        register_rest_route('techops/v1', '/plugins/install-custom', [
            'methods' => 'POST',
            'callback' => [$this, 'install_custom_plugin'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'source_url' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                ],
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'source_auth' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // NEW: Custom theme installation endpoint
        register_rest_route('techops/v1', '/themes/install-custom', [
            'methods' => 'POST',
            'callback' => [$this, 'install_custom_theme'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'source_url' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                ],
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'source_auth' => [
                    'required' => false,
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
        // 1) Ensure WP's plugin functions are loaded
        if ( ! function_exists('get_plugins') ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // 2) Grab all installed plugins and the active_plugins option
        $all_plugins    = get_plugins();
        $active_plugins = get_option('active_plugins', []);

        // 3) Load the "update_plugins" transient so we know what the latest versions are
        $update_plugins = get_site_transient('update_plugins');
        // If WordPress hasn't checked recently, force a refresh:
        if ( ! isset($update_plugins->checked) || ! is_array($update_plugins->checked) ) {
            wp_version_check();
            wp_update_plugins();
            $update_plugins = get_site_transient('update_plugins');
        }

        $formatted = [];
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            // a) Derive the "slug" from the folder name
            $slug = explode('/', $plugin_path)[0];

            // b) Get detailed plugin data including update information
            $full_plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            $current_version = $full_plugin_data['Version'];

            // c) Look up in the transient's response array for any update
            $has_update = false;
            $update_version = null;
            if (
                isset($update_plugins->response)
                && is_array($update_plugins->response)
                && isset($update_plugins->response[$plugin_path])
            ) {
                $has_update = true;
                $update_version = $update_plugins->response[$plugin_path]->new_version;
            }

            $formatted[] = [
                'name'           => $plugin_data['Name'],
                'slug'           => $slug,
                'version'        => $current_version,
                'has_update'     => $has_update,
                'update_version' => $update_version,
                'active'         => in_array($plugin_path, $active_plugins, true),
                'path'           => $plugin_path,
            ];
        }

        return rest_ensure_response($formatted);
    }

    /**
     * Get list of installed themes
     */
    public function get_themes_list() {
        // 1) Ensure WP's theme functions are loaded
        if (!function_exists('wp_get_themes')) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }

        // 2) Get all themes and active theme
        $all_themes = wp_get_themes();
        $active_theme = wp_get_theme();

        // 3) Load the "update_themes" transient so we know what the latest versions are
        $update_themes = get_site_transient('update_themes');
        // If WordPress hasn't checked recently, force a refresh:
        if (!isset($update_themes->checked) || !is_array($update_themes->checked)) {
            wp_version_check();
            wp_update_themes();
            $update_themes = get_site_transient('update_themes');
        }

        $formatted = [];
        foreach ($all_themes as $theme_slug => $theme) {
            // Get detailed theme data
            $theme_data = wp_get_theme($theme_slug);
            $current_version = $theme_data->get('Version');

            // Check for updates
            $has_update = false;
            $update_version = null;
            if (
                isset($update_themes->response)
                && is_array($update_themes->response)
                && isset($update_themes->response[$theme_slug])
            ) {
                $has_update = true;
                $update_version = $update_themes->response[$theme_slug]['new_version'];
            }

            $formatted[] = [
                'name'           => $theme_data->get('Name'),
                'slug'           => $theme_slug,
                'version'        => $current_version,
                'has_update'     => $has_update,
                'update_version' => $update_version,
                'active'         => ($theme_slug === $active_theme->get_stylesheet()),
                'path'           => $theme_data->get_stylesheet_directory()
            ];
        }

        return rest_ensure_response($formatted);
    }

    /**
     * Download a plugin as a zip file
     */
    public function download_plugin($request) {
        $slug = $request->get_param('slug');
        
        // Get plugin data
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugins = get_plugins();
        $plugin_path = null;
        
        // Find the plugin path by slug
        foreach ($plugins as $path => $data) {
            if (strpos($path, $slug . '/') === 0) {
                $plugin_path = $path;
                break;
            }
        }
        
        if (!$plugin_path) {
            return new WP_Error('plugin_not_found', 'Plugin not found', ['status' => 404]);
        }
        
        // Get the plugin directory
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_path);
        
        // Create a temporary directory for the zip
        $temp_dir = get_temp_dir();
        $zip_file = $temp_dir . $slug . '.zip';
        
        // Create the zip file
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return new WP_Error('zip_error', 'Could not create zip file', ['status' => 500]);
        }
        
        // Add files to the zip
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($plugin_dir) + 1);
                
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
        
        // Read the file content
        $file_content = file_get_contents($zip_file);
        
        // Clean up the temporary file immediately after reading
        unlink($zip_file);
        
        if ($file_content === false) {
            error_log('TechOps Content Sync: Could not read zip file content for ' . $slug);
            return new WP_Error('file_read_error', 'Could not read zip file content', ['status' => 500]);
        }
        
        // Create a REST response
        $response = new WP_REST_Response($file_content, 200);
        
        // Set headers for download
        $response->header('Content-Type', 'application/zip');
        $response->header('Content-Disposition', 'attachment; filename="' . $slug . '.zip"');
        $response->header('Content-Length', strlen($file_content));
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        
        return $response;
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

    /**
     * Download plugin from source site
     */
    public function download_plugin_from_source($request) {
        // Ensure we're sending JSON response
        header('Content-Type: application/json');
        
        error_log("\n\n=== TECHOPS CONTENT SYNC: STARTING PLUGIN DOWNLOAD ===");
        
        $slug = $request->get_param('slug');
        $source_url = $request->get_param('source_url');
        $source_auth = $request->get_param('source_auth');
        
        error_log("TECHOPS CONTENT SYNC: Request Parameters:");
        error_log("- Slug: " . $slug);
        error_log("- Source URL: " . $source_url);
        error_log("- Auth Provided: " . (!empty($source_auth) ? 'Yes' : 'No'));
        
        // Initialize steps array to track progress
        $steps = [];
        
        // Step 1: Construct download URL
        error_log("\nTECHOPS CONTENT SYNC: Step 1 - Constructing download URL");
        // Remove any trailing slashes and wp-json/techops/v1/plugins/download from source_url if present
        $source_url = rtrim($source_url, '/');
        $source_url = preg_replace('#/wp-json/techops/v1/plugins/download/?$#', '', $source_url);
        $download_url = $source_url . '/wp-json/techops/v1/plugins/download/' . $slug;
        
        error_log("TECHOPS CONTENT SYNC: Cleaned source URL: " . $source_url);
        error_log("TECHOPS CONTENT SYNC: Final download URL: " . $download_url);
        
        $steps[] = [
            'step' => 1,
            'description' => 'Constructing download URL',
            'status' => 'completed',
            'timestamp' => current_time('mysql')
        ];
        
        // Initialize download handler
        $download_handler = new Download_Handler();
        
        // Step 2: Create directories
        error_log("\nTECHOPS CONTENT SYNC: Step 2 - Creating download directories");
        $result = $download_handler->create_download_directories();
        if (is_wp_error($result)) {
            $error_message = $result->get_error_message();
            error_log("TECHOPS CONTENT SYNC: Directory creation failed: " . $error_message);
            $steps[] = [
                'step' => 2,
                'description' => 'Creating download directories',
                'status' => 'failed',
                'error' => $error_message,
                'timestamp' => current_time('mysql')
            ];
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to create download directories: ' . $error_message,
                'steps' => $steps
            ], 500);
        }
        error_log("TECHOPS CONTENT SYNC: Directories created successfully");
        $steps[] = [
            'step' => 2,
            'description' => 'Creating download directories',
            'status' => 'completed',
            'timestamp' => current_time('mysql')
        ];
        
        // Step 3: Download file
        error_log("\nTECHOPS CONTENT SYNC: Step 3 - Starting file download");
        error_log("TECHOPS CONTENT SYNC: Download URL: " . $download_url);
        
        try {
            // If source_auth is provided, ensure it's in the correct format
            if (!empty($source_auth)) {
                // If source_auth doesn't start with 'Basic ', add it
                if (strpos($source_auth, 'Basic ') !== 0) {
                    $source_auth = 'Basic ' . $source_auth;
                }
            }
            
            $zip_file = $download_handler->download_and_save_file($download_url, 'plugins', $slug, $source_auth);
            if (is_wp_error($zip_file)) {
                $error_message = $zip_file->get_error_message();
                error_log("TECHOPS CONTENT SYNC: Download failed: " . $error_message);
                $steps[] = [
                    'step' => 3,
                    'description' => 'Downloading ZIP file',
                    'status' => 'failed',
                    'error' => $error_message,
                    'timestamp' => current_time('mysql')
                ];
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Failed to download plugin: ' . $error_message,
                    'steps' => $steps
                ], 500);
            }
            
            error_log("TECHOPS CONTENT SYNC: File downloaded successfully to: " . $zip_file);
            $steps[] = [
                'step' => 3,
                'description' => 'Downloading ZIP file',
                'status' => 'completed',
                'timestamp' => current_time('mysql')
            ];
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            error_log("TECHOPS CONTENT SYNC: Download exception: " . $error_message);
            $steps[] = [
                'step' => 3,
                'description' => 'Downloading ZIP file',
                'status' => 'failed',
                'error' => $error_message,
                'timestamp' => current_time('mysql')
            ];
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Download failed with exception: ' . $error_message,
                'steps' => $steps
            ], 500);
        }
        
        // Step 4: Extract ZIP file
        error_log("\nTECHOPS CONTENT SYNC: Step 4 - Starting ZIP extraction");
        $extract_dir = $download_handler->extract_zip_file($zip_file, 'plugins', $slug);
        if (is_wp_error($extract_dir)) {
            $error_message = $extract_dir->get_error_message();
            error_log("TECHOPS CONTENT SYNC: Extraction failed: " . $error_message);
            $steps[] = [
                'step' => 4,
                'description' => 'Extracting ZIP file',
                'status' => 'failed',
                'error' => $error_message,
                'timestamp' => current_time('mysql')
            ];
            $download_handler->cleanup_temp_files('plugins', $slug);
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to extract plugin: ' . $error_message,
                'steps' => $steps
            ], 500);
        }
        error_log("TECHOPS CONTENT SYNC: ZIP extraction completed successfully");
        $steps[] = [
            'step' => 4,
            'description' => 'Extracting ZIP file',
            'status' => 'completed',
            'timestamp' => current_time('mysql')
        ];
        
        // Step 5: Validate plugin files
        error_log("\nTECHOPS CONTENT SYNC: Step 5 - Validating plugin files");
        $validation = $download_handler->validate_plugin_files($extract_dir);
        if (is_wp_error($validation)) {
            $error_message = $validation->get_error_message();
            error_log("TECHOPS CONTENT SYNC: Validation failed: " . $error_message);
            $steps[] = [
                'step' => 5,
                'description' => 'Validating plugin files',
                'status' => 'failed',
                'error' => $error_message,
                'timestamp' => current_time('mysql')
            ];
            $download_handler->cleanup_temp_files('plugins', $slug);
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to validate plugin: ' . $error_message,
                'steps' => $steps
            ], 500);
        }
        error_log("TECHOPS CONTENT SYNC: Plugin validation completed successfully");
        $steps[] = [
            'step' => 5,
            'description' => 'Validating plugin files',
            'status' => 'completed',
            'timestamp' => current_time('mysql')
        ];
        
        // Step 6: Move to final destination
        error_log("\nTECHOPS CONTENT SYNC: Step 6 - Moving plugin to final destination");
        $move_result = $download_handler->move_to_final_destination($extract_dir, 'plugins', $slug);
        if (is_wp_error($move_result)) {
            $error_message = $move_result->get_error_message();
            error_log("TECHOPS CONTENT SYNC: Move failed: " . $error_message);
            $steps[] = [
                'step' => 6,
                'description' => 'Moving to final destination',
                'status' => 'failed',
                'error' => $error_message,
                'timestamp' => current_time('mysql')
            ];
            $download_handler->cleanup_temp_files('plugins', $slug);
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Failed to move plugin to final destination: ' . $error_message,
                'steps' => $steps
            ], 500);
        }
        error_log("TECHOPS CONTENT SYNC: Plugin moved to final destination successfully");
        $steps[] = [
            'step' => 6,
            'description' => 'Moving to final destination',
            'status' => 'completed',
            'timestamp' => current_time('mysql')
        ];
        
        // Step 7: Cleanup
        error_log("\nTECHOPS CONTENT SYNC: Step 7 - Cleaning up temporary files");
        // Temporarily commenting out cleanup for debugging
        // $download_handler->cleanup_temp_files('plugins', $slug);
        error_log("TECHOPS CONTENT SYNC: Cleanup skipped for debugging purposes");
        $steps[] = [
            'step' => 7,
            'description' => 'Cleaning up temporary files',
            'status' => 'skipped',
            'timestamp' => current_time('mysql')
        ];
        
        error_log("\n=== TECHOPS CONTENT SYNC: PLUGIN DOWNLOAD COMPLETED SUCCESSFULLY ===\n");
        
        // Return success response with all steps
        $response = new \WP_REST_Response([
            'success' => true,
            'message' => 'Plugin downloaded and installed successfully',
            'slug' => $slug,
            'steps' => $steps,
            'total_steps' => count($steps),
            'completed_steps' => count(array_filter($steps, function($step) {
                return $step['status'] === 'completed';
            })),
            'debug_info' => [
                'temp_dir' => WP_CONTENT_DIR . '/plugins/techops-content-sync/downloads/plugins/temp',
                'extract_dir' => WP_CONTENT_DIR . '/plugins/techops-content-sync/downloads/plugins/temp/' . $slug,
                'zip_file' => WP_CONTENT_DIR . '/plugins/techops-content-sync/downloads/plugins/temp/' . $slug . '.zip'
            ]
        ], 200);
        
        // Set content type to JSON
        $response->header('Content-Type', 'application/json');
        
        return $response;
    }

    /**
     * Download theme from source site
     */
    public function download_theme_from_source($request) {
        $slug = $request->get_param('slug');
        $source_url = $request->get_param('source_url');
        $source_auth = $request->get_param('source_auth');
        
        // Initialize download handler
        $download_handler = new Download_Handler();
        
        // Create necessary directories
        $result = $download_handler->create_download_directories();
        if (is_wp_error($result)) {
            return $result;
        }
        
        // If source_auth is provided, ensure it's in the correct format
        if (!empty($source_auth)) {
            // If source_auth doesn't start with 'Basic ', add it
            if (strpos($source_auth, 'Basic ') !== 0) {
                $source_auth = 'Basic ' . $source_auth;
            }
        }
        
        // Download the file
        $zip_file = $download_handler->download_and_save_file($source_url, 'themes', $slug, $source_auth);
        if (is_wp_error($zip_file)) {
            return $zip_file;
        }
        
        // Extract the ZIP file
        $extract_dir = $download_handler->extract_zip_file($zip_file, 'themes', $slug);
        if (is_wp_error($extract_dir)) {
            $download_handler->cleanup_temp_files('themes', $slug);
            return $extract_dir;
        }
        
        // Validate theme files
        $validation = $download_handler->validate_theme_files($extract_dir);
        if (is_wp_error($validation)) {
            $download_handler->cleanup_temp_files('themes', $slug);
            return $validation;
        }
        
        // Move to final destination
        $move_result = $download_handler->move_to_final_destination($extract_dir, 'themes', $slug);
        if (is_wp_error($move_result)) {
            $download_handler->cleanup_temp_files('themes', $slug);
            return $move_result;
        }
        
        // Clean up temporary files
        $download_handler->cleanup_temp_files('themes', $slug);
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Theme downloaded and installed successfully',
            'slug' => $slug
        ]);
    }

    /**
     * Register the routes for the tasklist endpoints
     */
    private function register_tasklist_routes() {
        register_rest_route('techops/v1', '/tasklists', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_tasklists'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_tasklist'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));

        register_rest_route('techops/v1', '/tasklists/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_tasklist'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_tasklist'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_tasklist'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));

        register_rest_route('techops/v1', '/tasklists/(?P<id>\d+)/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_tasklist'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    /**
     * Get all tasklists
     */
    public function get_tasklists($request) {
        $tasklist_handler = new Tasklist_Handler();
        $args = array(
            'status' => $request->get_param('status'),
            'limit' => $request->get_param('limit'),
            'offset' => $request->get_param('offset'),
        );

        $tasklists = $tasklist_handler->list_tasklists($args);

        if (is_wp_error($tasklists)) {
            return new \WP_Error(
                'tasklist_error',
                $tasklists->get_error_message(),
                array('status' => 400)
            );
        }

        return rest_ensure_response($tasklists);
    }

    /**
     * Create a new tasklist
     */
    public function create_tasklist($request) {
        $tasklist_handler = new Tasklist_Handler();
        $data = $request->get_params();

        $result = $tasklist_handler->create_tasklist($data);

        if (is_wp_error($result)) {
            return new \WP_Error(
                'tasklist_error',
                $result->get_error_message(),
                array('status' => 400)
            );
        }

        return rest_ensure_response(array(
            'id' => $result,
            'message' => 'Tasklist created successfully'
        ));
    }

    /**
     * Get a single tasklist
     */
    public function get_tasklist($request) {
        $tasklist_handler = new Tasklist_Handler();
        $id = $request->get_param('id');

        $tasklist = $tasklist_handler->get_tasklist($id);

        if (is_wp_error($tasklist)) {
            return new \WP_Error(
                'tasklist_error',
                $tasklist->get_error_message(),
                array('status' => 404)
            );
        }

        return rest_ensure_response($tasklist);
    }

    /**
     * Update a tasklist
     */
    public function update_tasklist($request) {
        $tasklist_handler = new Tasklist_Handler();
        $id = $request->get_param('id');
        $data = $request->get_params();

        $result = $tasklist_handler->update_tasklist($id, $data);

        if (is_wp_error($result)) {
            return new \WP_Error(
                'tasklist_error',
                $result->get_error_message(),
                array('status' => 400)
            );
        }

        return rest_ensure_response(array(
            'message' => 'Tasklist updated successfully'
        ));
    }

    /**
     * Delete a tasklist
     */
    public function delete_tasklist($request) {
        $tasklist_handler = new Tasklist_Handler();
        $id = $request->get_param('id');

        $result = $tasklist_handler->delete_tasklist($id);

        if (is_wp_error($result)) {
            return new \WP_Error(
                'tasklist_error',
                $result->get_error_message(),
                array('status' => 400)
            );
        }

        return rest_ensure_response(array(
            'message' => 'Tasklist deleted successfully'
        ));
    }

    /**
     * Sync a tasklist with GitHub
     */
    public function sync_tasklist($request) {
        $tasklist_handler = new Tasklist_Handler();
        $github_handler = new GitHub_Handler();
        $id = $request->get_param('id');

        $tasklist = $tasklist_handler->get_tasklist($id);

        if (is_wp_error($tasklist)) {
            return new \WP_Error(
                'tasklist_error',
                $tasklist->get_error_message(),
                array('status' => 404)
            );
        }

        if (empty($tasklist->github_path)) {
            return new \WP_Error(
                'tasklist_error',
                'No GitHub path specified for this tasklist',
                array('status' => 400)
            );
        }

        $result = $github_handler->download_file($tasklist->github_path);

        if (is_wp_error($result)) {
            return new \WP_Error(
                'tasklist_error',
                'Failed to sync with GitHub: ' . $result->get_error_message(),
                array('status' => 400)
            );
        }

        $tasklist_handler->update_last_sync($id);

        return rest_ensure_response(array(
            'message' => 'Tasklist synced successfully with GitHub'
        ));
    }

    /**
     * Install and activate a plugin from WordPress.org
     */
    public function install_plugin_from_wporg($request) {
        error_log('TechOps Content Sync: Plugin installation request received from WordPress.org');
        
        // Get the plugin slug
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No plugin slug provided');
            return new \WP_Error(
                'missing_slug',
                'No plugin slug provided',
                ['status' => 400]
            );
        }

        // Include necessary files
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Check if plugin is already installed
        $installed = get_plugins();
        foreach ($installed as $path => $details) {
            if (strpos($path, trailingslashit($slug)) === 0) {
                // Plugin exists, try to activate it
                $activate_result = activate_plugin($path);
                if (is_wp_error($activate_result)) {
                    error_log('TechOps Content Sync: Plugin activation failed: ' . $activate_result->get_error_message());
                    return new \WP_Error(
                        'activation_failed',
                        'Plugin exists but activation failed: ' . $activate_result->get_error_message(),
                        ['status' => 500]
                    );
                }
                return rest_ensure_response([
                    'success' => true,
                    'installed' => true,
                    'activated' => true,
                    'message' => sprintf('Plugin "%s" was already present and is now activated.', $slug)
                ]);
            }
        }

        // Get plugin info from WordPress.org
        $api = plugins_api('plugin_information', [
            'slug' => $slug,
            'fields' => [
                'sections' => false,
                'reviews' => false,
                'downloaded' => false,
                'last_updated' => false,
                'tags' => false,
                'compatibility' => false,
                'donate_link' => false,
                'requires' => false,
                'rating' => false,
                'ratings' => false,
                'homepage' => false,
                'short_description' => false,
            ],
        ]);

        if (is_wp_error($api)) {
            error_log('TechOps Content Sync: Failed to fetch plugin info: ' . $api->get_error_message());
            return new \WP_Error(
                'api_error',
                'Could not fetch plugin information: ' . $api->get_error_message(),
                ['status' => 500]
            );
        }

        // Install the plugin
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $install_result = $upgrader->install($api->download_link);

        if (is_wp_error($install_result)) {
            error_log('TechOps Content Sync: Plugin installation failed: ' . $install_result->get_error_message());
            return new \WP_Error(
                'installation_failed',
                'Plugin installation failed: ' . $install_result->get_error_message(),
                ['status' => 500]
            );
        }

        // Activate the plugin
        if (!empty($skin->result) && !empty($skin->result['destination_name'])) {
            $plugin_folder = $skin->result['destination_name'];
            $all_plugins = get_plugins('/' . $plugin_folder);
            
            if (!empty($all_plugins)) {
                $paths = array_keys($all_plugins);
                $main = $plugin_folder . '/' . $paths[0];
                
                $activate_result = activate_plugin($main);
                if (is_wp_error($activate_result)) {
                    error_log('TechOps Content Sync: Plugin activation failed: ' . $activate_result->get_error_message());
                    return new \WP_Error(
                        'activation_failed',
                        'Installed but activation failed: ' . $activate_result->get_error_message(),
                        ['status' => 500]
                    );
                }

                return rest_ensure_response([
                    'success' => true,
                    'installed' => true,
                    'activated' => true,
                    'plugin_file' => $main,
                    'destination' => $plugin_folder,
                    'message' => sprintf('Plugin "%s" was installed and activated successfully.', $slug)
                ]);
            }
        }

        error_log('TechOps Content Sync: Plugin installation succeeded but could not locate plugin files');
        return new \WP_Error(
            'unknown_error',
            'Plugin install succeeded but we could not locate the plugin files to activate.',
            ['status' => 500]
        );
    }

    /**
     * Install and activate a theme from WordPress.org
     */
    public function install_theme_from_wporg($request) {
        error_log('TechOps Content Sync: Theme installation request received from WordPress.org');
        
        // Get the theme slug
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No theme slug provided');
            return new \WP_Error(
                'missing_slug',
                'No theme slug provided',
                ['status' => 400]
            );
        }

        // Include necessary files
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader.php';

        // Check if theme is already installed
        $theme = wp_get_theme($slug);
        if ($theme->exists()) {
            // Theme exists, try to activate it
            switch_theme($slug);
            if (wp_get_theme()->get_stylesheet() === $slug) {
                return rest_ensure_response([
                    'success' => true,
                    'installed' => true,
                    'activated' => true,
                    'message' => sprintf('Theme "%s" was already present and is now activated.', $slug)
                ]);
            }
        }

        // Get theme info from WordPress.org
        $api = themes_api('theme_information', [
            'slug' => $slug,
            'fields' => [
                'sections' => false,
                'tags' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'homepage' => false,
                'screenshots' => false,
                'download_link' => true,
            ],
        ]);

        if (is_wp_error($api)) {
            error_log('TechOps Content Sync: Failed to fetch theme info: ' . $api->get_error_message());
            return new \WP_Error(
                'api_error',
                'Could not fetch theme information: ' . $api->get_error_message(),
                ['status' => 500]
            );
        }

        // Install the theme
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Theme_Upgrader($skin);
        $install_result = $upgrader->install($api->download_link);

        if (is_wp_error($install_result)) {
            error_log('TechOps Content Sync: Theme installation failed: ' . $install_result->get_error_message());
            return new \WP_Error(
                'installation_failed',
                'Theme installation failed: ' . $install_result->get_error_message(),
                ['status' => 500]
            );
        }

        // Activate the theme
        switch_theme($slug);
        if (wp_get_theme()->get_stylesheet() === $slug) {
            return rest_ensure_response([
                'success' => true,
                'installed' => true,
                'activated' => true,
                'message' => sprintf('Theme "%s" was installed and activated successfully.', $slug)
            ]);
        }

        error_log('TechOps Content Sync: Theme installation succeeded but activation failed');
        return new \WP_Error(
            'activation_failed',
            'Theme was installed but could not be activated.',
            ['status' => 500]
        );
    }

    /**
     * Update a plugin to its latest version
     */
    public function update_plugin($request) {
        error_log('TechOps Content Sync: Plugin update request received');
        
        // Get the plugin slug
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            error_log('TechOps Content Sync: No plugin slug provided');
            return new \WP_Error(
                'missing_slug',
                'No plugin slug provided',
                ['status' => 400]
            );
        }

        // Include necessary files
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        // Get all installed plugins
        $installed_plugins = get_plugins();
        $plugin_path = '';
        $was_active = false;

        // Find the plugin path and check if it's active
        foreach ($installed_plugins as $path => $plugin_data) {
            if (strpos($path, trailingslashit($slug)) === 0) {
                $plugin_path = $path;
                $was_active = is_plugin_active($path);
                $current_version = $plugin_data['Version'];
                break;
            }
        }

        if (empty($plugin_path)) {
            error_log('TechOps Content Sync: Plugin not found: ' . $slug);
            return new \WP_Error(
                'plugin_not_found',
                'Plugin not found',
                ['status' => 404]
            );
        }

        // Force WordPress to check for updates
        wp_update_plugins();
        
        // Get update information
        $update_plugins = get_site_transient('update_plugins');
        if (empty($update_plugins->response[$plugin_path])) {
            error_log('TechOps Content Sync: No update available for plugin: ' . $slug);
            return new \WP_Error(
                'no_update_available',
                'No update available for this plugin',
                ['status' => 400]
            );
        }

        $update = $update_plugins->response[$plugin_path];
        $new_version = $update->new_version;

        error_log(sprintf(
            'TechOps Content Sync: Updating plugin %s from version %s to %s',
            $slug,
            $current_version,
            $new_version
        ));

        // Create upgrader instance
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);

        // Perform the update
        $update_result = $upgrader->upgrade($plugin_path);

        if (is_wp_error($update_result)) {
            error_log('TechOps Content Sync: Plugin update failed: ' . $update_result->get_error_message());
            return new \WP_Error(
                'update_failed',
                'Plugin update failed: ' . $update_result->get_error_message(),
                ['status' => 500]
            );
        }

        // Check if update was successful
        if (!$update_result) {
            error_log('TechOps Content Sync: Plugin update failed: Unknown error');
            return new \WP_Error(
                'update_failed',
                'Plugin update failed: Unknown error',
                ['status' => 500]
            );
        }

        // Reactivate the plugin if it was active before
        $is_active = false;
        if ($was_active) {
            $activate_result = activate_plugin($plugin_path);
            if (is_wp_error($activate_result)) {
                error_log('TechOps Content Sync: Plugin reactivation failed: ' . $activate_result->get_error_message());
                // Don't return error here, just log it as the update was successful
            } else {
                $is_active = true;
            }
        }

        // Get updated plugin data
        $updated_plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
        $final_version = $updated_plugin_data['Version'];

        error_log(sprintf(
            'TechOps Content Sync: Plugin %s updated successfully from version %s to %s',
            $slug,
            $current_version,
            $final_version
        ));

        return rest_ensure_response([
            'success' => true,
            'plugin' => [
                'slug' => $slug,
                'old_version' => $current_version,
                'new_version' => $final_version,
                'status' => 'updated',
                'was_active' => $was_active,
                'is_active' => $is_active
            ],
            'message' => sprintf(
                'Plugin "%s" updated successfully from version %s to %s',
                $slug,
                $current_version,
                $final_version
            )
        ]);
    }

    /**
     * Update a theme to its latest version
     */
    public function update_theme($request) {
        try {
            error_log("\n=== TECHOPS CONTENT SYNC: STARTING THEME UPDATE PROCESS ===");
            error_log("Request received at: " . current_time('mysql'));
            
            // Get the theme slug
            $slug = $request->get_param('slug');
            if (empty($slug)) {
                error_log("ERROR: No theme slug provided");
                return new \WP_Error(
                    'missing_slug',
                    'No theme slug provided',
                    ['status' => 400]
                );
            }
            error_log("Processing theme slug: " . $slug);

            // Include necessary files with error checking
            $required_files = [
                'wp-admin/includes/theme.php',
                'wp-admin/includes/class-wp-upgrader.php',
                'wp-admin/includes/class-wp-ajax-upgrader-skin.php',
                'wp-admin/includes/class-theme-upgrader.php'
            ];

            foreach ($required_files as $file) {
                if (!file_exists(ABSPATH . $file)) {
                    error_log("ERROR: Required file not found: " . $file);
                    return new \WP_Error(
                        'missing_file',
                        'Required WordPress file not found: ' . $file,
                        ['status' => 500]
                    );
                }
                require_once ABSPATH . $file;
            }
            error_log("All required files loaded successfully");

            // Get theme information with detailed error checking
            error_log("Attempting to get theme information for: " . $slug);
            $theme = wp_get_theme($slug);
            
            if (!$theme->exists()) {
                error_log("ERROR: Theme not found: " . $slug);
                return new \WP_Error(
                    'theme_not_found',
                    'Theme not found',
                    ['status' => 404]
                );
            }

            $current_version = $theme->get('Version');
            $was_active = (wp_get_theme()->get_stylesheet() === $slug);
            error_log(sprintf(
                "Theme found - Current version: %s, Was active: %s",
                $current_version,
                $was_active ? 'Yes' : 'No'
            ));

            // Force WordPress to check for updates
            error_log("Checking for theme updates...");
            wp_update_themes();
            
            // Get update information with detailed error checking
            $update_themes = get_site_transient('update_themes');
            if (empty($update_themes)) {
                error_log("ERROR: Failed to get update information from WordPress.org");
                return new \WP_Error(
                    'update_check_failed',
                    'Failed to check for theme updates',
                    ['status' => 500]
                );
            }

            if (empty($update_themes->response[$slug])) {
                error_log("No update available for theme: " . $slug);
                return new \WP_Error(
                    'no_update_available',
                    'No update available for this theme',
                    ['status' => 400]
                );
            }

            $update = $update_themes->response[$slug];
            $new_version = $update['new_version'];
            error_log(sprintf(
                "Update available - Current: %s, New: %s",
                $current_version,
                $new_version
            ));

            // Create upgrader instance with error checking
            error_log("Initializing theme upgrader...");
            try {
                $skin = new \WP_Ajax_Upgrader_Skin();
                $upgrader = new \Theme_Upgrader($skin);
            } catch (\Exception $e) {
                error_log("ERROR: Failed to initialize upgrader: " . $e->getMessage());
                return new \WP_Error(
                    'upgrader_init_failed',
                    'Failed to initialize theme upgrader: ' . $e->getMessage(),
                    ['status' => 500]
                );
            }

            // Temporarily remove problematic hooks
            error_log("Temporarily removing problematic hooks...");
            $removed_hooks = [];
            global $wp_filter;
            
            // Store and remove hooks that might cause conflicts
            if (isset($wp_filter['upgrader_process_complete'])) {
                $removed_hooks['upgrader_process_complete'] = $wp_filter['upgrader_process_complete'];
                unset($wp_filter['upgrader_process_complete']);
            }
            
            if (isset($wp_filter['upgrader_post_install'])) {
                $removed_hooks['upgrader_post_install'] = $wp_filter['upgrader_post_install'];
                unset($wp_filter['upgrader_post_install']);
            }

            // Perform the update with detailed error handling
            error_log("Starting theme update process...");
            try {
                $update_result = $upgrader->upgrade($slug);
                
                if (is_wp_error($update_result)) {
                    error_log("ERROR: Theme update failed: " . $update_result->get_error_message());
                    return new \WP_Error(
                        'update_failed',
                        'Theme update failed: ' . $update_result->get_error_message(),
                        ['status' => 500]
                    );
                }

                if (!$update_result) {
                    error_log("ERROR: Theme update failed with unknown error");
                    return new \WP_Error(
                        'update_failed',
                        'Theme update failed: Unknown error',
                        ['status' => 500]
                    );
                }
            } catch (\Exception $e) {
                error_log("ERROR: Exception during theme update: " . $e->getMessage());
                return new \WP_Error(
                    'update_exception',
                    'Exception during theme update: ' . $e->getMessage(),
                    ['status' => 500]
                );
            } finally {
                // Restore removed hooks
                error_log("Restoring previously removed hooks...");
                foreach ($removed_hooks as $hook_name => $hook_data) {
                    $wp_filter[$hook_name] = $hook_data;
                }
            }

            // Reactivate the theme if it was active before
            $is_active = false;
            if ($was_active) {
                error_log("Attempting to reactivate theme...");
                try {
                    switch_theme($slug);
                    $is_active = (wp_get_theme()->get_stylesheet() === $slug);
                } catch (\Exception $e) {
                    error_log("ERROR: Exception during theme reactivation: " . $e->getMessage());
                }
            }

            // Get updated theme data
            $updated_theme = wp_get_theme($slug);
            $final_version = $updated_theme->get('Version');

            error_log(sprintf(
                "Theme update completed successfully - Old version: %s, New version: %s",
                $current_version,
                $final_version
            ));

            $response = [
                'success' => true,
                'theme' => [
                    'slug' => $slug,
                    'old_version' => $current_version,
                    'new_version' => $final_version,
                    'status' => 'updated',
                    'was_active' => $was_active,
                    'is_active' => $is_active
                ],
                'message' => sprintf(
                    'Theme "%s" updated successfully from version %s to %s',
                    $slug,
                    $current_version,
                    $final_version
                ),
                'debug_info' => [
                    'update_check_time' => current_time('mysql'),
                    'update_source' => 'WordPress.org',
                    'upgrader_class' => get_class($upgrader),
                    'skin_class' => get_class($skin)
                ]
            ];

            error_log("=== TECHOPS CONTENT SYNC: THEME UPDATE PROCESS COMPLETED ===\n");
            return rest_ensure_response($response);

        } catch (\Exception $e) {
            error_log("CRITICAL ERROR in theme update process: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new \WP_Error(
                'critical_error',
                'A critical error occurred during theme update: ' . $e->getMessage(),
                [
                    'status' => 500,
                    'debug_info' => [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]
            );
        }
    }

    /**
     * Update a plugin to a specific version
     */
    public function update_plugin_to_version($request) {
        try {
            error_log("\n=== TECHOPS CONTENT SYNC: STARTING PLUGIN VERSION UPDATE PROCESS ===");
            error_log("Request received at: " . current_time('mysql'));
            
            // Get the plugin slug and target version
            $slug = $request->get_param('slug');
            $target_version = $request->get_param('version');
            
            if (empty($slug) || empty($target_version)) {
                error_log("ERROR: Missing required parameters");
                return new \WP_Error(
                    'missing_parameters',
                    'Both slug and version are required',
                    ['status' => 400]
                );
            }
            
            error_log(sprintf(
                "Processing plugin: %s, Target version: %s",
                $slug,
                $target_version
            ));

            // Include necessary files
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
            require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';

            // Get current plugin information
            $installed_plugins = get_plugins();
            $plugin_path = '';
            $was_active = false;
            $current_version = '';

            foreach ($installed_plugins as $path => $plugin_data) {
                if (strpos($path, trailingslashit($slug)) === 0) {
                    $plugin_path = $path;
                    $was_active = is_plugin_active($path);
                    $current_version = $plugin_data['Version'];
                    break;
                }
            }

            // if (empty($plugin_path)) {
            //     error_log("ERROR: Plugin not found: " . $slug);
            //     return new \WP_Error(
            //         'plugin_not_found',
            //         'Plugin not found',
            //         ['status' => 404]
            //     );
            // }

            if (empty($plugin_path)) {
                error_log("Plugin not found: " . $slug . ". Attempting to install...");
            
                // Simulate internal REST API call to install plugin
                $install_request = new \WP_REST_Request('POST', '/techops/v1/plugins/install');
                $install_request->set_param('slug', $slug);
            
                $install_response = rest_do_request($install_request);
                $install_data = $install_response->get_data();
            
                if ($install_response->is_error() || empty($install_data['success'])) {
                    error_log("ERROR: Failed to install plugin via internal API. Message: " . json_encode($install_data));
                    return new \WP_Error(
                        'install_failed',
                        'Failed to install plugin before updating',
                        ['status' => 500, 'debug' => $install_data]
                    );
                }
            
                error_log("Plugin installed successfully: " . $slug);
            
                // Refresh installed plugins data after installation
                $installed_plugins = get_plugins();
                foreach ($installed_plugins as $path => $plugin_data) {
                    if (strpos($path, trailingslashit($slug)) === 0) {
                        $plugin_path = $path;
                        $was_active = is_plugin_active($path);
                        $current_version = $plugin_data['Version'];
                        break;
                    }
                }
            
                if (empty($plugin_path)) {
                    error_log("ERROR: Plugin installed but still not found in plugin list.");
                    return new \WP_Error(
                        'plugin_missing_after_install',
                        'Plugin installed but cannot be located afterward.',
                        ['status' => 500]
                    );
                }
            }
            

            error_log("Current plugin version: " . $current_version);
            error_log("Plugin was active: " . ($was_active ? 'Yes' : 'No'));

            // Get plugin information from WordPress.org
            $api = plugins_api('plugin_information', [
                'slug' => $slug,
                'fields' => [
                    'versions' => true,
                    'sections' => false,
                    'reviews' => false,
                    'downloaded' => false,
                    'last_updated' => false,
                    'tags' => false,
                    'compatibility' => false,
                    'donate_link' => false,
                    'requires' => false,
                    'rating' => false,
                    'ratings' => false,
                    'homepage' => false,
                    'short_description' => false,
                ],
            ]);

            if (is_wp_error($api)) {
                error_log("ERROR: Failed to fetch plugin info: " . $api->get_error_message());
                return new \WP_Error(
                    'api_error',
                    'Could not fetch plugin information: ' . $api->get_error_message(),
                    ['status' => 500]
                );
            }

            // Check if we have version information
            if (!isset($api->versions) || !is_array($api->versions)) {
                error_log("WARNING: No version information available, using current version download link");
                if (!isset($api->download_link)) {
                    error_log("ERROR: No download link available");
                    return new \WP_Error(
                        'no_download_link',
                        'No download link available for the plugin',
                        ['status' => 500]
                    );
                }
                $download_url = $api->download_link;
            } else {
                error_log("Available versions: " . implode(', ', array_keys($api->versions)));

                // Check if requested version exists
                if (!isset($api->versions[$target_version])) {
                    error_log("ERROR: Requested version not found: " . $target_version);
                    return new \WP_Error(
                        'version_not_found',
                        'Requested version not found. Available versions: ' . implode(', ', array_keys($api->versions)),
                        ['status' => 400]
                    );
                }
                $download_url = $api->versions[$target_version];
            }

            // Temporarily remove problematic hooks
            error_log("Temporarily removing problematic hooks...");
            $removed_hooks = [];
            global $wp_filter;
            
            if (isset($wp_filter['upgrader_process_complete'])) {
                $removed_hooks['upgrader_process_complete'] = $wp_filter['upgrader_process_complete'];
                unset($wp_filter['upgrader_process_complete']);
            }

            // Perform the update
            error_log("Starting plugin version update...");
            try {
                error_log("Download URL: " . $download_url);

                // Create a temporary file
                $temp_file = download_url($download_url);
                if (is_wp_error($temp_file)) {
                    error_log("ERROR: Failed to download plugin package: " . $temp_file->get_error_message());
                    return new \WP_Error(
                        'download_failed',
                        'Failed to download plugin package: ' . $temp_file->get_error_message(),
                        ['status' => 500]
                    );
                }

                error_log("Temporary file created: " . $temp_file);

                // Create a temporary directory for extraction
                $temp_dir = get_temp_dir() . 'plugin-update-' . uniqid();
                if (!wp_mkdir_p($temp_dir)) {
                    error_log("ERROR: Failed to create temporary directory");
                    @unlink($temp_file);
                    return new \WP_Error(
                        'temp_dir_failed',
                        'Failed to create temporary directory',
                        ['status' => 500]
                    );
                }

                // Extract the plugin
                WP_Filesystem();
                $unzipped = unzip_file($temp_file, $temp_dir);
                if (is_wp_error($unzipped)) {
                    error_log("ERROR: Failed to unzip plugin: " . $unzipped->get_error_message());
                    @unlink($temp_file);
                    return new \WP_Error(
                        'unzip_failed',
                        'Failed to unzip plugin: ' . $unzipped->get_error_message(),
                        ['status' => 500]
                    );
                }

                // Handle the plugin directory
                $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
                $backup_dir = WP_PLUGIN_DIR . '/' . $slug . '-backup-' . uniqid();

                if (is_dir($plugin_dir)) {
                    // Deactivate the plugin if it's active
                    if ($was_active) {
                        deactivate_plugins($plugin_path);
                    }

                    // Rename the existing plugin directory
                    if (!rename($plugin_dir, $backup_dir)) {
                        error_log("ERROR: Failed to rename existing plugin directory");
                        @unlink($temp_file);
                        return new \WP_Error(
                            'rename_failed',
                            'Failed to rename existing plugin directory',
                            ['status' => 500]
                        );
                    }
                }

                // Move the new plugin
                $moved = rename($temp_dir . '/' . $slug, $plugin_dir);
                if (!$moved) {
                    error_log("ERROR: Failed to move plugin to plugins directory");
                    // Try to restore the backup
                    if (is_dir($backup_dir)) {
                        rename($backup_dir, $plugin_dir);
                    }
                    @unlink($temp_file);
                    return new \WP_Error(
                        'move_failed',
                        'Failed to move plugin to plugins directory',
                        ['status' => 500]
                    );
                }

                // Clean up temporary files
                @unlink($temp_file);
                @rmdir($temp_dir);

                error_log("Plugin package installed successfully");

                // Reactivate the plugin if it was active before
                $is_active = false;
                if ($was_active) {
                    error_log("Attempting to reactivate plugin...");
                    try {
                        activate_plugin($plugin_path);
                        $is_active = is_plugin_active($plugin_path);
                        error_log("Plugin reactivation " . ($is_active ? "successful" : "failed"));
                    } catch (\Exception $e) {
                        error_log("ERROR: Exception during plugin reactivation: " . $e->getMessage());
                    }
                }

                // Only delete the backup if everything was successful
                if ($is_active || !$was_active) {
                    error_log("Deleting backup directory: " . $backup_dir);
                    if (is_dir($backup_dir)) {
                        $this->delete_directory($backup_dir);
                    }
                } else {
                    error_log("WARNING: Keeping backup directory due to reactivation failure: " . $backup_dir);
                }

            } catch (\Exception $e) {
                error_log("ERROR: Exception during plugin update: " . $e->getMessage());
                // Try to restore from backup
                if (isset($backup_dir) && is_dir($backup_dir)) {
                    if (is_dir($plugin_dir)) {
                        $this->delete_directory($plugin_dir);
                    }
                    rename($backup_dir, $plugin_dir);
                }
                if (isset($temp_file) && file_exists($temp_file)) {
                    @unlink($temp_file);
                }
                if (isset($temp_dir) && is_dir($temp_dir)) {
                    @rmdir($temp_dir);
                }
                return new \WP_Error(
                    'update_exception',
                    'Exception during plugin update: ' . $e->getMessage(),
                    ['status' => 500]
                );
            } finally {
                // Restore removed hooks
                error_log("Restoring previously removed hooks...");
                foreach ($removed_hooks as $hook_name => $hook_data) {
                    $wp_filter[$hook_name] = $hook_data;
                }
            }

            // Get updated plugin data
            $updated_plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            $final_version = $updated_plugin_data['Version'];

            error_log(sprintf(
                "Plugin update completed successfully - Old version: %s, New version: %s",
                $current_version,
                $final_version
            ));

            $response = [
                'success' => true,
                'plugin' => [
                    'slug' => $slug,
                    'old_version' => $current_version,
                    'new_version' => $final_version,
                    'target_version' => $target_version,
                    'status' => 'updated',
                    'was_active' => $was_active,
                    'is_active' => $is_active
                ],
                'message' => sprintf(
                    'Plugin "%s" updated successfully from version %s to %s',
                    $slug,
                    $current_version,
                    $final_version
                ),
                'debug_info' => [
                    'update_check_time' => current_time('mysql'),
                    'update_source' => 'WordPress.org',
                    'download_url' => $download_url,
                    'temp_file' => $temp_file,
                    'backup_dir' => isset($backup_dir) ? $backup_dir : null
                ]
            ];

            error_log("=== TECHOPS CONTENT SYNC: PLUGIN VERSION UPDATE PROCESS COMPLETED ===\n");
            return rest_ensure_response($response);

        } catch (\Exception $e) {
            error_log("CRITICAL ERROR in plugin version update process: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new \WP_Error(
                'critical_error',
                'A critical error occurred during plugin version update: ' . $e->getMessage(),
                [
                    'status' => 500,
                    'debug_info' => [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]
            );
        }
    }

    /**
     * Helper function to recursively delete a directory
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        return rmdir($dir);
    }

    /**
     * Update a theme to a specific version
     */
    public function update_theme_to_version($request) {
        try {
            error_log("\n=== TECHOPS CONTENT SYNC: STARTING THEME VERSION UPDATE PROCESS ===");
            error_log("Request received at: " . current_time('mysql'));
            
            // Get the theme slug and target version
            $slug = $request->get_param('slug');
            $target_version = $request->get_param('version');
            
            if (empty($slug) || empty($target_version)) {
                error_log("ERROR: Missing required parameters");
                return new \WP_Error(
                    'missing_parameters',
                    'Both slug and version are required',
                    ['status' => 400]
                );
            }
            
            error_log(sprintf(
                "Processing theme: %s, Target version: %s",
                $slug,
                $target_version
            ));

            // Include necessary files
            require_once ABSPATH . 'wp-admin/includes/theme.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
            require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';

            // Get current theme information
            $theme = wp_get_theme($slug);

            if (!$theme->exists()) {
                error_log("Theme not found: " . $slug . ". Attempting installation from WordPress.org...");

                // Fetch theme information from WordPress.org
                $api = themes_api('theme_information', [
                    'slug' => $slug,
                    'fields' => [
                        'sections' => false,
                        'description' => false,
                        'tested' => false,
                        'requires' => false,
                        'rating' => false,
                        'downloaded' => false,
                        'download_link' => true
                    ]
                ]);

                if (is_wp_error($api)) {
                    error_log('ERROR: Failed to fetch theme info: ' . $api->get_error_message());
                    return new \WP_Error(
                        'api_error',
                        'Could not fetch theme information: ' . $api->get_error_message(),
                        ['status' => 500]
                    );
                }

                // Install theme
                $skin = new \WP_Ajax_Upgrader_Skin();
                $upgrader = new \Theme_Upgrader($skin);
                $install_result = $upgrader->install($api->download_link);

                if (is_wp_error($install_result)) {
                    error_log('ERROR: Theme installation failed: ' . $install_result->get_error_message());
                    return new \WP_Error(
                        'installation_failed',
                        'Theme installation failed: ' . $install_result->get_error_message(),
                        ['status' => 500]
                    );
                }

                // Re-fetch theme after installation
                $theme = wp_get_theme($slug);

                if (!$theme->exists()) {
                    error_log("ERROR: Theme installation succeeded but could not locate theme files");
                    return new \WP_Error(
                        'theme_install_failed',
                        'Theme was installed but could not be found',
                        ['status' => 500]
                    );
                }

                error_log("Theme installed successfully: " . $slug);
            }


            $current_version = $theme->get('Version');
            $was_active = (wp_get_theme()->get_stylesheet() === $slug);

            error_log("Current theme version: " . $current_version);
            error_log("Theme was active: " . ($was_active ? 'Yes' : 'No'));

            // Get theme information from WordPress.org
            $api = themes_api('theme_information', [
                'slug' => $slug,
                'fields' => [
                    'versions' => true,
                    'sections' => false,
                    'tags' => false,
                    'rating' => false,
                    'ratings' => false,
                    'downloaded' => false,
                    'last_updated' => false,
                    'homepage' => false,
                    'screenshots' => false,
                ],
            ]);

            if (is_wp_error($api)) {
                error_log("ERROR: Failed to fetch theme info: " . $api->get_error_message());
                return new \WP_Error(
                    'api_error',
                    'Could not fetch theme information: ' . $api->get_error_message(),
                    ['status' => 500]
                );
            }

            error_log("Available versions: " . implode(', ', array_keys($api->versions)));

            // Check if requested version exists
            if (!isset($api->versions[$target_version])) {
                error_log("ERROR: Requested version not found: " . $target_version);
                return new \WP_Error(
                    'version_not_found',
                    'Requested version not found. Available versions: ' . implode(', ', array_keys($api->versions)),
                    ['status' => 400]
                );
            }

            // Temporarily remove problematic hooks
            error_log("Temporarily removing problematic hooks...");
            $removed_hooks = [];
            global $wp_filter;
            
            if (isset($wp_filter['upgrader_process_complete'])) {
                $removed_hooks['upgrader_process_complete'] = $wp_filter['upgrader_process_complete'];
                unset($wp_filter['upgrader_process_complete']);
            }
            
            if (isset($wp_filter['upgrader_post_install'])) {
                $removed_hooks['upgrader_post_install'] = $wp_filter['upgrader_post_install'];
                unset($wp_filter['upgrader_post_install']);
            }

            // Perform the update
            error_log("Starting theme version update...");
            try {
                // Get the download URL for the specific version
                $download_url = $api->versions[$target_version];
                error_log("Download URL: " . $download_url);

                // Create a temporary file
                $temp_file = download_url($download_url);
                if (is_wp_error($temp_file)) {
                    error_log("ERROR: Failed to download theme package: " . $temp_file->get_error_message());
                    return new \WP_Error(
                        'download_failed',
                        'Failed to download theme package: ' . $temp_file->get_error_message(),
                        ['status' => 500]
                    );
                }

                error_log("Temporary file created: " . $temp_file);

                // Create a temporary directory for extraction
                $temp_dir = get_temp_dir() . 'theme-update-' . uniqid();
                if (!wp_mkdir_p($temp_dir)) {
                    error_log("ERROR: Failed to create temporary directory");
                    @unlink($temp_file);
                    return new \WP_Error(
                        'temp_dir_failed',
                        'Failed to create temporary directory',
                        ['status' => 500]
                    );
                }

                // Extract the theme
                WP_Filesystem();
                $unzipped = unzip_file($temp_file, $temp_dir);
                if (is_wp_error($unzipped)) {
                    error_log("ERROR: Failed to unzip theme: " . $unzipped->get_error_message());
                    @unlink($temp_file);
                    return new \WP_Error(
                        'unzip_failed',
                        'Failed to unzip theme: ' . $unzipped->get_error_message(),
                        ['status' => 500]
                    );
                }

                // Handle the theme directory
                $theme_dir = get_theme_root() . '/' . $slug;
                $backup_dir = get_theme_root() . '/' . $slug . '-backup-' . uniqid();

                if (is_dir($theme_dir)) {
                    // Rename the existing theme directory
                    if (!rename($theme_dir, $backup_dir)) {
                        error_log("ERROR: Failed to rename existing theme directory");
                        @unlink($temp_file);
                        return new \WP_Error(
                            'rename_failed',
                            'Failed to rename existing theme directory',
                            ['status' => 500]
                        );
                    }
                }

                // Move the new theme
                $moved = rename($temp_dir . '/' . $slug, $theme_dir);
                if (!$moved) {
                    error_log("ERROR: Failed to move theme to themes directory");
                    // Try to restore the backup
                    if (is_dir($backup_dir)) {
                        rename($backup_dir, $theme_dir);
                    }
                    @unlink($temp_file);
                    return new \WP_Error(
                        'move_failed',
                        'Failed to move theme to themes directory',
                        ['status' => 500]
                    );
                }

                // Clean up temporary files
                @unlink($temp_file);
                @rmdir($temp_dir);

                error_log("Theme package installed successfully");

                // Reactivate the theme if it was active before
                $is_active = false;
                if ($was_active) {
                    error_log("Attempting to reactivate theme...");
                    try {
                        switch_theme($slug);
                        $is_active = (wp_get_theme()->get_stylesheet() === $slug);
                        error_log("Theme reactivation " . ($is_active ? "successful" : "failed"));
                    } catch (\Exception $e) {
                        error_log("ERROR: Exception during theme reactivation: " . $e->getMessage());
                    }
                }

                // Only delete the backup if everything was successful
                if ($is_active || !$was_active) {
                    error_log("Deleting backup directory: " . $backup_dir);
                    if (is_dir($backup_dir)) {
                        $this->delete_directory($backup_dir);
                    }
                } else {
                    error_log("WARNING: Keeping backup directory due to reactivation failure: " . $backup_dir);
                }

            } catch (\Exception $e) {
                error_log("ERROR: Exception during theme update: " . $e->getMessage());
                // Try to restore from backup
                if (isset($backup_dir) && is_dir($backup_dir)) {
                    if (is_dir($theme_dir)) {
                        $this->delete_directory($theme_dir);
                    }
                    rename($backup_dir, $theme_dir);
                }
                if (isset($temp_file) && file_exists($temp_file)) {
                    @unlink($temp_file);
                }
                if (isset($temp_dir) && is_dir($temp_dir)) {
                    @rmdir($temp_dir);
                }
                return new \WP_Error(
                    'update_exception',
                    'Exception during theme update: ' . $e->getMessage(),
                    ['status' => 500]
                );
            } finally {
                // Restore removed hooks
                error_log("Restoring previously removed hooks...");
                foreach ($removed_hooks as $hook_name => $hook_data) {
                    $wp_filter[$hook_name] = $hook_data;
                }
            }

            // Get updated theme data
            $updated_theme = wp_get_theme($slug);
            $final_version = $updated_theme->get('Version');

            error_log(sprintf(
                "Theme update completed successfully - Old version: %s, New version: %s",
                $current_version,
                $final_version
            ));

            $response = [
                'success' => true,
                'theme' => [
                    'slug' => $slug,
                    'old_version' => $current_version,
                    'new_version' => $final_version,
                    'target_version' => $target_version,
                    'status' => 'updated',
                    'was_active' => $was_active,
                    'is_active' => $is_active
                ],
                'message' => sprintf(
                    'Theme "%s" updated successfully from version %s to %s',
                    $slug,
                    $current_version,
                    $final_version
                ),
                'debug_info' => [
                    'update_check_time' => current_time('mysql'),
                    'update_source' => 'WordPress.org',
                    'download_url' => $download_url,
                    'temp_file' => $temp_file,
                    'backup_dir' => isset($backup_dir) ? $backup_dir : null
                ]
            ];

            error_log("=== TECHOPS CONTENT SYNC: THEME VERSION UPDATE PROCESS COMPLETED ===\n");
            return rest_ensure_response($response);

        } catch (\Exception $e) {
            error_log("CRITICAL ERROR in theme version update process: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new \WP_Error(
                'critical_error',
                'A critical error occurred during theme version update: ' . $e->getMessage(),
                [
                    'status' => 500,
                    'debug_info' => [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]
            );
        }
    }

     /**
     * Install a custom plugin from a remote REST API endpoint
     */
    public function install_custom_plugin($request) {
        try {
            error_log("\n=== TECHOPS CONTENT SYNC: STARTING CUSTOM PLUGIN INSTALLATION ===");
            error_log("Request received at: " . current_time('mysql'));
            
            // Get parameters
            $source_url = $request->get_param('source_url');
            $slug = $request->get_param('slug');
            $source_auth = $request->get_param('source_auth');
            
            if (empty($source_url) || empty($slug)) {
                error_log("ERROR: Missing required parameters");
                return new \WP_Error(
                    'missing_parameters',
                    'Both source_url and slug are required',
                    ['status' => 400]
                );
            }
            
            // Construct the full download URL
            $source_url = rtrim($source_url, '/');
            $download_url = $source_url . '/wp-json/techops/v1/plugins/download/' . $slug;
            
            error_log(sprintf(
                "Processing plugin: %s, Source URL: %s, Download URL: %s",
                $slug,
                $source_url,
                $download_url
            ));

            // Include necessary files
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
            require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';

            // Check if plugin is already installed
            $installed_plugins = get_plugins();
            $plugin_path = '';
            $was_active = false;
            foreach ($installed_plugins as $path => $plugin_data) {
                if (strpos($path, trailingslashit($slug)) === 0) {
                    $plugin_path = $path;
                    $was_active = is_plugin_active($path);
                    error_log(sprintf(
                        "Plugin %s already exists at %s (Active: %s). Will be overwritten.",
                        $slug,
                        $plugin_path,
                        $was_active ? 'Yes' : 'No'
                    ));
                    break;
                }
            }

            // Download the plugin
            error_log("Downloading plugin from source...");
            $args = [
                'timeout' => 300,
                'headers' => [
                    'Accept' => 'application/zip'
                ]
            ];

            // Handle source authentication
            if (!empty($source_auth)) {
                // If source_auth doesn't start with 'Basic ', add it
                if (strpos($source_auth, 'Basic ') !== 0) {
                    $source_auth = 'Basic ' . $source_auth;
                }
                $args['headers']['Authorization'] = $source_auth;
                error_log("Using source authentication: " . $source_auth);
            }

            error_log("Making download request to: " . $download_url);
            error_log("Request headers: " . print_r($args['headers'], true));

            $response = wp_remote_get($download_url, $args);
            
            if (is_wp_error($response)) {
                error_log("ERROR: Download failed: " . $response->get_error_message());
                return new \WP_Error(
                    'download_failed',
                    'Failed to download plugin: ' . $response->get_error_message(),
                    ['status' => 500]
                );
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log("ERROR: Download failed with status code: " . $response_code);
                return new \WP_Error(
                    'download_failed',
                    'Failed to download plugin. Status code: ' . $response_code,
                    ['status' => 500]
                );
            }

            // Log response headers for debugging
            $headers = wp_remote_retrieve_headers($response);
            error_log("Response headers: " . print_r($headers, true));

            // Create temporary file
            $temp_file = wp_tempnam($slug);
            if (!$temp_file) {
                error_log("ERROR: Failed to create temporary file");
                return new \WP_Error(
                    'temp_file_failed',
                    'Failed to create temporary file',
                    ['status' => 500]
                );
            }

            // Save the downloaded content to the temporary file
            $content = wp_remote_retrieve_body($response);
            if (empty($content)) {
                error_log("ERROR: Empty response from source");
                @unlink($temp_file);
                return new \WP_Error(
                    'empty_response',
                    'Empty response from source',
                    ['status' => 500]
                );
            }

            // Log content type and length
            error_log("Content-Type: " . wp_remote_retrieve_header($response, 'content-type'));
            error_log("Content-Length: " . strlen($content));

            // Save file and verify it was written correctly
            $bytes_written = file_put_contents($temp_file, $content);
            if ($bytes_written === false) {
                error_log("ERROR: Failed to write content to temporary file");
                @unlink($temp_file);
                return new \WP_Error(
                    'write_failed',
                    'Failed to write content to temporary file',
                    ['status' => 500]
                );
            }
            error_log("Wrote " . $bytes_written . " bytes to temporary file");

            // Verify file exists and has content
            if (!file_exists($temp_file)) {
                error_log("ERROR: Temporary file not found after writing");
                return new \WP_Error(
                    'file_not_found',
                    'Temporary file not found after writing',
                    ['status' => 500]
                );
            }

            $file_size = filesize($temp_file);
            if ($file_size === 0) {
                error_log("ERROR: Temporary file is empty");
                @unlink($temp_file);
                return new \WP_Error(
                    'empty_file',
                    'Temporary file is empty',
                    ['status' => 500]
                );
            }
            error_log("Temporary file size: " . $file_size . " bytes");

            // Check ZIP file signature
            $file_content = file_get_contents($temp_file, false, null, 0, 4);
            if ($file_content === false) {
                error_log("ERROR: Failed to read ZIP signature");
                @unlink($temp_file);
                return new \WP_Error(
                    'read_failed',
                    'Failed to read ZIP signature',
                    ['status' => 500]
                );
            }

            $signature = bin2hex($file_content);
            error_log("ZIP file signature (hex): " . $signature);
            if ($signature !== '504b0304') {
                error_log("ERROR: Invalid ZIP file signature. Expected: 504b0304, Got: " . $signature);
                @unlink($temp_file);
                return new \WP_Error(
                    'invalid_zip',
                    'Invalid ZIP file signature. Expected: 504b0304, Got: ' . $signature,
                    ['status' => 500]
                );
            }

            // Verify the downloaded file is a valid ZIP
            if (!class_exists('ZipArchive')) {
                error_log("ERROR: ZipArchive class not available");
                @unlink($temp_file);
                return new \WP_Error(
                    'zip_not_available',
                    'ZipArchive class not available',
                    ['status' => 500]
                );
            }

            $zip = new \ZipArchive();
            $zip_result = $zip->open($temp_file);
            if ($zip_result !== true) {
                $error_message = 'Invalid ZIP file. Error code: ' . $zip_result;
                error_log("ERROR: " . $error_message);
                @unlink($temp_file);
                return new \WP_Error(
                    'invalid_zip',
                    $error_message,
                    ['status' => 500]
                );
            }

            // Log ZIP file contents for debugging
            $num_files = $zip->numFiles;
            error_log("Number of files in ZIP: " . $num_files);
            for ($i = 0; $i < $num_files; $i++) {
                $file_info = $zip->statIndex($i);
                error_log("File " . ($i + 1) . ": " . $file_info['name'] . " (" . $file_info['size'] . " bytes)");
            }

            $zip->close();

            // Create upgrader instance with custom skin for better error reporting
            class_exists('WP_Ajax_Upgrader_Skin') or require_once(ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php');
            class_exists('Plugin_Upgrader') or require_once(ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php');

            $skin = new \WP_Ajax_Upgrader_Skin();
            $upgrader = new \Plugin_Upgrader($skin);

            // Define arguments for the installation process, crucial for overwriting
            // $install_args = array(
            //     'overwrite_package'  => true, // This is critical for replacing an existing plugin [1, 2]
            //     'clear_update_cache' => true, // Ensures WordPress recognizes the new version immediately [1, 2]
            // );

            // Install the plugin
            error_log("Installing plugin...");
            $result = $upgrader->install($temp_file/*, $install_args*/); // Pass the arguments here

            // Clean up temporary file
            @unlink($temp_file);

            if (is_wp_error($result)) {
                error_log("ERROR: Installation failed: " . $result->get_error_message());
                return new \WP_Error(
                    'installation_failed',
                    'Plugin installation failed: ' . $result->get_error_message(),
                    ['status' => 500]
                );
            }

            if (!$result) {
                error_log("ERROR: Installation failed with unknown error");
                error_log("Upgrader skin messages: " . print_r($skin->get_upgrade_messages(), true));
                error_log("Upgrader skin errors: " . print_r($skin->get_errors(), true));
                return new \WP_Error(
                    'installation_failed',
                    'Plugin installation failed with unknown error. Check error logs for details.',
                    ['status' => 500]
                );
            }

            // Get installed plugin data
            $installed_plugins = get_plugins();
            $plugin_path = '';
            foreach ($installed_plugins as $path => $plugin_data) {
                if (strpos($path, trailingslashit($slug)) === 0) {
                    $plugin_path = $path;
                    break;
                }
            }

            if (empty($plugin_path)) {
                error_log("ERROR: Plugin installed but could not find plugin file");
                error_log("Available plugins: " . print_r(array_keys($installed_plugins), true));
                return new \WP_Error(
                    'plugin_not_found',
                    'Plugin installed but could not find plugin file',
                    ['status' => 500]
                );
            }

            // Activate the plugin
            error_log("Activating plugin...");
            $activate_result = activate_plugin($plugin_path);
            if (is_wp_error($activate_result)) {
                error_log("WARNING: Plugin installed but activation failed: " . $activate_result->get_error_message());
                // Don't return error here, just log it as the installation was successful
            }

            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            
            error_log("=== TECHOPS CONTENT SYNC: CUSTOM PLUGIN INSTALLATION COMPLETED ===\n");

            return rest_ensure_response([
                'success' => true,
                'plugin' => [
                    'slug' => $slug,
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'status' => 'installed',
                    'is_active' => is_plugin_active($plugin_path)
                ],
                'message' => sprintf(
                    'Plugin "%s" installed successfully',
                    $plugin_data['Name']
                ),
                'debug_info' => [
                    'installation_time' => current_time('mysql'),
                    'plugin_path' => $plugin_path,
                    'source_url' => $source_url
                ]
            ]);

        } catch (\Exception $e) {
            error_log("CRITICAL ERROR in custom plugin installation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new \WP_Error(
                'critical_error',
                'A critical error occurred during plugin installation: ' . $e->getMessage(),
                [
                    'status' => 500,
                    'debug_info' => [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]
            );
        }
    }

    /**
     * Install a custom theme from a remote REST API endpoint
     */
    public function install_custom_theme($request) {
        try {
            error_log("\n=== TECHOPS CONTENT SYNC: STARTING CUSTOM THEME INSTALLATION ===");
            error_log("Request received at: " . current_time('mysql'));
            
            // Get parameters
            $source_url = $request->get_param('source_url');
            $slug = $request->get_param('slug');
            $source_auth = $request->get_param('source_auth');
            
            if (empty($source_url) || empty($slug)) {
                error_log("ERROR: Missing required parameters");
                return new \WP_Error(
                    'missing_parameters',
                    'Both source_url and slug are required',
                    ['status' => 400]
                );
            }
            
            error_log(sprintf(
                "Processing theme: %s, Source URL: %s",
                $slug,
                $source_url
            ));

            // Include necessary files
            require_once ABSPATH . 'wp-admin/includes/theme.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
            require_once ABSPATH . 'wp-admin/includes/class-theme-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';

            // Check if theme is already installed
            $theme = wp_get_theme($slug);
            if ($theme->exists()) {
                error_log("ERROR: Theme already installed: " . $slug);
                return new \WP_Error(
                    'theme_exists',
                    'Theme is already installed',
                    ['status' => 400]
                );
            }

            // Download the theme
            error_log("Downloading theme from source...");
            $args = [
                'timeout' => 300,
                'headers' => []
            ];

            if (!empty($source_auth)) {
                $args['headers']['Authorization'] = $source_auth;
            }

            $response = wp_remote_get($source_url, $args);
            
            if (is_wp_error($response)) {
                error_log("ERROR: Download failed: " . $response->get_error_message());
                return new \WP_Error(
                    'download_failed',
                    'Failed to download theme: ' . $response->get_error_message(),
                    ['status' => 500]
                );
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log("ERROR: Download failed with status code: " . $response_code);
                return new \WP_Error(
                    'download_failed',
                    'Failed to download theme. Status code: ' . $response_code,
                    ['status' => 500]
                );
            }

            // Create temporary file
            $temp_file = wp_tempnam($slug);
            if (!$temp_file) {
                error_log("ERROR: Failed to create temporary file");
                return new \WP_Error(
                    'temp_file_failed',
                    'Failed to create temporary file',
                    ['status' => 500]
                );
            }

            // Save the downloaded content to the temporary file
            $content = wp_remote_retrieve_body($response);
            if (empty($content)) {
                error_log("ERROR: Empty response from source");
                @unlink($temp_file);
                return new \WP_Error(
                    'empty_response',
                    'Empty response from source',
                    ['status' => 500]
                );
            }

            file_put_contents($temp_file, $content);
            error_log("Theme downloaded successfully to: " . $temp_file);

            // Create upgrader instance
            $skin = new \WP_Ajax_Upgrader_Skin();
            $upgrader = new \Theme_Upgrader($skin);

            // Define arguments for the installation process, crucial for overwriting
            $install_args = array(
                'overwrite_package'  => true, // This is critical for replacing an existing theme
                'clear_update_cache' => true, // Ensures WordPress recognizes the new version immediately
            );

            // Install the theme
            error_log("Installing theme...");
            $result = $upgrader->install($temp_file, $install_args); // Pass the arguments here

            // Clean up temporary file
            @unlink($temp_file);

            if (is_wp_error($result)) {
                error_log("ERROR: Installation failed: " . $result->get_error_message());
                return new \WP_Error(
                    'installation_failed',
                    'Theme installation failed: ' . $result->get_error_message(),
                    ['status' => 500]
                );
            }

            if (!$result) {
                error_log("ERROR: Installation failed with unknown error");
                return new \WP_Error(
                    'installation_failed',
                    'Theme installation failed with unknown error',
                    ['status' => 500]
                );
            }

            // Get installed theme data
            $theme = wp_get_theme($slug);
            if (!$theme->exists()) {
                error_log("ERROR: Theme installed but could not find theme directory");
                return new \WP_Error(
                    'theme_not_found',
                    'Theme installed but could not find theme directory',
                    ['status' => 500]
                );
            }

            // Activate the theme
            error_log("Activating theme...");
            switch_theme($slug);
            $is_active = (wp_get_theme()->get_stylesheet() === $slug);
            
            error_log("=== TECHOPS CONTENT SYNC: CUSTOM THEME INSTALLATION COMPLETED ===\n");

            return rest_ensure_response([
                'success' => true,
                'theme' => [
                    'slug' => $slug,
                    'name' => $theme->get('Name'),
                    'version' => $theme->get('Version'),
                    'status' => 'installed',
                    'is_active' => $is_active
                ],
                'message' => sprintf(
                    'Theme "%s" installed successfully',
                    $theme->get('Name')
                ),
                'debug_info' => [
                    'installation_time' => current_time('mysql'),
                    'theme_path' => $theme->get_stylesheet_directory(),
                    'source_url' => $source_url
                ]
            ]);

        } catch (\Exception $e) {
            error_log("CRITICAL ERROR in custom theme installation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new \WP_Error(
                'critical_error',
                'A critical error occurred during theme installation: ' . $e->getMessage(),
                [
                    'status' => 500,
                    'debug_info' => [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]
            );
        }
    }

    /**
     * Get list of installed themes
     */
    public function list_themes() {
        // 1) Ensure WP's theme functions are loaded
        if (!function_exists('wp_get_themes')) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }

        // 2) Get all themes and active theme
        $all_themes = wp_get_themes();
        $active_theme = wp_get_theme();

        // 3) Load the "update_themes" transient so we know what the latest versions are
        $update_themes = get_site_transient('update_themes');
        // If WordPress hasn't checked recently, force a refresh:
        if (!isset($update_themes->checked) || !is_array($update_themes->checked)) {
            wp_version_check();
            wp_update_themes();
            $update_themes = get_site_transient('update_themes');
        }

        $formatted = [];
        foreach ($all_themes as $theme_slug => $theme) {
            // Get detailed theme data
            $theme_data = wp_get_theme($theme_slug);
            $current_version = $theme_data->get('Version');

            // Check for updates
            $has_update = false;
            $update_version = null;
            if (
                isset($update_themes->response)
                && is_array($update_themes->response)
                && isset($update_themes->response[$theme_slug])
            ) {
                $has_update = true;
                $update_version = $update_themes->response[$theme_slug]['new_version'];
            }

            $formatted[] = [
                'name'           => $theme_data->get('Name'),
                'slug'           => $theme_slug,
                'version'        => $current_version,
                'has_update'     => $has_update,
                'update_version' => $update_version,
                'active'         => ($theme_slug === $active_theme->get_stylesheet()),
                'path'           => $theme_data->get_stylesheet_directory()
            ];
        }

        return rest_ensure_response($formatted);
    }
}