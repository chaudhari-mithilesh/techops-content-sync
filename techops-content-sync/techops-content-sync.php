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
    
    // Initialize API endpoints
    $api_endpoints = new TechOpsContentSync\API_Endpoints();
    add_action('rest_api_init', [$api_endpoints, 'register_routes']);
    
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
    
    // Get plugin status
    $api_endpoints = new TechOpsContentSync\API_Endpoints();
    $auth = new TechOpsContentSync\Authentication();
    $security = new TechOpsContentSync\Security();
    
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