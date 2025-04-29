<?php
/**
 * Plugin Name: TechOps Content Sync
 * Plugin URI: https://example.com/techops-content-sync
 * Description: Syncs WordPress plugins and themes with a Git repository
 * Version: 1.0.0
 * Author: TechOps
 * Author URI: https://example.com
 * Text Domain: techops-content-sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('TECHOPS_CONTENT_SYNC_VERSION', '1.0.0');
define('TECHOPS_CONTENT_SYNC_DIR', plugin_dir_path(__FILE__));
define('TECHOPS_CONTENT_SYNC_URL', plugin_dir_url(__FILE__));
define('TECHOPS_CONTENT_SYNC_DEBUG', true); // Enable debugging by default

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    // Check if the class belongs to our namespace
    if (strpos($class, 'TechOpsContentSync\\') !== 0) {
        return;
    }

    // Remove namespace prefix
    $class = str_replace('TechOpsContentSync\\', '', $class);
    
    // Convert class name to file path
    $file = TECHOPS_CONTENT_SYNC_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Initialize the plugin
 */
function techops_content_sync_init() {
    // Log initialization
    error_log('TechOps Content Sync: Initializing plugin');
    
    // Load required files
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-api-endpoints.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-authentication.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-security.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-file-handler.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-github-handler.php';
    
    // Initialize API endpoints
    $api_endpoints = new TechOpsContentSync\API_Endpoints();
    add_action('rest_api_init', [$api_endpoints, 'register_routes']);
    
    // Initialize GitHub handler
    $github_handler = new TechOpsContentSync\GitHub_Handler();
    
    // Log API init hook
    error_log('TechOps Content Sync: Added REST API init hook');
}
add_action('init', 'techops_content_sync_init');

/**
 * Activation hook
 */
function techops_content_sync_activate() {
    error_log('TechOps Content Sync: Plugin activated');
    
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $techops_dir = $upload_dir['basedir'] . '/techops-content-sync';
    
    if (!file_exists($techops_dir)) {
        wp_mkdir_p($techops_dir);
    }
    
    // Create git-content directory
    $git_content_dir = TECHOPS_CONTENT_SYNC_DIR . 'git-content';
    if (!file_exists($git_content_dir)) {
        wp_mkdir_p($git_content_dir);
    }
    
    // Create log file
    $log_file = $techops_dir . '/techops-content-sync.log';
    if (!file_exists($log_file)) {
        file_put_contents($log_file, 'TechOps Content Sync Log - ' . date('Y-m-d H:i:s') . "\n");
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'techops_content_sync_activate');

/**
 * Deactivation hook
 */
function techops_content_sync_deactivate() {
    error_log('TechOps Content Sync: Plugin deactivated');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'techops_content_sync_deactivate');

/**
 * Add admin menu
 */
function techops_content_sync_admin_menu() {
    add_menu_page(
        'TechOps Content Sync',
        'TechOps Sync',
        'manage_options',
        'techops-content-sync',
        'techops_content_sync_admin_page',
        'dashicons-update',
        30
    );
}
add_action('admin_menu', 'techops_content_sync_admin_menu');

/**
 * Admin page callback
 */
function techops_content_sync_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submissions
    if (isset($_POST['techops_github_settings']) && check_admin_referer('techops_github_settings_update')) {
        $settings = array(
            'github_username' => sanitize_text_field($_POST['github_username']),
            'github_repo' => sanitize_text_field($_POST['github_repo']),
            'github_token' => sanitize_text_field($_POST['github_token']),
            'github_file_path' => sanitize_text_field($_POST['github_file_path']),
            'github_download_path' => sanitize_text_field($_POST['github_download_path'])
        );
        
        update_option('techops_github_settings', $settings);
        add_settings_error('techops_github_settings', 'settings_updated', 'GitHub settings updated successfully.', 'updated');
    }
    
    // Handle file download
    if (isset($_POST['download_github_file']) && check_admin_referer('techops_download_file')) {
        $github_handler = new TechOpsContentSync\GitHub_Handler();
        $result = $github_handler->download_file();
        
        if (is_wp_error($result)) {
            add_settings_error('techops_github_settings', 'download_error', 'Error downloading file: ' . $result->get_error_message(), 'error');
        } else {
            add_settings_error('techops_github_settings', 'download_success', 'File downloaded successfully to: ' . esc_html($result), 'updated');
        }
    }
    
    // Get current settings
    $settings = get_option('techops_github_settings', array(
        'github_username' => '',
        'github_repo' => '',
        'github_token' => '',
        'github_file_path' => '',
        'github_download_path' => 'git-content/'
    ));
    
    // Get plugin status
    $api_endpoints = new TechOpsContentSync\API_Endpoints();
    $auth = new TechOpsContentSync\Authentication();
    $security = new TechOpsContentSync\Security();
    
    // Display settings errors
    settings_errors('techops_github_settings');
    
    // Display admin page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="card">
            <h2>Plugin Status</h2>
            <p>Version: <?php echo TECHOPS_CONTENT_SYNC_VERSION; ?></p>
            <p>Debug Mode: <?php echo TECHOPS_CONTENT_SYNC_DEBUG ? 'Enabled' : 'Disabled'; ?></p>
            <p>Plugin Directory: <?php echo TECHOPS_CONTENT_SYNC_DIR; ?></p>
            <p>Plugin URL: <?php echo TECHOPS_CONTENT_SYNC_URL; ?></p>
        </div>
        
        <div class="card">
            <h2>GitHub Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field('techops_github_settings_update'); ?>
                <input type="hidden" name="techops_github_settings" value="1">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="github_username">GitHub Username</label></th>
                        <td>
                            <input type="text" id="github_username" name="github_username" 
                                   value="<?php echo esc_attr($settings['github_username']); ?>" class="regular-text">
                            <p class="description">Your GitHub username or organization name.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="github_repo">Repository Name</label></th>
                        <td>
                            <input type="text" id="github_repo" name="github_repo" 
                                   value="<?php echo esc_attr($settings['github_repo']); ?>" class="regular-text">
                            <p class="description">The name of the GitHub repository.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="github_token">Personal Access Token</label></th>
                        <td>
                            <input type="password" id="github_token" name="github_token" 
                                   value="<?php echo esc_attr($settings['github_token']); ?>" class="regular-text">
                            <p class="description">Your GitHub personal access token for accessing private repositories.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="github_file_path">Default File Path</label></th>
                        <td>
                            <input type="text" id="github_file_path" name="github_file_path" 
                                   value="<?php echo esc_attr($settings['github_file_path']); ?>" class="regular-text">
                            <p class="description">Default path to the file in the repository (e.g., config/settings.json).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="github_download_path">Download Directory</label></th>
                        <td>
                            <input type="text" id="github_download_path" name="github_download_path" 
                                   value="<?php echo esc_attr($settings['github_download_path']); ?>" class="regular-text">
                            <p class="description">Directory where downloaded files will be stored (relative to plugin directory).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save GitHub Settings'); ?>
            </form>
            
            <hr>
            
            <h3>Download File</h3>
            <form method="post" action="">
                <?php wp_nonce_field('techops_download_file'); ?>
                <input type="hidden" name="download_github_file" value="1">
                <p>
                    <input type="submit" class="button button-primary" value="Download File">
                    <span class="description">Downloads the configured file from GitHub.</span>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>REST API Status</h2>
            <p>API Base URL: <?php echo esc_url(rest_url('techops/v1')); ?></p>
            <p>Available Endpoints:</p>
            <ul>
                <li>GET /plugins/list - List all plugins</li>
                <li>POST /plugins/activate/{slug} - Activate a plugin</li>
                <li>POST /plugins/deactivate/{slug} - Deactivate a plugin</li>
                <li>GET /plugins/download/{slug} - Download a plugin</li>
                <li>GET /themes/list - List all themes</li>
                <li>POST /themes/activate/{slug} - Activate a theme</li>
                <li>POST /themes/deactivate/{slug} - Deactivate a theme</li>
                <li>GET /themes/download/{slug} - Download a theme</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Authentication</h2>
            <p>Authentication Method: Basic Auth</p>
            <p>Required Capability: manage_options</p>
            <?php if (TECHOPS_CONTENT_SYNC_DEBUG): ?>
                <p>Debug Token: d2lzZG06czF6WiBDSWxJIDF2QmMgOWU1biBZUk1MIDFrU0w=</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Logs</h2>
            <?php
            $upload_dir = wp_upload_dir();
            $log_file = $upload_dir['basedir'] . '/techops-content-sync/techops-content-sync.log';
            
            if (file_exists($log_file)) {
                $logs = file_get_contents($log_file);
                echo '<pre>' . esc_html($logs) . '</pre>';
            } else {
                echo '<p>No logs found.</p>';
            }
            ?>
        </div>
    </div>
    <?php
} 