<?php
/**
 * Plugin Name: Visual Regression Tester
 * Description: A WordPress plugin for visual regression testing using BackstopJS
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: visual-regression-tester
 *
 * @package Visual_Regression_Tester
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'VRT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VRT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once VRT_PLUGIN_DIR . 'includes/class-visual-regression-tester.php';
require_once VRT_PLUGIN_DIR . 'includes/class-vrt-backstop-runner.php';
require_once VRT_PLUGIN_DIR . 'public/class-vrt-frontend-shortcode.php';
require_once VRT_PLUGIN_DIR . 'public/single-test/class-vrt-public-single-test.php';

// Initialize the plugin.
function vrt_init() {
	// Initialize main plugin class using singleton pattern.
	$vrt = Visual_Regression_Tester::get_instance();
}
add_action( 'plugins_loaded', 'vrt_init' );

/**
 * Activation hook callback.
 *
 * @return void
 */
register_activation_hook(
	__FILE__,
	function () {
		// Create necessary directories.
		$backstop_dir = plugin_dir_path( __FILE__ ) . 'backstop_data';

		// Create main backstop directory if it doesn't exist.
		if ( ! file_exists( $backstop_dir ) ) {
			if ( ! wp_mkdir_p( $backstop_dir ) ) {
				wp_die( 'Failed to create backstop_data directory. Please check your WordPress installation permissions.' );
			}
		}

		// Create subdirectories.
		$subdirs = array(
			'bitmaps_reference',
			'bitmaps_test',
			'engine_scripts',
			'html_report',
			'ci_report',
		);

		foreach ( $subdirs as $subdir ) {
			$dir = $backstop_dir . '/' . $subdir;
			if ( ! file_exists( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					wp_die( 'Failed to create ' . $subdir . ' directory. Please check your WordPress installation permissions.' );
				}
			}
		}

		// Ensure directories are writable.
		if ( ! is_writable( $backstop_dir ) ) {
			wp_die( 'backstop_data directory is not writable. Please check your WordPress installation permissions.' );
		}

		// Create .htaccess to protect the directory.
		$htaccess_file = $backstop_dir . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents( $htaccess_file, $htaccess_content );
		}
	}
);

/**
 * Deactivation hook callback.
 *
 * @return void
 */
register_deactivation_hook(
	__FILE__,
	function () {
		// Clean up if needed.
	}
);
