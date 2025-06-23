<?php
/**
 * Single Test template
 *
 * @package Visual_Regression_Tester
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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