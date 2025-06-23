<?php
/**
 * Main class for Visual Regression Tester
 *
 * @package Visual_Regression_Tester
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class Visual_Regression_Tester {
	/**
	 * Instance of this class
	 *
	 * @var Visual_Regression_Tester
	 */
	private static $instance = null;

	/**
	 * BackstopJS runner instance
	 *
	 * @var VRT_Backstop_Runner
	 */
	private $backstop_runner;

	/**
	 * Get instance of this class
	 *
	 * @return Visual_Regression_Tester
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->backstop_runner = new VRT_Backstop_Runner();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_vrt_run_test', array( $this, 'handle_ajax_request' ) );
		// add_action( 'wp_ajax_vrt_test_connection', array( $this, 'handle_test_connection' ) );
		// add_action( 'wp_ajax_vrt_test_sitemap_connection', array( $this, 'handle_test_sitemap_connection' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Visual Regression Tester', 'visual-regression-tester' ),
			__( 'Visual Regression Tester', 'visual-regression-tester' ),
			'manage_options',
			'visual-regression-tester',
			array( $this, 'render_admin_page' ),
			'dashicons-visibility'
		);
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_visual-regression-tester' !== $hook ) {
			return;
		}

		// Enqueue common admin styles.
		wp_enqueue_style(
			'vrt-admin',
			plugins_url( 'admin/assets/css/style.css', __DIR__ ),
			array(),
			'1.0.0'
		);

		// Enqueue single test scripts.
		wp_enqueue_script(
			'vrt-single-test',
			plugins_url( 'admin/single-test/assets/js/single-test.js', __DIR__ ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Enqueue site-wide audit scripts.
		wp_enqueue_script(
			'vrt-site-wide-audit',
			plugins_url( 'admin/site-wide-audit/assets/js/site-wide-audit.js', __DIR__ ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Localize scripts.
		wp_localize_script(
			'vrt-single-test',
			'vrtSingleTestData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vrt_single_test' ),
			)
		);

		wp_localize_script(
			'vrt-site-wide-audit',
			'vrtSiteWideAuditData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vrt_site_wide_audit' ),
			)
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="vrt-tabs">
				<button class="vrt-tab active" data-tab="single-test">Single Test</button>
				<button class="vrt-tab" data-tab="site-wide-audit">Site-wide Audit</button>
			</div>

			<div id="single-test" class="vrt-tab-content active">
				<?php require_once plugin_dir_path( __FILE__ ) . '../admin/single-test/templates/single-test.php'; ?>
			</div>

			<div id="site-wide-audit" class="vrt-tab-content">
				<?php require_once plugin_dir_path( __FILE__ ) . '../admin/site-wide-audit/templates/site-wide-audit.php'; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request
	 */
	public function handle_ajax_request() {
		check_ajax_referer( 'vrt_run_test', 'nonce' );

		$reference_url = isset( $_POST['reference_url'] ) ? esc_url_raw( wp_unslash( $_POST['reference_url'] ) ) : '';
		$test_url      = isset( $_POST['test_url'] ) ? esc_url_raw( wp_unslash( $_POST['test_url'] ) ) : '';

		if ( empty( $reference_url ) || empty( $test_url ) ) {
			wp_send_json_error( 'Missing URLs' );
		}

		$test_id = 'vrt_' . time();
		$result  = $this->backstop_runner->run_test( $reference_url, $test_url, $test_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle test connection AJAX request
	 *
	 * @return void
	 */
	// public function handle_test_connection() {
	// Verify nonce.
	// if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_single_test' ) ) {
	// wp_send_json_error( array(
	// 'message' => 'Invalid nonce',
	// ) );
	// return;
	// }

	// Get URLs.
	// $reference_url = isset( $_POST['reference_url'] ) ? esc_url_raw( wp_unslash( $_POST['reference_url'] ) ) : '';
	// $test_url = isset( $_POST['test_url'] ) ? esc_url_raw( wp_unslash( $_POST['test_url'] ) ) : '';

	// if ( empty( $reference_url ) || empty( $test_url ) ) {
	// wp_send_json_error( array(
	// 'message' => 'Both URLs are required',
	// ) );
	// return;
	// }

	// Get authentication credentials.
	// $reference_auth = array(
	// 'username' => isset( $_POST['reference_auth_username'] ) ? sanitize_text_field( wp_unslash( $_POST['reference_auth_username'] ) ) : '',
	// 'password' => isset( $_POST['reference_auth_password'] ) ? sanitize_text_field( wp_unslash( $_POST['reference_auth_password'] ) ) : '',
	// );

	// $test_auth = array(
	// 'username' => isset( $_POST['test_auth_username'] ) ? sanitize_text_field( wp_unslash( $_POST['test_auth_username'] ) ) : '',
	// 'password' => isset( $_POST['test_auth_password'] ) ? sanitize_text_field( wp_unslash( $_POST['test_auth_password'] ) ) : '',
	// );

	// Test reference URL.
	// $reference_accessible = $this->test_url_connection( $reference_url, $reference_auth );
	// $test_accessible = $this->test_url_connection( $test_url, $test_auth );

	// if ( $reference_accessible && $test_accessible ) {
	// wp_send_json_success(
	// array(
	// 'message' => 'Both URLs are accessible',
	// )
	// );
	// } else {
	// wp_send_json_error(
	// array(
	// 'reference_needs_auth' => ! $reference_accessible,
	// 'test_needs_auth' => ! $test_accessible,
	// 'message' => 'Authentication required for one or both URLs',
	// )
	// );
	// }
	// }

	/**
	 * Test if a URL is accessible.
	 *
	 * @param string $url URL to test.
	 * @param array  $auth Authentication credentials.
	 * @return bool Whether the URL is accessible.
	 */
	private function test_url_connection( $url, $auth ) {
		$args = array(
			'timeout'     => 10,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => 'WordPress/Visual Regression Tester',
		);

		// Add authentication if provided
		if ( ! empty( $auth['username'] ) && ! empty( $auth['password'] ) ) {
			$args['headers'] = array(
				'Authorization' => 'Basic ' . base64_encode( $auth['username'] . ':' . $auth['password'] ),
			);
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		// Only consider 200-299 as successful
		// 401 means authentication is required but not provided
		return ( 200 <= $response_code && $response_code < 300 );
	}

	/**
	 * Handle test sitemap connection AJAX request
	 *
	 * @return void
	 */
	public function handle_test_sitemap_connection() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_run_site_wide_test' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Get sitemap URLs.
		$reference_sitemap = isset( $_POST['reference_sitemap'] ) ? esc_url_raw( wp_unslash( $_POST['reference_sitemap'] ) ) : '';
		$test_sitemap      = isset( $_POST['test_sitemap'] ) ? esc_url_raw( wp_unslash( $_POST['test_sitemap'] ) ) : '';

		if ( empty( $reference_sitemap ) || empty( $test_sitemap ) ) {
			wp_send_json_error( 'Both sitemap URLs are required.' );
			return;
		}

		// Get authentication credentials.
		$reference_auth = array(
			'username' => isset( $_POST['reference_auth_username'] ) ? sanitize_text_field( wp_unslash( $_POST['reference_auth_username'] ) ) : '',
			'password' => isset( $_POST['reference_auth_password'] ) ? sanitize_text_field( wp_unslash( $_POST['reference_auth_password'] ) ) : '',
		);

		$test_auth = array(
			'username' => isset( $_POST['test_auth_username'] ) ? sanitize_text_field( wp_unslash( $_POST['test_auth_username'] ) ) : '',
			'password' => isset( $_POST['test_auth_password'] ) ? sanitize_text_field( wp_unslash( $_POST['test_auth_password'] ) ) : '',
		);

		// Ensure we're using page-sitemap.xml.
		$reference_sitemap = $this->ensure_page_sitemap( $reference_sitemap );
		$test_sitemap      = $this->ensure_page_sitemap( $test_sitemap );

		// Test sitemap URLs.
		$reference_accessible = $this->test_url_connection( $reference_sitemap, $reference_auth );
		$test_accessible      = $this->test_url_connection( $test_sitemap, $test_auth );

		if ( $reference_accessible && $test_accessible ) {
			wp_send_json_success(
				array(
					'message'           => 'Both page sitemaps are accessible.',
					'reference_sitemap' => $reference_sitemap,
					'test_sitemap'      => $test_sitemap,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'reference_needs_auth' => ! $reference_accessible,
					'test_needs_auth'      => ! $test_accessible,
					'message'              => 'Authentication required for one or both page sitemaps.',
				)
			);
		}
	}

	/**
	 * Ensure the URL points to page-sitemap.xml
	 *
	 * @param string $url The sitemap URL to check.
	 * @return string The modified URL pointing to page-sitemap.xml.
	 */
	private function ensure_page_sitemap( $url ) {
		// If URL ends with sitemap.xml, replace with page-sitemap.xml.
		if ( substr( $url, -11 ) === 'sitemap.xml' ) {
			$url = substr( $url, 0, -11 ) . 'page-sitemap.xml';
		}
		return $url;
	}
}
