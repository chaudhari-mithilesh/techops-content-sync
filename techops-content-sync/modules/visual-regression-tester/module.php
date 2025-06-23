<?php
namespace TechOpsContentSync\Modules\VisualRegressionTester;
error_log('TechOps Content Sync: DEBUG: module.php loaded.');

class Module {
    private static $instance = null;
    private $module_path;
    private $module_url;

    private function __construct() {
        $this->module_path = TECHOPS_CONTENT_SYNC_DIR . 'modules/visual-regression-tester';
        $this->module_url = TECHOPS_CONTENT_SYNC_URL . 'modules/visual-regression-tester';
        $this->init();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init() {
        // Load module dependencies
        $this->load_dependencies();
        
        // Initialize module components
        \TechOpsContentSync\Modules\VisualRegressionTester\Core::get_instance();
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    private function load_dependencies() {
        // Load module classes
        $classes_dir = $this->module_path . '/includes';
        if (is_dir($classes_dir)) {
            $files = glob($classes_dir . '/*.php');
            foreach ($files as $file) {
                require_once $file;
            }
        }
    }

    public function init_module() {
        // This method is no longer directly hooked to 'init', its logic is moved
        // You can remove this method if no other logic relies on it
    }

    public function add_admin_menu() {
        // Add submenu to main plugin menu
        add_submenu_page(
            'techops-content-sync',
            'Visual Regression Testing',
            'Visual Testing',
            'manage_options',
            'techops-visual-testing',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        // Render the admin page
        require_once $this->module_path . '/templates/admin-page.php';
    }

    public function enqueue_admin_assets($hook) {
        if ('techops-content-sync_page_techops-visual-testing' !== $hook) {
            return;
        }

        // Enqueue module assets
        wp_enqueue_style(
            'techops-visual-testing-admin',
            $this->module_url . '/assets/css/admin.css',
            [],
            TECHOPS_CONTENT_SYNC_VERSION
        );

        wp_enqueue_script(
            'techops-visual-testing-admin',
            $this->module_url . '/assets/js/admin.js',
            ['jquery'],
            TECHOPS_CONTENT_SYNC_VERSION,
            true
        );
    }
}

// Initialize the module
Module::get_instance(); 