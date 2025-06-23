<?php
/**
 * Public Single Test Form for Visual Regression Tester.
 *
 * @package Visual_Regression_Tester\Public\Single_Test
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VRT_Public_Single_Test
 * Handles rendering of the single test form for the frontend.
 */
class VRT_Public_Single_Test {
	/**
	 * Instance of this class
	 *
	 * @var VRT_Public_Single_Test
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
	 * @return VRT_Public_Single_Test
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->backstop_runner = new VRT_Backstop_Runner();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_vrt_wdm_run_single_test', array( $this, 'handle_run_single_test' ) );
		add_action( 'wp_ajax_nopriv_vrt_wdm_run_single_test', array( $this, 'handle_run_single_test' ) );
	}

	/**
	 * Render the Single Test form.
	 *
	 * @return string
	 */
	public static function render_form() {
		ob_start();
		?>
		<div class="vrt-form-container">
			<form id="vrt-single-test-form" class="vrt-form">
				<div class="vrt-form-group">
					<label for="single-test-reference-url"><?php esc_html_e( 'Reference URL', 'visual-regression-tester' ); ?></label>
					<input type="url" id="single-test-reference-url" name="reference_url" required>
					<div class="reference-auth-fields auth-fields" style="display: none;">
						<label for="single-test-reference-auth-username"><?php esc_html_e( 'Username', 'visual-regression-tester' ); ?></label>
						<input type="text" id="single-test-reference-auth-username" name="reference_auth_username">
						<label for="single-test-reference-auth-password"><?php esc_html_e( 'Password', 'visual-regression-tester' ); ?></label>
						<input type="password" id="single-test-reference-auth-password" name="reference_auth_password">
					</div>
				</div>

				<div class="vrt-form-group">
					<label for="single-test-test-url"><?php esc_html_e( 'Test URL', 'visual-regression-tester' ); ?></label>
					<input type="url" id="single-test-test-url" name="test_url" required>
					<div class="test-auth-fields auth-fields" style="display: none;">
						<label for="single-test-test-auth-username"><?php esc_html_e( 'Username', 'visual-regression-tester' ); ?></label>
						<input type="text" id="single-test-test-auth-username" name="test_auth_username">
						<label for="single-test-test-auth-password"><?php esc_html_e( 'Password', 'visual-regression-tester' ); ?></label>
						<input type="password" id="single-test-test-auth-password" name="test_auth_password">
					</div>
				</div>

				<div class="vrt-form-actions">
					<button type="button" id="vrt-test-connection" class="button button-secondary">
						<?php esc_html_e( 'Test Connection', 'visual-regression-tester' ); ?>
					</button>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Run Test', 'visual-regression-tester' ); ?>
					</button>
				</div>

				<div class="vrt-loading" style="display: none;">
					<?php esc_html_e( 'Processing...', 'visual-regression-tester' ); ?>
				</div>

				<div class="vrt-result" style="display: none;"></div>

				<div id="vrt-report-buttons" class="vrt-report-buttons" style="display: none;">
					<div id="vrt-view-report" class="vrt-view-report">
						<a href="#" target="_blank" class="button button-primary">
							<?php esc_html_e( 'View Report', 'visual-regression-tester' ); ?>
						</a>
					</div>
				</div>
			</form>
		</div> 
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue CSS/JS for the frontend tool.
	 */
	public static function enqueue_assets() {
		// Enqueue CSS/JS for the frontend tool.
		wp_enqueue_style( 'vrt-frontend-style', VRT_PLUGIN_URL . 'public/assets/css/vrt-frontend.css' );
		wp_enqueue_script( 'vrt-frontend-script', VRT_PLUGIN_URL . 'public/assets/js/vrt-frontend.js', array( 'jquery' ), null, true );
		wp_localize_script(
			'vrt-frontend-script',
			'vrtSingleTestData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vrt_single_test' ),
			)
		);
	}

	/**
	 * Handle running a single test.
	 */
	public function handle_run_single_test() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_single_test' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Get URLs.
		$reference_url = isset( $_POST['reference_url'] ) ? esc_url_raw( wp_unslash( $_POST['reference_url'] ) ) : '';
		$test_url      = isset( $_POST['test_url'] ) ? esc_url_raw( wp_unslash( $_POST['test_url'] ) ) : '';

		if ( empty( $reference_url ) || empty( $test_url ) ) {
			wp_send_json_error( 'Both URLs are required.' );
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

		// Add authentication to URLs if provided.
		if ( ! empty( $reference_auth['username'] ) && ! empty( $reference_auth['password'] ) ) {
			$reference_url = $this->add_auth_to_url( $reference_url, $reference_auth );
		}

		if ( ! empty( $test_auth['username'] ) && ! empty( $test_auth['password'] ) ) {
			$test_url = $this->add_auth_to_url( $test_url, $test_auth );
		}

		// Run test.
		$test_id = 'vrt_' . time();
		$result  = $this->backstop_runner->run_test( $reference_url, $test_url, $test_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( $result );
		}
	}

	/**
	 * Add authentication to URL.
	 *
	 * @param string $url URL to add authentication to.
	 * @param array  $auth Authentication credentials.
	 * @return string URL with authentication.
	 */
	private function add_auth_to_url( $url, $auth ) {
		$parsed_url         = parse_url( $url );
		$parsed_url['user'] = $auth['username'];
		$parsed_url['pass'] = $auth['password'];

		$scheme   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$user     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
		$pass     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
		$pass     = ( $user || $pass ) ? $pass . '@' : '';
		$path     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
		$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

		return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
	}
}

new VRT_Public_Single_Test();
