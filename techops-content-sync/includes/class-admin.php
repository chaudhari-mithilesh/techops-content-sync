<?php
namespace TechOpsContentSync;

class Admin {
    /**
     * Initialize the admin class
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Register Current Site settings
        register_setting(
            'techops_current_site_settings_group', // Option group
            'techops_current_site_settings', // Option name
            [$this, 'sanitize_site_settings'] // Sanitize callback
        );

        // Register Remote Site settings
        register_setting(
            'techops_remote_site_settings_group', // Option group
            'techops_remote_site_settings', // Option name
            [$this, 'sanitize_site_settings'] // Sanitize callback
        );

        // Register GitHub settings (keep existing registration)
         register_setting(
             'techops_github_settings_group', // Option group
             'techops_github_settings', // Option name
             'sanitize_text_field' // Sanitize callback
         );

        // Note: We don't need add_settings_section or add_settings_field here
        // because we are manually rendering the form table in the partial,
        // and options.php handles saving based on the registered settings.
    }

    /**
     * Sanitize site settings and generate Base64 token.
     * This callback is used for both current and remote site settings.
     *
     * @param array $input The settings input from the form.
     * @return array The sanitized input with the added auth_token and potentially remote_url.
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

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        // Define the slugs for our pages
        $settings_page_slug = 'techops-content-sync-settings';
        $functionality_page_slug = 'techops-content-sync'; // Main slug
        $automation_page_slug = 'techops-content-sync-automation'; // Automation slug

        // Only load on our plugin pages
        if (strpos($hook, $functionality_page_slug) === false) {
             return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'techops-content-sync-admin',
            TECHOPS_CONTENT_SYNC_URL . 'admin/css/tasklist.css',
            [],
            TECHOPS_CONTENT_SYNC_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'techops-content-sync-admin',
            TECHOPS_CONTENT_SYNC_URL . 'admin/js/tasklist.js',
            ['jquery'],
            TECHOPS_CONTENT_SYNC_VERSION,
            true
        );

        // Localize script with necessary data
        wp_localize_script('techops-content-sync-admin', 'techopsContentSync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('techops_content_sync_nonce'),
            'restUrl' => rest_url('techops/v1/'),
            'restNonce' => wp_create_nonce('wp_rest')
        ]);

        // Enqueue automation workflow script
        wp_enqueue_script(
            'techops-content-sync-automation',
            TECHOPS_CONTENT_SYNC_URL . 'admin/js/automation_workflow.js',
            ['jquery'],
            TECHOPS_CONTENT_SYNC_VERSION,
            true
        );

        // Localize script with necessary data
        wp_localize_script('techops-content-sync-automation', 'techopsContentSync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('techops_content_sync_nonce'),
            'restUrl' => rest_url('techops/v1/'),
            'restNonce' => wp_create_nonce('wp_rest')
        ]);
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        // Add main menu page (Functionality)
        add_menu_page(
            'TechOps Content Sync Functionality',
            'TechOps Sync',
            'manage_options',
            'techops-content-sync',
            [$this, 'render_functionality_page'],
            'dashicons-update',
            30
        );

        // Add submenu page (Settings)
        add_submenu_page(
            'techops-content-sync', // Parent slug
            'TechOps Content Sync Settings',
            'Settings',
            'manage_options',
            'techops-content-sync-settings',
            [$this, 'render_settings_page']
        );

        // Add submenu page (Automation)
        add_submenu_page(
            'techops-content-sync', // Parent slug
            'TechOps Automation Dashboard',
            'Automation',
            'manage_options',
            'techops-content-sync-automation',
            [$this, 'render_automation_page']
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include settings page template
        include TECHOPS_CONTENT_SYNC_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * Render automation page
     */
    public function render_automation_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include automation page template
        require_once TECHOPS_CONTENT_SYNC_DIR . 'admin/partials/automation-page.php';
    }

    /**
     * Render functionality page
     */
    public function render_functionality_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include functionality page template
        include TECHOPS_CONTENT_SYNC_DIR . 'admin/partials/admin-page.php';
    }
} 