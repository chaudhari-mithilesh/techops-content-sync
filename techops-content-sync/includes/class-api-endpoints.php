<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Endpoints Class
 * 
 * Handles all REST API routes for the sync process.
 */
class API_Endpoints {
    private $sync_manager;
    private $github_api;
    private $namespace = 'techops/v1';

    /**
     * Constructor
     * 
     * @param Sync_Manager $sync_manager Sync manager instance
     * @param GitHub_API_Handler $github_api GitHub API handler instance
     */
    public function __construct($sync_manager, $github_api) {
        $this->sync_manager = $sync_manager;
        $this->github_api = $github_api;
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // WordPress Plugin Management Endpoints
        register_rest_route('techops/v1', '/plugins/list', [
            'methods' => 'GET',
            'callback' => [$this, 'list_plugins'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/plugins/activate/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_plugin'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/plugins/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_plugin_from_body'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/plugins/deactivate/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_plugin'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/plugins/deactivate', [
            'methods' => 'POST',
            'callback' => [$this, 'deactivate_plugin_from_body'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/plugins/download/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'download_plugin'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // WordPress Theme Management Endpoints
        register_rest_route('techops/v1', '/themes/list', [
            'methods' => 'GET',
            'callback' => [$this, 'get_themes_list'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/themes/download/(?P<slug>[^/]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'download_theme'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // Git Download Endpoint
        register_rest_route('techops/v1', '/git/download', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_git_download'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // GitHub Integration Endpoints
        register_rest_route('techops/v1', '/github/repository/info', [
            'methods' => 'POST',
            'callback' => [$this, 'get_repository_info'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'repository_url' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'GitHub repository URL'
                ],
                'include_branches' => [
                    'type' => 'boolean',
                    'default' => false
                ],
                'include_tags' => [
                    'type' => 'boolean',
                    'default' => false
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/github/sync/start', [
            'methods' => 'POST',
            'callback' => [$this, 'start_sync'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'repository' => [
                    'required' => true,
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'branch' => [
                            'type' => 'string',
                            'required' => true
                        ]
                    ]
                ],
                'packages' => [
                    'required' => true,
                    'type' => 'array',
                    'items' => [
                        'type' => 'object'
                    ]
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/github/sync/status/(?P<sync_id>[^/]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sync_status'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('techops/v1', '/github/token/validate', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_token'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'token' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);

        register_rest_route('techops/v1', '/github/rate-limit', [
            'methods' => 'GET',
            'callback' => [$this, 'get_rate_limit'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }

    /**
     * Check if user has permission to access endpoints
     * 
     * @return bool Whether user has permission
     */
    public function check_permission() {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($auth_header)) {
            return false;
        }
        
        if (strpos($auth_header, 'Basic ') !== 0) {
            return false;
        }
        
        $credentials = base64_decode(substr($auth_header, 6));
        if ($credentials === false) {
            return false;
        }
        
        list($username, $password) = array_pad(explode(':', $credentials, 2), 2, '');
        
        if (empty($username) || empty($password)) {
            return false;
        }
        
        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            return false;
        }
        
        return user_can($user, 'manage_options');
    }

    /**
     * Get repository information
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function get_repository_info($request) {
        $url = $request->get_param('repository_url');
        $include_branches = $request->get_param('include_branches');
        $include_tags = $request->get_param('include_tags');

        try {
            $repo_info = $this->github_api->get_repository_info($url);
            if (is_wp_error($repo_info)) {
                return $repo_info;
            }

            $response = [
                'status' => 'success',
                'repository' => $repo_info
            ];

            if ($include_branches) {
                $branches = $this->github_api->get_branches($url);
                if (!is_wp_error($branches)) {
                    $response['repository']['branches'] = $branches;
                }
            }

            if ($include_tags) {
                $tags = $this->github_api->get_tags($url);
                if (!is_wp_error($tags)) {
                    $response['repository']['tags'] = $tags;
                }
            }

            return rest_ensure_response($response);
        } catch (\Exception $e) {
            return new \WP_Error('repository_info_failed', $e->getMessage());
        }
    }

    /**
     * Start sync process
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function start_sync($request) {
        $repository = $request->get_param('repository');
        $packages = $request->get_param('packages');

        try {
            $result = $this->sync_manager->start_sync(
                $repository['url'],
                $repository['branch'],
                $packages
            );

            if (is_wp_error($result)) {
                return $result;
            }

            return rest_ensure_response([
                'status' => 'initiated',
                'sync_id' => $result['sync_id'],
                'timestamp' => current_time('c')
            ]);
        } catch (\Exception $e) {
            return new \WP_Error('sync_failed', $e->getMessage());
        }
    }

    /**
     * Get sync status
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function get_sync_status($request) {
        $sync_id = $request->get_param('sync_id');
        $status = $this->sync_manager->get_sync_status($sync_id);

        return rest_ensure_response($status);
    }

    /**
     * Validate GitHub token
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function validate_token($request) {
        $token = $request->get_param('token');
        $result = $this->github_api->validate_token($token);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'status' => 'success',
            'scopes' => $result['scopes']
        ]);
    }

    /**
     * Get GitHub API rate limit status
     * 
     * @return WP_REST_Response|WP_Error Response object
     */
    public function get_rate_limit() {
        $rate_limit = $this->github_api->get_rate_limit();
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }
        return rest_ensure_response($rate_limit);
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
            
            $file_size = \filesize($zip_file);
            if ($file_size === false) {
                throw new \Exception('Could not determine file size');
            }
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            header('Content-Length: ' . $file_size);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');
            
            if (!\readfile($zip_file)) {
                throw new \Exception('Failed to read file');
            }
            
            if (isset($result['cleanup']) && is_callable($result['cleanup'])) {
                $result['cleanup']();
            }
            
            exit;
        } catch (\Exception $e) {
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
            
            $file_size = \filesize($zip_file);
            if ($file_size === false) {
                throw new \Exception('Could not determine file size');
            }
            
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            header('Content-Length: ' . $file_size);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');
            
            if (!\readfile($zip_file)) {
                throw new \Exception('Failed to read file');
            }
            
            if (isset($result['cleanup']) && is_callable($result['cleanup'])) {
                $result['cleanup']();
            }
            
            exit;
        } catch (\Exception $e) {
            return new \WP_Error(
                'theme_download_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Activate a plugin from request body
     */
    public function activate_plugin_from_body($request) {
        $plugin = $request->get_param('plugin');
        if (empty($plugin)) {
            return new \WP_Error(
                'missing_plugin',
                'No plugin identifier provided in request body',
                ['status' => 400]
            );
        }
        
        return $this->process_plugin_activation($plugin);
    }

    /**
     * Activate a plugin (from URL parameter)
     */
    public function activate_plugin($request) {
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            return new \WP_Error(
                'missing_slug',
                'No plugin slug provided in URL',
                ['status' => 400]
            );
        }
        
        return $this->process_plugin_activation($slug);
    }

    /**
     * Common method to process plugin activation
     */
    private function process_plugin_activation($identifier) {
        if (!function_exists('activate_plugin') || !function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $identifier = str_replace(['../', './'], '', $identifier);
        $plugins = get_plugins();

        if (isset($plugins[$identifier])) {
            $plugin_path = $identifier;
        } else {
            $plugin_path = '';
            foreach ($plugins as $path => $data) {
                if (strpos($path, $identifier . '/') === 0) {
                    $plugin_path = $path;
                    break;
                }
                $parts = explode('/', $path);
                $filename = end($parts);
                if ($filename === $identifier) {
                    $plugin_path = $path;
                    break;
                }
            }
        }

        if (empty($plugin_path)) {
            if ($identifier === 'hello.php' && isset($plugins['hello.php'])) {
                $plugin_path = 'hello.php';
            } else {
                return new \WP_Error(
                    'plugin_not_found',
                    'Plugin not found. Available plugins: ' . implode(', ', array_keys($plugins)),
                    ['status' => 404]
                );
            }
        }

        if (is_plugin_active($plugin_path)) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Plugin is already active',
                'plugin' => $plugin_path
            ]);
        }

        $result = activate_plugin($plugin_path);
        if (is_wp_error($result)) {
            return new \WP_Error(
                'plugin_activation_failed',
                $result->get_error_message(),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Plugin activated successfully',
            'plugin' => $plugin_path
        ]);
    }

    /**
     * Deactivate a plugin from request body
     */
    public function deactivate_plugin_from_body($request) {
        $plugin = $request->get_param('plugin');
        if (empty($plugin)) {
            return new \WP_Error(
                'missing_plugin',
                'No plugin identifier provided in request body',
                ['status' => 400]
            );
        }
        
        return $this->process_plugin_deactivation($plugin);
    }

    /**
     * Deactivate a plugin (from URL parameter)
     */
    public function deactivate_plugin($request) {
        $slug = $request->get_param('slug');
        if (empty($slug)) {
            return new \WP_Error(
                'missing_slug',
                'No plugin slug provided in URL',
                ['status' => 400]
            );
        }
        
        return $this->process_plugin_deactivation($slug);
    }

    /**
     * Common method to process plugin deactivation
     */
    private function process_plugin_deactivation($identifier) {
        if (!function_exists('deactivate_plugins') || !function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $identifier = str_replace(['../', './'], '', $identifier);
        $plugins = get_plugins();

        if (isset($plugins[$identifier])) {
            $plugin_path = $identifier;
        } else {
            $plugin_path = '';
            foreach ($plugins as $path => $data) {
                if (strpos($path, $identifier . '/') === 0) {
                    $plugin_path = $path;
                    break;
                }
                $parts = explode('/', $path);
                $filename = end($parts);
                if ($filename === $identifier) {
                    $plugin_path = $path;
                    break;
                }
            }
        }

        if (empty($plugin_path)) {
            if ($identifier === 'hello.php' && isset($plugins['hello.php'])) {
                $plugin_path = 'hello.php';
            } else {
                return new \WP_Error(
                    'plugin_not_found',
                    'Plugin not found. Available plugins: ' . implode(', ', array_keys($plugins)),
                    ['status' => 404]
                );
            }
        }

        if (!is_plugin_active($plugin_path)) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Plugin is already inactive',
                'plugin' => $plugin_path
            ]);
        }

        deactivate_plugins($plugin_path);
        
        if (is_plugin_active($plugin_path)) {
            return new \WP_Error(
                'plugin_deactivation_failed',
                'Failed to deactivate plugin',
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Plugin deactivated successfully',
            'plugin' => $plugin_path
        ]);
    }

    /**
     * Handle Git repository download request
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public function handle_git_download($request) {
        $repo_url = $request->get_param('repo_url');
        $folder_path = $request->get_param('folder_path');

        if (empty($repo_url) || empty($folder_path)) {
            return new \WP_Error(
                'missing_parameters',
                'Repository URL and folder path are required',
                ['status' => 400]
            );
        }

        $result = $this->github_api->handle_git_download($repo_url, $folder_path);
        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Successfully downloaded from Git repository',
            'download_path' => $result['download_path']
        ]);
    }
}