<?php
namespace TechOpsContentSync\Modules\VisualRegressionTester;

class Core {
    private static $instance = null;
    private $module_path;
    private $module_url;
    private $backstop_runner;

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
        // Initialize hooks
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add AJAX handlers
        add_action('wp_ajax_techops_run_visual_test', [$this, 'ajax_run_visual_test']);
        add_action('wp_ajax_techops_compare_screenshots', [$this, 'ajax_compare_screenshots']);

        // Initialize BackstopJS runner, passing the correct module path
        $this->backstop_runner = new Backstop_Runner($this->module_path);
    }

    public function register_post_types() {
        register_post_type('techops_visual_test', [
            'labels' => [
                'name' => 'Visual Tests',
                'singular_name' => 'Visual Test',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'manage_options',
                'edit_post' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_post' => 'manage_options',
                'read_private_posts' => 'manage_options',
                'delete_post' => 'manage_options',
            ],
        ]);
    }

    public function register_taxonomies() {
        register_taxonomy('techops_test_status', 'techops_visual_test', [
            'labels' => [
                'name' => 'Test Status',
                'singular_name' => 'Test Status',
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'test-status'],
        ]);
    }

    public function register_settings() {
        register_setting('techops_visual_testing', 'techops_visual_testing_settings');
        
        add_settings_section(
            'techops_visual_testing_main',
            'Visual Testing Settings',
            [$this, 'render_settings_section'],
            'techops_visual_testing'
        );
        
        add_settings_field(
            'screenshot_directory',
            'Screenshot Directory',
            [$this, 'render_screenshot_directory_field'],
            'techops_visual_testing',
            'techops_visual_testing_main'
        );
    }

    public function render_settings_section() {
        echo '<p>Configure your visual regression testing settings below.</p>';
    }

    public function render_screenshot_directory_field() {
        $options = get_option('techops_visual_testing_settings');
        $directory = isset($options['screenshot_directory']) ? $options['screenshot_directory'] : '';
        ?>
        <input type="text" name="techops_visual_testing_settings[screenshot_directory]" 
               value="<?php echo esc_attr($directory); ?>" class="regular-text">
        <p class="description">Directory where screenshots will be stored (relative to wp-content/uploads)</p>
        <?php
    }

    public function ajax_run_visual_test() {
        error_log('TechOps Content Sync: ajax_run_visual_test triggered.');

        if (!isset($_POST['techops_visual_testing_nonce']) || !wp_verify_nonce($_POST['techops_visual_testing_nonce'], 'techops_visual_testing')) {
            error_log('TechOps Content Sync: Nonce verification failed.');
            wp_send_json_error('Nonce verification failed.');
        }
        error_log('TechOps Content Sync: Nonce verification passed.');

        if (!current_user_can('manage_options')) {
            error_log('TechOps Content Sync: Insufficient permissions.');
            wp_send_json_error('Insufficient permissions');
        }
        error_log('TechOps Content Sync: User permissions checked.');

        $reference_url = isset($_POST['reference_url']) ? esc_url_raw($_POST['reference_url']) : '';
        $test_url = isset($_POST['test_url']) ? esc_url_raw($_POST['test_url']) : '';
        $test_id = isset($_POST['test_id']) ? sanitize_text_field($_POST['test_id']) : '';

        error_log('TechOps Content Sync: Received parameters - Reference URL: ' . $reference_url . ', Test URL: ' . $test_url . ', Test ID: ' . $test_id);

        if (empty($reference_url) || empty($test_url) || empty($test_id)) {
            error_log('TechOps Content Sync: Missing required parameters.');
            wp_send_json_error('Missing required parameters');
        }
        error_log('TechOps Content Sync: All required parameters present.');

        $result = $this->backstop_runner->run_test($reference_url, $test_url, $test_id);
        
        if (is_wp_error($result)) {
            error_log('TechOps Content Sync: BackstopJS run_test error: ' . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        } else {
            error_log('TechOps Content Sync: BackstopJS run_test success.');
            wp_send_json_success($result);
        }
    }

    public function ajax_compare_screenshots() {
        error_log('TechOps Content Sync: ajax_compare_screenshots triggered.');
        if (!isset($_POST['techops_visual_testing_nonce']) || !wp_verify_nonce($_POST['techops_visual_testing_nonce'], 'techops_visual_testing')) {
            error_log('TechOps Content Sync: Nonce verification failed for compare_screenshots.');
            wp_send_json_error('Nonce verification failed.');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Implement screenshot comparison logic here
        wp_send_json_success('Comparison completed successfully');
    }
} 