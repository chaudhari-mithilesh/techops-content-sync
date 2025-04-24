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
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-github-api-handler.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-settings.php';
    require_once TECHOPS_CONTENT_SYNC_DIR . 'includes/class-sync-history.php';
    
    // Initialize Settings
    new TechOpsContentSync\Settings();
    
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
 * Admin page callback
 */
function techops_content_sync_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap techops-content-sync-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
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
            
            <!-- Status Messages Container -->
            <div id="status-message"></div>
            
            <!-- Operation Log Container -->
            <div id="operation-log" class="operation-log-container" style="display: none;">
                <h3>Operation Log</h3>
                <div class="log-entries"></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Recent Operations</h2>
            <div id="recent-operations">
                <?php
                // Get recent operations from the database
                global $wpdb;
                $table_name = $wpdb->prefix . 'techops_sync_history';
                $recent_ops = $wpdb->get_results(
                    "SELECT * FROM {$table_name} ORDER BY started_at DESC LIMIT 10"
                );
                
                if ($recent_ops) {
                    echo '<table class="widefat">';
                    echo '<thead><tr>';
                    echo '<th>Repository</th>';
                    echo '<th>Status</th>';
                    echo '<th>Started</th>';
                    echo '<th>Completed</th>';
                    echo '<th>Result</th>';
                    echo '</tr></thead><tbody>';
                    
                    foreach ($recent_ops as $op) {
                        $status_class = '';
                        switch ($op->status) {
                            case 'completed':
                                $status_class = 'success';
                                break;
                            case 'failed':
                                $status_class = 'error';
                                break;
                            default:
                                $status_class = 'info';
                        }
                        
                        echo "<tr class='status-{$status_class}'>";
                        echo "<td>" . esc_html($op->repository_url) . "</td>";
                        echo "<td>" . esc_html(ucfirst($op->status)) . "</td>";
                        echo "<td>" . esc_html($op->started_at) . "</td>";
                        echo "<td>" . esc_html($op->completed_at ?: '-') . "</td>";
                        echo "<td>" . esc_html($op->error_message ?: 'Success') . "</td>";
                        echo "</tr>";
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>No recent operations found.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

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
 * Enqueue admin scripts and styles
 */
function techops_content_sync_admin_enqueue_scripts($hook) {
    // Only enqueue on our plugin pages
    if (strpos($hook, 'techops-content-sync') === false) {
        return;
    }
    
    wp_enqueue_style(
        'techops-content-sync-admin',
        TECHOPS_CONTENT_SYNC_URL . 'assets/css/admin.css',
        array(),
        TECHOPS_CONTENT_SYNC_VERSION
    );
    
    wp_enqueue_script(
        'techops-content-sync-admin',
        TECHOPS_CONTENT_SYNC_URL . 'assets/js/admin.js',
        array('jquery'),
        TECHOPS_CONTENT_SYNC_VERSION,
        true
    );
    
    wp_localize_script('techops-content-sync-admin', 'techops_ajax', array(
        'api_url' => rest_url(),
        'nonce' => wp_create_nonce('wp_rest'),
        'strings' => array(
            'validating' => __('Validating repository...', 'techops-content-sync'),
            'downloading' => __('Downloading files...', 'techops-content-sync'),
            'installing' => __('Installing...', 'techops-content-sync'),
            'error' => __('Error:', 'techops-content-sync'),
            'success' => __('Success:', 'techops-content-sync')
        )
    ));
}
add_action('admin_enqueue_scripts', 'techops_content_sync_admin_enqueue_scripts');

/**
 * AJAX handler for getting recent operations
 */
function techops_get_recent_operations() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'techops_sync_history';
    $recent_ops = $wpdb->get_results(
        "SELECT * FROM {$table_name} ORDER BY started_at DESC LIMIT 10"
    );
    
    ob_start();
    
    if ($recent_ops) {
        echo '<table class="widefat">';
        echo '<thead><tr>';
        echo '<th>Repository</th>';
        echo '<th>Status</th>';
        echo '<th>Started</th>';
        echo '<th>Completed</th>';
        echo '<th>Result</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($recent_ops as $op) {
            $status_class = '';
            switch ($op->status) {
                case 'completed':
                    $status_class = 'success';
                    break;
                case 'failed':
                    $status_class = 'error';
                    break;
                default:
                    $status_class = 'info';
            }
            
            echo "<tr class='status-{$status_class}'>";
            echo "<td>" . esc_html($op->repository_url) . "</td>";
            echo "<td>" . esc_html(ucfirst($op->status)) . "</td>";
            echo "<td>" . esc_html($op->started_at) . "</td>";
            echo "<td>" . esc_html($op->completed_at ?: '-') . "</td>";
            echo "<td>" . esc_html($op->error_message ?: 'Success') . "</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No recent operations found.</p>';
    }
    
    $html = ob_get_clean();
    wp_send_json_success($html);
}
add_action('wp_ajax_techops_get_recent_operations', 'techops_get_recent_operations'); 