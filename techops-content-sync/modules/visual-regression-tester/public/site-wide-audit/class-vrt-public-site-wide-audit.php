<?php
/**
 * Public Site-wide Audit Form for Visual Regression Tester.
 *
 * @package Visual_Regression_Tester\Public\Site_Wide_Audit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VRT_Public_Site_Wide_Audit
 * Handles rendering of the site-wide audit form for the frontend.
 */
class VRT_Public_Site_Wide_Audit {
	/**
	 * Instance of this class
	 *
	 * @var VRT_Public_Site_Wide_Audit
	 */
	private static $instance = null;

	/**
	 * BackstopJS runner instance
	 *
	 * @var VRT_Backstop_Runner
	 */
	private $backstop_runner;

	/**
	 * Batch size for processing URLs
	 *
	 * @var int
	 */
	private $batch_size = 3;

	/**
	 * Get instance of this class
	 *
	 * @return VRT_Public_Site_Wide_Audit
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
		add_action( 'wp_ajax_vrt_run_site_wide_audit_test', array( $this, 'handle_run_site_wide_audit_test' ) );
		add_action( 'wp_ajax_nopriv_vrt_run_site_wide_audit_test', array( $this, 'handle_run_site_wide_audit_test' ) );
		add_action( 'wp_ajax_vrt_process_audit_batch', array( $this, 'handle_process_audit_batch' ) );
		add_action( 'wp_ajax_nopriv_vrt_process_audit_batch', array( $this, 'handle_process_audit_batch' ) );
	}

	/**
	 * Enqueue CSS/JS for the frontend tool.
	 */
	public static function enqueue_assets() {
		wp_enqueue_script( 'vrt-site-wide-audit-script', VRT_PLUGIN_URL . 'public/assets/js/vrt-site-wide-audit.js', array( 'jquery' ), null, true );
		wp_localize_script(
			'vrt-site-wide-audit-script',
			'vrtSiteWideAuditData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vrt_site_wide_audit' ),
			)
		);
	}

	/**
	 * Parse sitemap XML and extract only page URLs.
	 *
	 * @param string $sitemap_url The sitemap index or page sitemap URL to parse.
	 * @param array  $auth Authentication credentials.
	 * @return array|WP_Error Array of page URLs or WP_Error on failure.
	 */
	private function parse_sitemap( $sitemap_url, $auth ) {
		$args = array(
			'timeout'     => 30,
			'user-agent'  => 'WordPress/Visual Regression Tester',
			'sslverify'   => true,
		);
		// Add authentication if provided.
		if ( ! empty( $auth['username'] ) && ! empty( $auth['password'] ) ) {
			$args['headers'] = array(
				'Authorization' => 'Basic ' . base64_encode( $auth['username'] . ':' . $auth['password'] ),
			);
		}

		add_filter('use_curl_transport', '__return_false');
		$response = wp_remote_get( $sitemap_url, $args );
		remove_filter('use_curl_transport', '__return_false');
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'vrt_sitemap_error', 'Failed to fetch sitemap: ' . $response->get_error_message() );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error( 'vrt_sitemap_error', 'Failed to fetch sitemap. Response code: ' . $response_code );
		}
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new WP_Error( 'vrt_sitemap_error', 'Empty sitemap response' );
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body );
		if ( false === $xml ) {
			$errors = libxml_get_errors();
			libxml_clear_errors();
			return new WP_Error( 'vrt_sitemap_error', 'Failed to parse sitemap XML: ' . $errors[0]->message );
		}

		$page_sitemaps = array();
		// If this is a sitemap index, extract only page sitemaps
		if ( isset( $xml->sitemap ) ) {
			foreach ( $xml->sitemap as $sitemap ) {
				if ( isset( $sitemap->loc ) && preg_match( '/\/page-sitemap\.xml\/?$/i', (string) $sitemap->loc ) ) {
					$page_sitemaps[] = (string) $sitemap->loc;
				}
			}
			if ( empty( $page_sitemaps ) ) {
				return new WP_Error( 'vrt_sitemap_error', 'No page sitemaps found in sitemap index.' );
			}
		} elseif ( preg_match( '/\/page-sitemap\.xml\/?$/i', $sitemap_url ) ) {
			$page_sitemaps[] = $sitemap_url;
		} else {
			return new WP_Error( 'vrt_sitemap_error', 'Provided sitemap is not a page sitemap or sitemap index.' );
		}

		$page_urls = array();
		foreach ( $page_sitemaps as $page_sitemap_url ) {
			$page_args = $args;
			add_filter('use_curl_transport', '__return_false');
			$page_response = wp_remote_get( $page_sitemap_url, $page_args );
			remove_filter('use_curl_transport', '__return_false');
			if ( is_wp_error( $page_response ) ) {
				continue;
			}
			$page_body = wp_remote_retrieve_body( $page_response );
			if ( empty( $page_body ) ) {
				continue;
			}
			$page_xml = simplexml_load_string( $page_body );
			if ( false === $page_xml ) {
				continue;
			}
			foreach ( $page_xml->url as $url ) {
				if ( isset( $url->loc ) ) {
					$page_urls[] = (string) $url->loc;
				}
			}
		}

		if ( empty( $page_urls ) ) {
			return new WP_Error( 'vrt_sitemap_error', 'No page URLs found in page sitemaps.' );
		}

		return $page_urls;
	}

	/**
	 * Normalize URL by removing domain and query parameters.
	 *
	 * @param string $url The URL to normalize.
	 * @return string Normalized URL path.
	 */
	private function normalize_url( $url ) {
		$parsed = parse_url( $url );
		return isset( $parsed['path'] ) ? $parsed['path'] : $url;
	}

	/**
	 * Add authentication to URL.
	 *
	 * @param string $url URL to add authentication to.
	 * @param array  $auth Authentication credentials.
	 * @return string URL with authentication.
	 */
	private function add_auth_to_url( $url, $auth ) {
		if ( empty( $auth['username'] ) || empty( $auth['password'] ) ) {
			return $url;
		}
		$parsed_url         = parse_url( $url );
		$user               = rawurlencode( $auth['username'] );
		$pass               = rawurlencode( $auth['password'] );
		$parsed_url['user'] = $user;
		$parsed_url['pass'] = $pass;
		return build_url( $parsed_url );
	}

	/**
	 * Pair URLs from reference and test sitemaps.
	 *
	 * @param array $reference_urls Array of reference URLs.
	 * @param array $test_urls Array of test URLs.
	 * @param array $reference_auth Authentication details for reference URLs.
	 * @param array $test_auth Authentication details for test URLs.
	 * @return array Array of paired URLs.
	 */
	private function pair_urls( $reference_urls, $test_urls, $reference_auth = array(), $test_auth = array() ) {
		$pairs           = array();
		$reference_paths = array();

		// Add this function here
		/**
		 * Build URL from parsed array.
		 *
		 * @param array $parts URL parts.
		 * @return string
		 */
		function build_url( array $parts ) {
			$scheme   = isset( $parts['scheme'] ) ? (string) $parts['scheme'] . '://' : '';
			$user     = isset( $parts['user'] ) ? (string) $parts['user'] : '';
			$pass     = isset( $parts['pass'] ) ? ':' . (string) $parts['pass'] : '';
			$pass     = ( $user || $pass ) ? $pass . '@' : '';
			$host     = isset( $parts['host'] ) ? (string) $parts['host'] : '';
			$port     = isset( $parts['port'] ) ? ':' . (string) $parts['port'] : '';
			$path     = isset( $parts['path'] ) ? (string) $parts['path'] : '';
			$query    = isset( $parts['query'] ) ? '?' . (string) $parts['query'] : '';
			$fragment = isset( $parts['fragment'] ) ? '#' . (string) $parts['fragment'] : '';
			return implode( '', array( $scheme, $user, $pass, $host, $port, $path, $query, $fragment ) );
		}

		// Create lookup of reference paths.
		foreach ( $reference_urls as $url ) {
			if ( ! empty( $reference_auth['username'] ) && ! empty( $reference_auth['password'] ) ) {
				$url = $this->add_auth_to_url( $url, $reference_auth );
			}
			$path                     = $this->normalize_url( $url );
			$reference_paths[ $path ] = $url;
		}

		// Match test URLs with reference URLs.
		foreach ( $test_urls as $url ) {
			if ( ! empty( $test_auth['username'] ) && ! empty( $test_auth['password'] ) ) {
				$url = $this->add_auth_to_url( $url, $test_auth );
			}
			$path    = $this->normalize_url( $url );
			$pairs[] = array(
				'reference_url' => isset( $reference_paths[ $path ] ) ? $reference_paths[ $path ] : null,
				'test_url'      => $url,
				'path'          => $path,
				'status'        => 'pending',
			);
		}

		return $pairs;
	}

	/**
	 * Store audit results in a transient.
	 *
	 * @param string $audit_id The audit ID.
	 * @param array  $results The results to store.
	 * @return bool True on success, false on failure.
	 */
	private function store_audit_results( $audit_id, $results ) {
		return set_transient( 'vrt_audit_' . $audit_id, $results, HOUR_IN_SECONDS );
	}

	/**
	 * Get stored audit results.
	 *
	 * @param string $audit_id The audit ID.
	 * @return array|false The stored results or false if not found.
	 */
	private function get_audit_results( $audit_id ) {
		return get_transient( 'vrt_audit_' . $audit_id );
	}

	/**
	 * Handle running a site-wide audit test.
	 */
	public function handle_run_site_wide_audit_test() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_site_wide_audit' ) ) {
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

		// Parse sitemaps.
		$reference_urls = $this->parse_sitemap( $reference_sitemap, $reference_auth );
		if ( is_wp_error( $reference_urls ) ) {
			wp_send_json_error( 'Failed to parse reference sitemap: ' . $reference_urls->get_error_message() );
			return;
		}

		$test_urls = $this->parse_sitemap( $test_sitemap, $test_auth );
		if ( is_wp_error( $test_urls ) ) {
			wp_send_json_error( 'Failed to parse test sitemap: ' . $test_urls->get_error_message() );
			return;
		}

		// Pair URLs.
		$pairs = $this->pair_urls( $reference_urls, $test_urls, $reference_auth, $test_auth );

		// Generate audit ID.
		$audit_id = uniqid( 'vrt_audit_' );

		// Store initial state.
		$this->store_audit_results(
			$audit_id,
			array(
				'pairs'     => $pairs,
				'processed' => 0,
				'total'     => count( $pairs ),
				'results'   => array(),
			)
		);

		// Return initial response.
		wp_send_json_success(
			array(
				'audit_id'   => $audit_id,
				'total_urls' => count( $pairs ),
				'message'    => 'Starting audit process...',
			)
		);
	}

	/**
	 * Handle processing a batch of URLs.
	 */
	public function handle_process_audit_batch() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vrt_site_wide_audit' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Get audit ID.
		$audit_id = isset( $_POST['audit_id'] ) ? sanitize_text_field( wp_unslash( $_POST['audit_id'] ) ) : '';
		if ( empty( $audit_id ) ) {
			wp_send_json_error( 'Audit ID is required.' );
			return;
		}

		// Get stored results.
		$audit_data = $this->get_audit_results( $audit_id );
		if ( false === $audit_data ) {
			wp_send_json_error( 'Audit not found.' );
			return;
		}

		// Process next batch.
		$start_index   = $audit_data['processed'];
		$end_index     = min( $start_index + $this->batch_size, $audit_data['total'] );
		$current_batch = array_slice( $audit_data['pairs'], $start_index, $this->batch_size );

		$batch_results = array();
		foreach ( $current_batch as $pair ) {
			if ( empty( $pair['reference_url'] ) ) {
				$batch_results[] = array(
					'pair'   => $pair,
					'status' => 'skipped',
					'error'  => 'Reference URL not found',
				);
				continue;
			}
			$test_id = 'vrt_' . time();
			$result = $this->backstop_runner->run_test( $pair['reference_url'], $pair['test_url'], $test_id );
			if ( is_wp_error( $result ) ) {
				$batch_results[] = array(
					'pair'   => $pair,
					'status' => 'error',
					'result' => null,
					'error'  => $result->get_error_message(),
				);
			} else {
				$batch_results[] = array(
					'pair'   => $pair,
					'status' => 'success',
					'result' => array('report_url' => isset($result['report_url']) ? $result['report_url'] : null),
					'error'  => null,
				);
			}
		}

		// Update stored results.
		$audit_data['processed'] = $end_index;
		$audit_data['results']   = array_merge( $audit_data['results'], $batch_results );
		$this->store_audit_results( $audit_id, $audit_data );

		// Return batch results.
		wp_send_json_success(
			array(
				'processed'     => $end_index,
				'total'         => $audit_data['total'],
				'batch_results' => $batch_results,
				'is_complete'   => $end_index >= $audit_data['total'],
			)
		);
	}

	/**
	 * Render the Site-wide Audit form.
	 *
	 * @return string
	 */
	public static function render_form() {
		ob_start();
		?>
		<div class="vrt-form-container">
			<form id="vrt-site-wide-audit-form" class="vrt-form">
				<?php wp_nonce_field( 'vrt_site_wide_audit', 'vrt_site_wide_audit_nonce' ); ?>

				<div class="vrt-form-group">
					<label for="site-audit-reference-sitemap"><?php esc_html_e( 'Reference Sitemap URL', 'visual-regression-tester' ); ?></label>
					<input type="url" id="site-audit-reference-sitemap" name="reference_sitemap" required>
					<div class="reference-auth-fields auth-fields" style="display: none;">
						<label for="site-audit-reference-auth-username"><?php esc_html_e( 'Username', 'visual-regression-tester' ); ?></label>
						<input type="text" id="site-audit-reference-auth-username" name="reference_auth_username">
						<label for="site-audit-reference-auth-password"><?php esc_html_e( 'Password', 'visual-regression-tester' ); ?></label>
						<input type="password" id="site-audit-reference-auth-password" name="reference_auth_password">
					</div>
				</div>

				<div class="vrt-form-group">
					<label for="site-audit-test-sitemap"><?php esc_html_e( 'Test Sitemap URL', 'visual-regression-tester' ); ?></label>
					<input type="url" id="site-audit-test-sitemap" name="test_sitemap" required>
					<div class="test-auth-fields auth-fields" style="display: none;">
						<label for="site-audit-test-auth-username"><?php esc_html_e( 'Username', 'visual-regression-tester' ); ?></label>
						<input type="text" id="site-audit-test-auth-username" name="test_auth_username">
						<label for="site-audit-test-auth-password"><?php esc_html_e( 'Password', 'visual-regression-tester' ); ?></label>
						<input type="password" id="site-audit-test-auth-password" name="test_auth_password">
					</div>
				</div>

				<div class="vrt-form-actions">
					<button type="button" id="vrt-test-sitemap" class="button button-secondary">
						<?php esc_html_e( 'Test Sitemap', 'visual-regression-tester' ); ?>
					</button>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Run Audit', 'visual-regression-tester' ); ?>
					</button>
				</div>

				<div class="vrt-loading" style="display: none;">
					<?php esc_html_e( 'Processing...', 'visual-regression-tester' ); ?>
				</div>

				<div class="vrt-result" style="display: none;"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}

new VRT_Public_Site_Wide_Audit();
