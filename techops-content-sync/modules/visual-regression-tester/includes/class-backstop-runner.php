<?php
/**
 * BackstopJS Runner class
 *
 * @package Visual_Regression_Tester
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VRT_Backstop_Runner
 */
class VRT_Backstop_Runner {
	/**
	 * Instance of this class
	 *
	 * @var VRT_Backstop_Runner
	 */
	private static $instance = null;

	/**
	 * Get instance of this class
	 *
	 * @return VRT_Backstop_Runner
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
		// Initialize if needed.
	}

	/**
	 * Run a visual regression test
	 *
	 * @param string $reference_url Reference URL.
	 * @param string $test_url Test URL.
	 * @param string $test_id Test ID.
	 * @return array|WP_Error Test results or error.
	 */
	public function run_test( $reference_url, $test_url, $test_id ) {
		// Create test directory.
		$test_dir = plugin_dir_path( __DIR__ ) . 'backstop_data/' . $test_id;
		if ( ! wp_mkdir_p( $test_dir ) ) {
			return new WP_Error( 'vrt_test_error', 'Could not create test directory.' );
		}

		// Create all required subdirectories.
		$subdirs = array(
			'bitmaps_reference',
			'bitmaps_test',
			'engine_scripts',
			'html_report',
			'ci_report',
		);

		foreach ( $subdirs as $subdir ) {
			$dir = $test_dir . '/' . $subdir;
			if ( ! wp_mkdir_p( $dir ) ) {
				return new WP_Error( 'vrt_test_error', 'Could not create ' . $subdir . ' directory.' );
			}
			$this->set_directory_permissions( $dir );
		}

		// Create BackstopJS config.
		$config = array(
			'id'                => $test_id,
			'viewports'         => array(
				array(
					'label'  => 'desktop',
					'width'  => 1920,
					'height' => 1080,
				),
			),
			'scenarios'         => array(
				array(
					'label'                 => 'Test Scenario',
					'url'                   => $test_url,
					'referenceUrl'          => $reference_url,
					'readyEvent'            => '',
					'readySelector'         => '',
					'delay'                 => 0,
					'hideSelectors'         => array(),
					'removeSelectors'       => array(),
					'hoverSelector'         => '',
					'clickSelector'         => '',
					'postInteractionWait'   => 0,
					'selectors'             => array(),
					'selectorExpansion'     => true,
					'expect'                => 0,
					'misMatchThreshold'     => 0.1,
					'requireSameDimensions' => true,
				),
			),
			'paths'             => array(
				'bitmaps_reference' => $test_dir . '/bitmaps_reference',
				'bitmaps_test'      => $test_dir . '/bitmaps_test',
				'engine_scripts'    => $test_dir . '/engine_scripts',
				'html_report'       => $test_dir . '/html_report',
				'ci_report'         => $test_dir . '/ci_report',
			),
			'report'            => array( 'browser' ),
			'engine'            => 'puppeteer',
			'engineOptions'     => array(
				'args' => array( '--no-sandbox' ),
			),
			'asyncCaptureLimit' => 5,
			'asyncCompareLimit' => 50,
			'debug'             => false,
			'debugWindow'       => false,
		);

		// Write config file.
		$config_file = $test_dir . '/backstop.json';
		if ( ! file_put_contents( $config_file, wp_json_encode( $config, JSON_PRETTY_PRINT ) ) ) {
			return new WP_Error( 'vrt_test_error', 'Could not write config file.' );
		}

		// Set permissions for config file.
		chmod( $config_file, 0644 );

		// Run BackstopJS test with known paths.
		$command = 'cd ' . escapeshellarg( $test_dir ) . ' && npx backstop test --config=backstop.json';
		error_log( 'Executing BackstopJS command: ' . $command );

		exec( $command, $output, $return_var );

		// Log the command output for debugging.
		error_log( 'BackstopJS test command output: ' . print_r( $output, true ) );
		error_log( 'BackstopJS test return value: ' . $return_var );

		// Check if BackstopJS command failed.
		if ( 0 !== $return_var ) {
			$error_message  = 'BackstopJS command failed. ';
			$error_message .= 'Return code: ' . $return_var . '. ';
			$error_message .= 'Command output: ' . implode( "\n", $output );
			return new WP_Error( 'vrt_test_error', $error_message );
		}

		// Check if report directory exists.
		$report_dir = $test_dir . '/html_report';
		if ( ! is_dir( $report_dir ) ) {
			error_log( 'Report directory not found at: ' . $report_dir );
			return new WP_Error( 'vrt_test_error', 'Report directory not found. Check if BackstopJS generated the report.' );
		}

		// Check if report file exists.
		$report_file = $report_dir . '/index.html';
		if ( ! file_exists( $report_file ) ) {
			error_log( 'Report file not found at: ' . $report_file );
			return new WP_Error( 'vrt_test_error', 'Report file not found. Check if BackstopJS generated the report.' );
		}

		// Generate report URL.
		$report_url = plugin_dir_url( __DIR__ ) . 'backstop_data/' . $test_id . '/html_report/index.html';

		// Check if there are any mismatches in the output.
		$has_mismatches = false;
		foreach ( $output as $line ) {
			if ( strpos( $line, 'Mismatch errors found' ) !== false ) {
				$has_mismatches = true;
				break;
			}
		}

		// Return results.
		return array(
			'success'        => true,
			'report_url'     => $report_url,
			'message'        => $has_mismatches ? 'Visual differences found. Please check the report for details.' : 'No visual differences found.',
			'has_mismatches' => $has_mismatches,
		);
	}

	/**
	 * Add authentication to URL
	 *
	 * @param string $url The URL to add authentication to.
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

	/**
	 * Set proper permissions for a directory and its contents.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	private function set_directory_permissions( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		// Set directory permissions.
		chmod( $dir, 0755 );

		// Set permissions for all files in the directory.
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				chmod( $file->getPathname(), 0755 );
			} else {
				chmod( $file->getPathname(), 0644 );
			}
		}
	}

	/**
	 * Create BackstopJS configuration
	 *
	 * @param string $reference_url Reference URL.
	 * @param string $test_url Test URL.
	 * @param string $test_dir Test directory.
	 * @return array Configuration array
	 */
	private function create_config( $reference_url, $test_url, $test_dir ) {
		return array(
			'id'                => 'visual_regression_test',
			'viewports'         => array(
				array(
					'name'   => 'desktop',
					'width'  => 1920,
					'height' => 1080,
				),
			),
			'scenarios'         => array(
				array(
					'label'                 => 'Homepage',
					'url'                   => $test_url,
					'referenceUrl'          => $reference_url,
					'readyEvent'            => '',
					'readySelector'         => '',
					'delay'                 => 0,
					'hideSelectors'         => array(),
					'removeSelectors'       => array(),
					'hoverSelector'         => '',
					'clickSelector'         => '',
					'postInteractionWait'   => 0,
					'selectors'             => array(),
					'selectorExpansion'     => true,
					'expect'                => 0,
					'misMatchThreshold'     => 0.1,
					'requireSameDimensions' => true,
				),
			),
			'paths'             => array(
				'bitmaps_reference' => $test_dir . '/backstop_data/bitmaps_reference',
				'bitmaps_test'      => $test_dir . '/backstop_data/bitmaps_test',
				'engine_scripts'    => $test_dir . '/backstop_data/engine_scripts',
				'html_report'       => $test_dir . '/backstop_data/html_report',
				'ci_report'         => $test_dir . '/backstop_data/ci_report',
			),
			'report'            => array( 'browser' ),
			'engine'            => 'puppeteer',
			'engineOptions'     => array(
				'args' => array( '--no-sandbox' ),
			),
			'asyncCaptureLimit' => 5,
			'asyncCompareLimit' => 50,
			'debug'             => false,
			'debugWindow'       => false,
		);
	}
}
