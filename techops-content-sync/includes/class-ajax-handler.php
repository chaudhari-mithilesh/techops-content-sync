<?php
namespace TechOpsContentSync;

class Ajax_Handler {
    /**
     * Initialize the AJAX handler
     */
    public function __construct() {
        add_action('wp_ajax_techops_save_credentials', [$this, 'save_credentials']);
        add_action('wp_ajax_techops_get_settings', [$this, 'get_settings']);
        add_action('wp_ajax_fetch_and_compare', [$this, 'fetch_and_compare']);
        add_action('wp_ajax_analyze_dependencies', [$this, 'analyze_dependencies']);
        add_action('wp_ajax_sync_plugins_with_dependencies', [$this, 'sync_plugins_with_dependencies']);
        add_action('wp_ajax_update_plugins', [$this, 'update_plugins']);
    }

    /**
     * Save GitHub credentials
     */
    public function save_credentials() {
        // Verify nonce
        if (!check_ajax_referer('techops_content_sync_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Get and sanitize input for GitHub settings (this part is still handled via options.php form submission now)
        // This AJAX endpoint was originally for saving credentials directly, but now we use options.php
        // Keeping this for potential future use or if we switch back.
        
        // For now, this endpoint might not be strictly necessary if saving is done via options.php.
        // However, the tasklist.js currently expects an AJAX endpoint to save credentials.
        // Let's remove the saving logic here and just keep the endpoint registered in case the JS is adapted.
        // Or better yet, let's update the JS to use the new settings retrieval method.
        
        // *** Decision: Update tasklist.js to NOT use this endpoint for saving anymore. ***
        // The saving is handled by WordPress's options.php form submission now.
        // This AJAX endpoint will be removed or refactored later if needed.
        
         wp_send_json_error('This endpoint is deprecated.'); // Indicate this endpoint is no longer the primary save method
    }

     /**
      * Get saved settings (remote URL, auth tokens)
      */
    /**
     * Fetch and compare plugins/themes
     */
    public function fetch_and_compare() {
        // Verify nonce
        if (!check_ajax_referer('techops_content_sync_nonce', 'nonce', false)) {
            wp_send_json_error(['success' => false, 'message' => 'Invalid nonce']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['success' => false, 'message' => 'Insufficient permissions']);
            return;
        }

        try {
            // Get current site plugins/themes
            $current_plugins = get_plugins();
            $current_themes = wp_get_themes();

            // Get remote site data
            $remote_site_settings = get_option('techops_remote_site_settings');
            $remote_url = $remote_site_settings['remote_url'];

            // TODO: Implement actual remote site data fetching
            $remote_plugins = []; // Placeholder
            $remote_themes = [];  // Placeholder

            // Compare data
            $comparison = [
                'plugins' => $this->compare_plugins($current_plugins, $remote_plugins),
                'themes' => $this->compare_themes($current_themes, $remote_themes)
            ];

            wp_send_json_success(['success' => true, 'comparison' => $comparison]);
        } catch (Exception $e) {
            wp_send_json_error(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Analyze plugin dependencies
     */
    public function analyze_dependencies() {
        // Verify nonce
        if (!check_ajax_referer('techops_content_sync_nonce', 'nonce', false)) {
            wp_send_json_error(['success' => false, 'message' => 'Invalid nonce']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['success' => false, 'message' => 'Insufficient permissions']);
            return;
        }

        try {
            $comparison = $_POST['comparison'];
            $dependencies = $this->build_dependency_tree($comparison);
            wp_send_json_success(['success' => true, 'dependencies' => $dependencies]);
        } catch (Exception $e) {
            wp_send_json_error(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Sync plugins with dependency awareness
     */
    public function sync_plugins_with_dependencies() {
        // Verify nonce
        if (!check_ajax_referer('techops_content_sync_nonce', 'nonce', false)) {
            wp_send_json_error(['success' => false, 'message' => 'Invalid nonce']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['success' => false, 'message' => 'Insufficient permissions']);
            return;
        }

        try {
            $dependencies = $_POST['dependencies'];
            $results = $this->process_dependencies($dependencies);
            wp_send_json_success(['success' => true, 'results' => $results]);
        } catch (Exception $e) {
            wp_send_json_error(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update plugins
     */
    public function update_plugins() {
        // Verify nonce
        if (!check_ajax_referer('techops_content_sync_nonce', 'nonce', false)) {
            wp_send_json_error(['success' => false, 'message' => 'Invalid nonce']);
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['success' => false, 'message' => 'Insufficient permissions']);
            return;
        }

        try {
            // Verify staging site
            if (!$this->is_staging_site()) {
                throw new Exception('Plugin updates can only be performed on staging sites');
            }

            // Get plugins that need updates
            $plugins = get_plugins();
            $updates = $this->get_plugin_updates($plugins);

            // Process updates in dependency order
            $results = $this->process_plugin_updates($updates);
            wp_send_json_success(['success' => true, 'results' => $results]);
        } catch (Exception $e) {
            wp_send_json_error(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Helper functions
     */


    private function compare_plugins($current, $remote) {
        $comparison = [];
        foreach ($current as $slug => $plugin) {
            $remote_plugin = isset($remote[$slug]) ? $remote[$slug] : null;
            $comparison[] = [
                'slug' => $slug,
                'name' => $plugin['Name'],
                'current_version' => $plugin['Version'],
                'remote_version' => $remote_plugin ? $remote_plugin['Version'] : null,
                'is_active' => is_plugin_active($slug),
                'requires' => isset($plugin['Requires']) ? $plugin['Requires'] : []
            ];
        }
        return $comparison;
    }

    private function compare_themes($current, $remote) {
        $comparison = [];
        foreach ($current as $theme) {
            $remote_theme = isset($remote[$theme->get_stylesheet()]) ? $remote[$theme->get_stylesheet()] : null;
            $comparison[] = [
                'stylesheet' => $theme->get_stylesheet(),
                'name' => $theme->get('Name'),
                'current_version' => $theme->get('Version'),
                'remote_version' => $remote_theme ? $remote_theme['Version'] : null,
                'is_active' => $theme->get_stylesheet() === get_stylesheet()
            ];
        }
        return $comparison;
    }

    private function build_dependency_tree($comparison) {
        $dependencies = [];
        foreach ($comparison['plugins'] as $plugin) {
            $dependencies[$plugin['slug']] = [
                'name' => $plugin['name'],
                'version' => $plugin['current_version'],
                'dependencies' => $this->get_plugin_dependencies($plugin['slug'])
            ];
        }
        
        return $this->resolve_dependency_order($dependencies);
    }

    private function get_plugin_dependencies($plugin_slug) {
        $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . $plugin_slug . '.php';
        if (!file_exists($plugin_file)) {
            return [];
        }

        $plugin_data = get_plugin_data($plugin_file);
        $requires = [];
        
        // Check for Requires field
        if (!empty($plugin_data['Requires'])) {
            $requires = explode(',', $plugin_data['Requires']);
            $requires = array_map('trim', $requires);
        }
        
        // Check for Requires at least
        if (!empty($plugin_data['Requires at least'])) {
            $requires[] = 'wordpress ' . $plugin_data['Requires at least'];
        }
        
        return $requires;
    }

    private function resolve_dependency_order($dependencies) {
        $sorted = [];
        $visited = [];
        $tempMark = [];

        foreach ($dependencies as $plugin => $dep) {
            if (!isset($visited[$plugin])) {
                $this->visit($plugin, $dependencies, $visited, $tempMark, $sorted);
            }
        }

        return array_reverse($sorted);
    }

    private function visit($plugin, &$dependencies, &$visited, &$tempMark, &$sorted) {
        if (isset($tempMark[$plugin])) {
            throw new Exception("Circular dependency detected for plugin: $plugin");
        }

        if (!isset($visited[$plugin])) {
            $tempMark[$plugin] = true;
            
            foreach ($dependencies[$plugin]['dependencies'] as $dep) {
                $dep_slug = $this->get_plugin_slug_from_requirement($dep);
                if ($dep_slug && isset($dependencies[$dep_slug])) {
                    $this->visit($dep_slug, $dependencies, $visited, $tempMark, $sorted);
                }
            }

            $visited[$plugin] = true;
            unset($tempMark[$plugin]);
            $sorted[] = $dependencies[$plugin];
        }
    }

    private function get_plugin_slug_from_requirement($requirement) {
        // Extract plugin slug from requirement string
        $requirement = strtolower($requirement);
        if (strpos($requirement, 'wordpress') !== false) {
            return null; // Skip WordPress version requirements
        }
        return $requirement;
    }

    private function process_dependencies($dependencies) {
        $results = [];
        foreach ($dependencies as $plugin) {
            try {
                $current_site_auth = get_option('techops_current_site_settings')['auth_token'];
                $remote_site_auth = get_option('techops_remote_site_settings')['auth_token'];

                if (!$current_site_auth || !$remote_site_auth) {
                    throw new Exception('Authentication tokens not set');
                }

                // Process plugin based on its state
                if (!$plugin['is_active']) {
                    // Activate plugin
                    $response = wp_remote_post(
                        techopsContentSync.restUrl . 'plugins/activate',
                        [
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'X-WP-Nonce' => techopsContentSync.restNonce,
                                'Authorization' => 'Basic ' . $current_site_auth
                            ],
                            'body' => json_encode(['slug' => $plugin['slug']])
                        ]
                    );
                } else {
                    // Update plugin if needed
                    $response = wp_remote_post(
                        techopsContentSync.restUrl . 'plugins/update-version',
                        [
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'X-WP-Nonce' => techopsContentSync.restNonce,
                                'Authorization' => 'Basic ' . $current_site_auth
                            ],
                            'body' => json_encode([
                                'slug' => $plugin['slug'],
                                'version' => $plugin['remote_version']
                            ])
                        ]
                    );
                }

                $result = json_decode(wp_remote_retrieve_body($response), true);
                $results[] = [
                    'plugin' => $plugin['name'],
                    'status' => $response['response']['code'] === 200 ? 'success' : 'error',
                    'message' => $result['message'] ?? 'Operation completed'
                ];
            } catch (Exception $e) {
                $results[] = [
                    'plugin' => $plugin['name'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        return $results;
    }

    private function is_staging_site() {
        // Check if this is a staging site
        // You might want to implement this based on your staging site detection logic
        // For now, let's assume any site that's not production is staging
        $site_url = get_site_url();
        return strpos($site_url, 'staging.') !== false || 
               strpos($site_url, '-staging.') !== false ||
               strpos($site_url, '-dev.') !== false;
    }

    private function get_plugin_updates($plugins) {
        $updates = [];
        foreach ($plugins as $plugin) {
            if ($plugin['update_available']) {
                $updates[] = $plugin;
            }
        }
        return $updates;
    }

    private function process_plugin_updates($updates) {
        $results = [];
        foreach ($updates as $plugin) {
            try {
                $current_site_auth = get_option('techops_current_site_settings')['auth_token'];
                
                if (!$current_site_auth) {
                    throw new Exception('Authentication token not set');
                }

                $response = wp_remote_post(
                    techopsContentSync.restUrl . 'plugins/update-version',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'X-WP-Nonce' => techopsContentSync.restNonce,
                            'Authorization' => 'Basic ' . $current_site_auth
                        ],
                        'body' => json_encode([
                            'slug' => $plugin['slug'],
                            'version' => $plugin['latest_version']
                        ])
                    ]
                );

                $result = json_decode(wp_remote_retrieve_body($response), true);
                $results[] = [
                    'plugin' => $plugin['name'],
                    'status' => $response['response']['code'] === 200 ? 'success' : 'error',
                    'message' => $result['message'] ?? 'Update completed'
                ];
            } catch (Exception $e) {
                $results[] = [
                    'plugin' => $plugin['name'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        return $results;
    }

     public function get_settings() {
         // Verify nonce (using the REST API nonce for frontend requests)
         // This might need adjustment based on how the nonce is passed from JS
         // Let's use the general admin nonce for this AJAX call.
         if (!check_ajax_referer('techops_content_sync_nonce', 'nonce', false)) {
             wp_send_json_error(['success' => false, 'message' => 'Invalid nonce']);
             return;
         }

         // Check user capabilities
         if (!current_user_can('manage_options')) {
             wp_send_json_error(['success' => false, 'message' => 'Insufficient permissions']);
             return;
         }

         $current_site_settings = get_option('techops_current_site_settings', [
             'username' => '',
             'app_password' => '',
             'auth_token' => ''
         ]);

         $remote_site_settings = get_option('techops_remote_site_settings', [
             'username' => '',
             'app_password' => '',
             'auth_token' => '',
             'remote_url' => ''
         ]);

         wp_send_json_success([
             'current_site_auth' => $current_site_settings['auth_token'] ?? '',
             'remote_site_auth' => $remote_site_settings['auth_token'] ?? '',
             'remote_site_url' => $remote_site_settings['remote_url'] ?? ''
         ]);
     }

    /**
     * Sanitize site settings and generate Base64 token.
     * This callback is used for both current and remote site settings.
     *
     * @param array $input The settings input from the form.
     * @return array The sanitized input with the added auth_token.
     */
    public function sanitize_site_settings($input) {
        $sanitized_input = [];
        $option_name = $this->get_current_option_name();
        $current_settings = get_option($option_name, []);

        // Sanitize username and password
        $sanitized_input['username'] = sanitize_text_field($input['username'] ?? '');
        $sanitized_input['app_password'] = sanitize_text_field($input['app_password'] ?? '');

        // Generate and store Base64 auth token if both username and password are provided
        if (!empty($sanitized_input['username']) && !empty($sanitized_input['app_password'])) {
            $sanitized_input['auth_token'] = base64_encode($sanitized_input['username'] . ':' . $sanitized_input['app_password']);
        } else {
            // If username or password is empty, keep the existing token
            $sanitized_input['auth_token'] = $current_settings['auth_token'] ?? '';
        }

        // If this is remote site settings, also handle the remote URL
        if ($option_name === 'techops_remote_site_settings') {
            // Sanitize the remote URL
            $sanitized_input['remote_url'] = esc_url_raw($input['remote_url'] ?? '');
            
            // If the URL is empty in the input but exists in current settings, keep the existing URL
            if (empty($sanitized_input['remote_url']) && !empty($current_settings['remote_url'])) {
                $sanitized_input['remote_url'] = $current_settings['remote_url'];
            }
        }

        return $sanitized_input;
    }
    
     // Helper to determine which option is currently being saved
     private function get_current_option_name() {
         // This is a bit of a hack, but needed because the same sanitize callback
         // is used for two different settings registered via register_setting.
         // The $_POST data will contain the name of the option being saved.
         if (isset($_POST['option_page']) && $_POST['option_page'] === 'techops_current_site_settings_group') {
             return 'techops_current_site_settings';
         } elseif (isset($_POST['option_page']) && $_POST['option_page'] === 'techops_remote_site_settings_group') {
             return 'techops_remote_site_settings';
         }
         // Fallback or handle unexpected cases
         return '';
     }
} 