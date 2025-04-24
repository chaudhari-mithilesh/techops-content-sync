<?php
namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Handler Class
 * 
 * Handles plugin settings and token management.
 */
class Settings {
    /**
     * Option names
     */
    const GITHUB_TOKEN_OPTION = 'techops_github_token';
    const WP_AUTH_OPTION = 'techops_wp_auth';
    const SYNC_PREFERENCES = 'techops_sync_preferences';

    private $option_name = 'techops_github_token';
    private $preferences_option_name = 'techops_sync_preferences';
    private $page_slug = 'techops-content-sync-settings';
    private $parent_slug = 'techops-content-sync';
    private $capability = 'manage_options';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting(
            $this->parent_slug, // Match with settings_fields() in the form
            $this->option_name,
            array($this, 'sanitize_token')
        );
        
        register_setting(
            $this->parent_slug,
            $this->preferences_option_name,
            array($this, 'sanitize_preferences')
        );
        
        // Add settings section
        add_settings_section(
            'techops_content_sync_main',
            __('GitHub Integration Settings', 'techops-content-sync'),
            array($this, 'render_section_info'),
            $this->page_slug
        );
        
        // Add settings fields
        add_settings_field(
            'github_token',
            __('GitHub Personal Access Token', 'techops-content-sync'),
            array($this, 'render_token_field'),
            $this->page_slug,
            'techops_content_sync_main'
        );
        
        add_settings_field(
            'auto_activate',
            __('Auto Activate', 'techops-content-sync'),
            array($this, 'render_auto_activate_field'),
            $this->page_slug,
            'techops_content_sync_main'
        );
        
        add_settings_field(
            'backup_before_sync',
            __('Backup Before Sync', 'techops-content-sync'),
            array($this, 'render_backup_field'),
            $this->page_slug,
            'techops_content_sync_main'
        );
        
        add_settings_field(
            'sync_interval',
            __('Sync Interval', 'techops-content-sync'),
            array($this, 'render_interval_field'),
            $this->page_slug,
            'techops_content_sync_main'
        );
    }

    public function add_settings_page() {
        add_submenu_page(
            $this->parent_slug,
            __('TechOps Content Sync Settings', 'techops-content-sync'),
            __('Settings', 'techops-content-sync'),
            $this->capability,
            $this->page_slug,
            array($this, 'render_settings_page')
        );
    }

    public function render_section_info() {
        echo '<p>' . __('Configure your GitHub integration settings below.', 'techops-content-sync') . '</p>';
    }

    public function render_settings_page() {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Show error/update messages
        settings_errors('techops_content_sync_messages');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->parent_slug);
                do_settings_sections($this->page_slug);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_token_field() {
        $token = $this->get_github_token();
        ?>
        <input type="password" 
               id="github_token" 
               name="<?php echo esc_attr($this->option_name); ?>" 
               value="<?php echo esc_attr($token); ?>" 
               class="regular-text"
        />
        <p class="description">
            <?php _e('Enter your GitHub Personal Access Token. This token will be used to authenticate with the GitHub API.', 'techops-content-sync'); ?>
            <a href="https://github.com/settings/tokens" target="_blank"><?php _e('Generate a new token', 'techops-content-sync'); ?></a>
        </p>
        <?php
    }

    public function render_auto_activate_field() {
        $preferences = $this->get_preferences();
        ?>
        <input type="checkbox" 
               id="auto_activate" 
               name="<?php echo esc_attr($this->preferences_option_name); ?>[auto_activate]" 
               value="1" 
               <?php checked(1, $preferences['auto_activate']); ?>
        />
        <p class="description">
            <?php _e('Automatically activate plugins and themes after installation.', 'techops-content-sync'); ?>
        </p>
        <?php
    }

    public function render_backup_field() {
        $preferences = $this->get_preferences();
        ?>
        <input type="checkbox" 
               id="backup_before_sync" 
               name="<?php echo esc_attr($this->preferences_option_name); ?>[backup_before_sync]" 
               value="1" 
               <?php checked(1, $preferences['backup_before_sync']); ?>
        />
        <p class="description">
            <?php _e('Create a backup before syncing plugins and themes.', 'techops-content-sync'); ?>
        </p>
        <?php
    }

    public function render_interval_field() {
        $preferences = $this->get_preferences();
        ?>
        <select id="sync_interval" 
                name="<?php echo esc_attr($this->preferences_option_name); ?>[sync_interval]">
            <option value="hourly" <?php selected('hourly', $preferences['sync_interval']); ?>>
                <?php _e('Hourly', 'techops-content-sync'); ?>
            </option>
            <option value="twicedaily" <?php selected('twicedaily', $preferences['sync_interval']); ?>>
                <?php _e('Twice Daily', 'techops-content-sync'); ?>
            </option>
            <option value="daily" <?php selected('daily', $preferences['sync_interval']); ?>>
                <?php _e('Daily', 'techops-content-sync'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('How often to check for updates.', 'techops-content-sync'); ?>
        </p>
        <?php
    }

    public function sanitize_token($input) {
        $input = trim($input);
        
        if (empty($input)) {
            return '';
        }
        
        // Test if the token works with GitHub API
        $response = wp_remote_get('https://api.github.com/user', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $input,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/TechOps-Content-Sync'
            )
        ));
        
        if (is_wp_error($response)) {
            add_settings_error(
                'techops_content_sync_messages',
                'invalid_token',
                __('Could not validate GitHub token: ' . $response->get_error_message(), 'techops-content-sync')
            );
            return get_option($this->option_name);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            add_settings_error(
                'techops_content_sync_messages',
                'invalid_token',
                __('Invalid GitHub token. Please check your token and try again.', 'techops-content-sync')
            );
            return get_option($this->option_name);
        }
        
        return $input;
    }
    
    public function sanitize_preferences($input) {
        $sanitized = array();
        
        $sanitized['auto_activate'] = isset($input['auto_activate']) ? 1 : 0;
        $sanitized['backup_before_sync'] = isset($input['backup_before_sync']) ? 1 : 0;
        
        $valid_intervals = array('hourly', 'twicedaily', 'daily');
        $sanitized['sync_interval'] = in_array($input['sync_interval'], $valid_intervals) 
            ? $input['sync_interval'] 
            : 'hourly';
        
        return $sanitized;
    }

    /**
     * Get GitHub token
     */
    public function get_github_token() {
        return get_option($this->option_name, '');
    }

    /**
     * Get sync preferences
     */
    public function get_preferences() {
        return get_option($this->preferences_option_name, array(
            'auto_activate' => 1,
            'backup_before_sync' => 1,
            'sync_interval' => 'hourly'
        ));
    }

    /**
     * Encrypt token before saving
     */
    public function encrypt_token($token) {
        if (empty($token)) {
            return '';
        }

        return wp_encrypt_password($token);
    }

    /**
     * Encrypt WordPress auth
     */
    public function encrypt_auth($auth) {
        if (empty($auth)) {
            return '';
        }

        return wp_encrypt_password($auth);
    }

    /**
     * Get WordPress auth
     */
    public function get_wp_auth() {
        $auth_data = get_option(self::WP_AUTH_OPTION);
        if (empty($auth_data)) {
            return '';
        }

        return $auth_data['value'] ?? '';
    }

    /**
     * Update GitHub token
     */
    public function update_github_token($token) {
        if (empty($token)) {
            return false;
        }

        return update_option(self::GITHUB_TOKEN_OPTION, [
            'value' => $this->encrypt_token($token),
            'created' => current_time('mysql'),
            'last_used' => current_time('mysql')
        ]);
    }

    /**
     * Update WordPress auth
     */
    public function update_wp_auth($username, $password) {
        if (empty($username) || empty($password)) {
            return false;
        }

        $auth_string = base64_encode($username . ':' . $password);
        return update_option(self::WP_AUTH_OPTION, [
            'value' => $this->encrypt_auth($auth_string),
            'username' => $username,
            'created' => current_time('mysql')
        ]);
    }

    /**
     * Test GitHub token
     */
    public function test_github_token($token) {
        $response = wp_remote_get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3+json'
            ]
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('token_test_failed', $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new \WP_Error(
                'token_invalid',
                'GitHub token validation failed. Status: ' . $status_code
            );
        }

        return true;
    }

    /**
     * Update sync preferences
     */
    public function update_sync_preferences($preferences) {
        return update_option(self::SYNC_PREFERENCES, array_merge(
            $this->get_preferences(),
            $preferences
        ));
    }
} 