<?php
/**
 * Site-wide Audit template for Visual Regression Tester
 *
 * @package Visual_Regression_Tester
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Site-wide Audit', 'visual-regression-tester' ); ?></h1>

	<div class="vrt-form-container">
		<form id="vrt-site-wide-audit-form" class="vrt-form">
			<?php wp_nonce_field( 'vrt_site_wide_audit', 'vrt_site_wide_audit_nonce' ); ?>

			<div class="vrt-form-group">
				<label for="site-audit-reference-sitemap"><?php esc_html_e( 'Reference Sitemap URL', 'visual-regression-tester' ); ?></label>
				<input type="url" id="site-audit-reference-sitemap" name="reference_sitemap" required>
				<div class="reference-auth-fields auth-fields">
					<label for="site-audit-reference-auth-username"><?php esc_html_e( 'Username', 'visual-regression-tester' ); ?></label>
					<input type="text" id="site-audit-reference-auth-username" name="reference_auth_username">
					<label for="site-audit-reference-auth-password"><?php esc_html_e( 'Password', 'visual-regression-tester' ); ?></label>
					<input type="password" id="site-audit-reference-auth-password" name="reference_auth_password">
				</div>
			</div>

			<div class="vrt-form-group">
				<label for="site-audit-test-sitemap"><?php esc_html_e( 'Test Sitemap URL', 'visual-regression-tester' ); ?></label>
				<input type="url" id="site-audit-test-sitemap" name="test_sitemap" required>
				<div class="test-auth-fields auth-fields">
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

		<div id="vrt-loading" class="vrt-loading" style="display: none;">
			<div class="vrt-loading-spinner"></div>
			<p><?php esc_html_e( 'Running site-wide audit...', 'visual-regression-tester' ); ?></p>
		</div>

		<div id="vrt-results" class="vrt-results" style="display: none;">
			<h2><?php esc_html_e( 'Audit Results', 'visual-regression-tester' ); ?></h2>
			<div id="vrt-results-content"></div>
		</div>
	</div>
</div> 