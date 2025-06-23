<?php
/**
 * Site-wide Audit class for Visual Regression Tester
 *
 * @package Visual_Regression_Tester
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide Audit class
 */
class VRT_Site_Wide_Audit {
	/**
	 * Instance of this class
	 *
	 * @var VRT_Site_Wide_Audit
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
	 * @return VRT_Site_Wide_Audit
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
		add_action( 'wp_ajax_vrt_test_sitemap_connection', array( $this, 'handle_test_sitemap_connection' ) );
		add_action( 'wp_ajax_vrt_run_site_wide_audit', array( $this, 'handle_run_audit' ) );
	}

	/**
	 * Handle test sitemap connection AJAX request
	 */
	public function handle_test_sitemap_connection() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_site_wide_audit' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Get sitemap URLs.
		$reference_sitemap = isset( $_POST['reference_sitemap'] ) ? esc_url_raw( wp_unslash( $_POST['reference_sitemap'] ) ) : '';
		$test_sitemap = isset( $_POST['test_sitemap'] ) ? esc_url_raw( wp_unslash( $_POST['test_sitemap'] ) ) : '';

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
		$test_sitemap = $this->ensure_page_sitemap( $test_sitemap );

		// Test sitemap URLs.
		$reference_accessible = $this->test_url_connection( $reference_sitemap, $reference_auth );
		$test_accessible = $this->test_url_connection( $test_sitemap, $test_auth );

		if ( $reference_accessible && $test_accessible ) {
			wp_send_json_success(
				array(
					'message' => 'Both page sitemaps are accessible.',
					'reference_sitemap' => $reference_sitemap,
					'test_sitemap' => $test_sitemap,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'reference_needs_auth' => ! $reference_accessible,
					'test_needs_auth' => ! $test_accessible,
					'message' => 'Authentication required for one or both page sitemaps.',
				)
			);
		}
	}

	/**
	 * Handle run audit AJAX request
	 */
	public function handle_run_audit() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_site_wide_audit' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Get sitemap URLs.
		$reference_sitemap = isset( $_POST['reference_sitemap'] ) ? esc_url_raw( wp_unslash( $_POST['reference_sitemap'] ) ) : '';
		$test_sitemap = isset( $_POST['test_sitemap'] ) ? esc_url_raw( wp_unslash( $_POST['test_sitemap'] ) ) : '';

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
		$test_sitemap = $this->ensure_page_sitemap( $test_sitemap );

		// Add authentication to URLs if provided.
		if ( ! empty( $reference_auth['username'] ) && ! empty( $reference_auth['password'] ) ) {
			$reference_sitemap = $this->add_auth_to_url( $reference_sitemap, $reference_auth );
		}

		if ( ! empty( $test_auth['username'] ) && ! empty( $test_auth['password'] ) ) {
			$test_sitemap = $this->add_auth_to_url( $test_sitemap, $test_auth );
		}

		// Run audit.
		$test_id = 'vrt_' . time();
		$result = $this->backstop_runner->run_test( $reference_sitemap, $test_sitemap, $test_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( $result );
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
			'timeout' => 10,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent' => 'WordPress/Visual Regression Tester',
		);

		// Add authentication if provided.
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
		
		// Only consider 200-299 as successful.
		// 401 means authentication is required but not provided.
		return ( 200 <= $response_code && $response_code < 300 );
	}

	/**
	 * Add authentication to URL.
	 *
	 * @param string $url URL to add authentication to.
	 * @param array  $auth Authentication credentials.
	 * @return string URL with authentication.
	 */
	private function add_auth_to_url( $url, $auth ) {
		$parsed_url = parse_url( $url );
		$parsed_url['user'] = $auth['username'];
		$parsed_url['pass'] = $auth['password'];
		
		$scheme = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
		$host = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$user = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
		$pass = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
		$pass = ( $user || $pass ) ? $pass . '@' : '';
		$path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		$query = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
		$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

		return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
	}

	/**
	 * Ensure the URL points to page-sitemap.xml.
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