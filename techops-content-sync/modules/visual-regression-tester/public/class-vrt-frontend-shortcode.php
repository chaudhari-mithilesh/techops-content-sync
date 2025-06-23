<?php
/**
 * Frontend Shortcode for Visual Regression Tester.
 *
 * @package Visual_Regression_Tester\Public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include public form classes.
// require_once plugin_dir_path( __FILE__ ) . 'single-test/class-vrt-public-single-test.php';
require_once plugin_dir_path( __FILE__ ) . 'site-wide-audit/class-vrt-public-site-wide-audit.php';

/**
 * Class VRT_Frontend_Shortcode
 * Handles the public-facing shortcode and asset loading for the Visual Regression Tester.
 */
class VRT_Frontend_Shortcode {
	/**
	 * Register the shortcode and enqueue assets.
	 */
	public static function register() {
		add_shortcode( 'visual_regression_tester', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_ajax_vrt_test_connection', array( __CLASS__, 'handle_test_connection_static' ) );
		add_action( 'wp_ajax_nopriv_vrt_test_connection', array( __CLASS__, 'handle_test_connection_static' ) );
	}

	/**
	 * Static wrapper for handle_test_connection.
	 */
	public static function handle_test_connection_static() {
		$instance = new self();
		$instance->handle_test_connection();
	}

	/**
	 * Render the Visual Regression Tester shortcode.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		ob_start();
		?>
		<div id="vrt-tool-frontend">
			<h2>Visual Regression Tester</h2>
			<div class="vrt-tabs">
				<button class="vrt-tab active" data-tab="single">Single Test</button>
				<button class="vrt-tab" data-tab="sitewide">Site-wide Audit</button>
			</div>
			<div class="vrt-tab-content active" id="single">
				<?php echo VRT_Public_Single_Test::render_form(); ?>
			</div>
			<div class="vrt-tab-content" id="sitewide">
				<?php echo VRT_Public_Site_Wide_Audit::render_form(); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle test connection AJAX request
	 *
	 * @return void
	 */
	public function handle_test_connection() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_single_test' ) ) {
			wp_send_json_error(
				array(
					'message' => 'Invalid nonce',
				)
			);
			return;
		}

		// Get URLs.
		$reference_url = isset( $_POST['reference_url'] ) ? esc_url_raw( wp_unslash( $_POST['reference_url'] ) ) : '';
		$test_url      = isset( $_POST['test_url'] ) ? esc_url_raw( wp_unslash( $_POST['test_url'] ) ) : '';

		if ( empty( $reference_url ) || empty( $test_url ) ) {
			wp_send_json_error(
				array(
					'message' => 'Both URLs are required',
				)
			);
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

		// Test reference URL.
		$reference_accessible = $this->test_url_connection( $reference_url, $reference_auth );
		$test_accessible      = $this->test_url_connection( $test_url, $test_auth );

		if ( $reference_accessible && $test_accessible ) {
			wp_send_json_success(
				array(
					'message' => 'Both URLs are accessible',
				)
			);
		} else {
			wp_send_json_error(
				array(
					'reference_needs_auth' => ! $reference_accessible,
					'test_needs_auth'      => ! $test_accessible,
					'message'              => 'Authentication required for one or both URLs',
				)
			);
		}
	}

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
}

VRT_Frontend_Shortcode::register();
