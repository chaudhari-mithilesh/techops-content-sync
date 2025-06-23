<?php
/**
 * BackstopJS Runner class
 *
 * @package Visual_Regression_Tester
 */

namespace TechOpsContentSync\Modules\VisualRegressionTester;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Backstop_Runner
 */
class Backstop_Runner {
	private $module_root_path;

	public function __construct( $module_root_path ) {
		$this->module_root_path = trailingslashit( $module_root_path );
	}

	/**
	 * Run BackstopJS test
	 *
	 * @param string $reference_url Reference URL.
	 * @param string $test_url Test URL.
	 * @param string $test_id Test ID.
	 * @return array|WP_Error Test results or error
	 */
	public function run_test( $reference_url, $test_url, $test_id ) {
		$node_path     = 'node'; // Assuming node is in system PATH
		$backstop_cli_path = $this->module_root_path . 'node_modules/backstopjs/cli/index.js';

		if ( ! file_exists( $backstop_cli_path ) ) {
			error_log( 'TechOps Content Sync: BackstopJS CLI not found at: ' . $backstop_cli_path );
			return new \WP_Error( 'missing_dependencies', 'Required dependency (BackstopJS) not found. Please ensure Node.js and npm are installed, then run `npm install` in the `techops-content-sync/modules/visual-regression-tester` plugin directory.' );
		}

		// Optional: Check if node is executable, though 'node' in PATH usually works fine
		$node_exists = shell_exec( 'which node' );
		if ( empty( $node_exists ) ) {
			error_log( 'TechOps Content Sync: Node.js executable not found in system PATH.' );
			return new \WP_Error( 'missing_dependencies', 'Required dependency (Node.js) not found. Please ensure Node.js is installed and accessible in your system PATH.' );
		}

		$test_dir = WP_CONTENT_DIR . '/vrt-tests/' . $test_id;
		if ( ! wp_mkdir_p( $test_dir ) ) {
			return new \WP_Error( 'directory_creation_failed', 'Failed to create test directory' );
		}

		// Set proper permissions for the test directory.
		chmod( $test_dir, 0755 );

		$config      = $this->create_config( $reference_url, $test_url, $test_dir );
		$config_file = $test_dir . '/backstop.json';

		if ( file_put_contents( $config_file, json_encode( $config ) ) === false ) {
			return new \WP_Error( 'config_creation_failed', 'Failed to create BackstopJS configuration' );
		}

		// Create engine scripts for handling lazy loading.
		$this->create_engine_scripts( $test_dir );

		// First, run reference command to create reference images.
		$reference_command = sprintf(
			'%s %s reference --config=%s',
			escapeshellarg( $node_path ), // Use 'node' or dynamically found node path
			escapeshellarg( $backstop_cli_path ), // Use the local backstopjs cli path
			escapeshellarg( $config_file )
		);

		error_log( 'TechOps Content Sync: Running reference command: ' . $reference_command );
		exec( $reference_command . ' 2>&1', $reference_output, $reference_code );
		$reference_output = implode( "\n", $reference_output );
		error_log( 'TechOps Content Sync: Reference command output: ' . $reference_output );

		if ( 0 !== $reference_code ) {
			return new \WP_Error( 'reference_failed', 'BackstopJS reference creation failed: ' . $reference_output );
		}

		// Then run the test command.
		$test_command = sprintf(
			'%s %s test --config=%s',
			escapeshellarg( $node_path ), // Use 'node' or dynamically found node path
			escapeshellarg( $backstop_cli_path ), // Use the local backstopjs cli path
			escapeshellarg( $config_file )
		);

		error_log( 'TechOps Content Sync: Running test command: ' . $test_command );
		exec( $test_command . ' 2>&1', $test_output, $test_code );
		$test_output = implode( "\n", $test_output );
		error_log( 'TechOps Content Sync: Test command output: ' . $test_output );

		// Generate the report URL using content URL.
		$content_url = content_url();
		$report_dir  = $test_dir . '/backstop_data/html_report';
		$report_url  = $content_url . '/vrt-tests/' . $test_id . '/backstop_data/html_report/index.html';

		// Check if the report directory exists.
		if ( ! is_dir( $report_dir ) ) {
			return new \WP_Error( 'report_not_found', 'Report directory not found at: ' . $report_dir );
		}

		// Check if the report file exists.
		if ( ! file_exists( $report_dir . '/index.html' ) ) {
			return new \WP_Error( 'report_not_found', 'Report file not found at: ' . $report_dir . '/index.html' );
		}

		// Set proper permissions for the report directory and files.
		$this->set_directory_permissions( $report_dir );

		// Check for mismatches in the test output.
		$has_mismatches = strpos( $test_output, 'Mismatch errors found' ) !== false;
		$message        = $has_mismatches ? 'Visual differences found. Please check the report for details.' : 'No visual differences found.';

		return array(
			'success'        => true, // Always return true since we have a report.
			'report_url'     => $report_url,
			'message'        => $message,
			'has_mismatches' => $has_mismatches,
		);
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
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
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
					'delay'                 => 10000, // Initial delay for page load.
					'hideSelectors'         => array(),
					'removeSelectors'       => array(),
					'hoverSelector'         => '',
					'clickSelector'         => '',
					'postInteractionWait'   => 5000, // Wait after scrolling.
					'selectors'             => array(),
					'selectorExpansion'     => true,
					'expect'                => 0,
					'misMatchThreshold'     => 0.1,
					'requireSameDimensions' => true,
					'onBeforeScript'        => 'onBefore.js',
					'onReadyScript'         => 'onReady.js',
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

	/**
	 * Create engine scripts for handling lazy loading
	 *
	 * @param string $test_dir Test directory.
	 * @return void
	 */
	private function create_engine_scripts( $test_dir ) {
		$engine_scripts_dir = $test_dir . '/backstop_data/engine_scripts';
		if ( ! wp_mkdir_p( $engine_scripts_dir ) ) {
			return;
		}

		// Create onBefore.js script.
		$on_before_script = <<<'EOD'
module.exports = async (page, scenario) => {
    // Set viewport size.
    await page.setViewport({
        width: 1920,
        height: 1080
    });
};
EOD;
		file_put_contents( $engine_scripts_dir . '/onBefore.js', $on_before_script );

		// Create advanced onReady.js script for lazy loading and animated counters.
		$on_ready_script = <<<'EOD'
module.exports = async (page, scenario) => {
    const wait = ms => new Promise(resolve => setTimeout(resolve, ms));

    // Scroll stepwise and pause at each step
    await page.evaluate(async () => {
        await new Promise(async resolve => {
            let totalHeight = 0;
            const distance = 200;
            const delay = 400;
            while (totalHeight < document.body.scrollHeight) {
                window.scrollBy(0, distance);
                totalHeight += distance;
                await new Promise(r => setTimeout(r, delay));
            }
            resolve();
        });
    });

    // Scroll to the bottom section (counters)
    await page.evaluate(() => {
        const countersSection = Array.from(document.querySelectorAll('section, div'))
            .find(el => el.textContent.includes('Utbilda dig när som helst på dygnet'));
        if (countersSection) countersSection.scrollIntoView({behavior: 'smooth', block: 'center'});
    });

    // Wait for counters to finish animating (poll for stable values)
    await page.evaluate(async () => {
        function getCounterValues() {
            return Array.from(document.querySelectorAll('span, .counter, .odometer, .count'))
                .map(el => el.textContent.trim());
        }
    });
};
EOD;
		file_put_contents( $engine_scripts_dir . '/onReady.js', $on_ready_script );
	}
}
