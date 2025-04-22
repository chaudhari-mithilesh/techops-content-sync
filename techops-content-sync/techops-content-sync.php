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
define('TECHOPS_CONTENT_SYNC_DEBUG', true);

// Load Composer autoloader if it exists
if (file_exists(TECHOPS_CONTENT_SYNC_DIR . 'vendor/autoload.php')) {
    require_once TECHOPS_CONTENT_SYNC_DIR . 'vendor/autoload.php';
}

/**
 * Check plugin dependencies
 */
function techops_content_sync_check_dependencies() {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = 'PHP 7.4 or higher is required.';
    }
    
    // Check ZipArchive extension
    if (!class_exists('ZipArchive')) {
        $errors[] = 'PHP ZipArchive extension is required.';
    }
    
    // Check if Composer autoload exists
    if (!file_exists(TECHOPS_CONTENT_SYNC_DIR . 'vendor/autoload.php')) {
        $errors[] = 'Composer dependencies are not installed. Please run composer install in the plugin directory.';
    }
    
    return $errors;
}

/**
 * Display admin notices for dependency issues
 */
function techops_content_sync_admin_notices() {
    $dependency_errors = techops_content_sync_check_dependencies();
    
    if (!empty($dependency_errors)) {
        ?>
        <div class="notice notice-error">
            <p><strong>TechOps Content Sync:</strong> The following requirements are not met:</p>
            <ul>
                <?php foreach ($dependency_errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}
add_action('admin_notices', 'techops_content_sync_admin_notices');

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
    // Check dependencies before initializing
    $dependency_errors = techops_content_sync_check_dependencies();
    if (!empty($dependency_errors)) {
        return;
    }
    
    // Log initialization
    error_log('TechOps Content Sync: Initializing plugin');
    
    // Load required files
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-api-endpoints.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-authentication.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-security.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-file-handler.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-git-handler.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-installer.php';
    
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
    $dependency_errors = techops_content_sync_check_dependencies();
    
    if (!empty($dependency_errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'TechOps Content Sync cannot be activated. The following requirements are not met:<br>' .
            implode('<br>', $dependency_errors)
        );
    }
    
    error_log('TechOps Content Sync: Plugin activated');
    
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $techops_dir = $upload_dir['basedir'] . '/techops-content-sync';
    
    if (!file_exists($techops_dir)) {
        wp_mkdir_p($techops_dir);
    }
    
    // Create temp directory
    $temp_dir = $techops_dir . '/temp';
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
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
            <h2>Git Repository Integration</h2>
            <form id="git-repo-form" method="post">
                <?php wp_nonce_field('techops_git_action', 'techops_git_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="repo-url">Git Repository URL</label></th>
                        <td>
                            <input type="url" id="repo-url" name="repo_url" class="regular-text" required>
                            <p class="description">Enter the URL of the Git repository</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="folder-path">Folder Path</label></th>
                        <td>
                            <input type="text" id="folder-path" name="folder_path" class="regular-text" required>
                            <p class="description">Enter the path to the folder within the repository</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Download and Install</button>
                </p>
            </form>
            <div id="status-message"></div>
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