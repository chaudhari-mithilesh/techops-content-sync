<?php
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('techops_visual_testing_settings');
?>

<div class="wrap">
    <h1>Visual Regression Testing</h1>

    <h2 class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active" id="single-test-tab">Single Test</a>
        <a href="#" class="nav-tab" id="site-wide-audit-tab">Site-wide Audit</a>
    </h2>

    <div id="single-test-panel" class="tab-panel">
        <div class="card">
            <h2>Single Test</h2>
            <form id="single-test-form">
                <?php wp_nonce_field('techops_visual_testing', 'techops_visual_testing_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="reference_url">Reference URL</label></th>
                        <td><input type="url" id="reference_url" name="reference_url" class="regular-text" value="" placeholder="e.g., https://reference.example.com"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="test_url">Test URL</label></th>
                        <td><input type="url" id="test_url" name="test_url" class="regular-text" value="" placeholder="e.g., https://test.example.com"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button" id="test-connection">Test Connection</button>
                    <button type="submit" class="button button-primary" id="run-single-test">Run Test</button>
                </p>
                <p id="test-connection-result" style="margin-top:10px;"></p>
            </form>
        </div>
    </div>

    <div id="site-wide-audit-panel" class="tab-panel" style="display: none;">
        <div class="card">
            <h2>Site-wide Audit</h2>
            <form id="site-wide-audit-form">
                <?php wp_nonce_field('techops_visual_testing', 'techops_visual_testing_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="reference_sitemap_url">Reference Sitemap URL</label></th>
                        <td><input type="url" id="reference_sitemap_url" name="reference_sitemap_url" class="regular-text" value="" placeholder="e.g., https://reference.example.com/sitemap.xml"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="test_sitemap_url">Test Sitemap URL</label></th>
                        <td><input type="url" id="test_sitemap_url" name="test_sitemap_url" class="regular-text" value="" placeholder="e.g., https://test.example.com/sitemap.xml"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button" id="test-sitemap">Test Sitemap</button>
                    <button type="submit" class="button button-primary" id="run-site-wide-audit">Run Audit</button>
                </p>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Recent Tests</h2>
        <?php
        $tests = get_posts([
            'post_type' => 'techops_visual_test',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        if ($tests): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td><?php echo esc_html($test->post_title); ?></td>
                            <td>
                                <?php
                                $terms = get_the_terms($test->ID, 'techops_test_status');
                                if ($terms && !is_wp_error($terms)) {
                                    echo esc_html($terms[0]->name);
                                }
                                ?>
                            </td>
                            <td><?php echo get_the_date('Y-m-d H:i:s', $test->ID); ?></td>
                            <td>
                                <a href="#" class="view-test" data-id="<?php echo $test->ID; ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tests have been run yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-panel').hide();
        $('#' + $(this).attr('id').replace('-tab', '-panel')).show();
    });

    // Initial display
    $('#single-test-tab').click();

    // Single Test form submission
    $('#single-test-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $button = $('#run-single-test');
        $button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'techops_run_visual_test',
                'techops_visual_testing_nonce': $form.find('input[name="techops_visual_testing_nonce"]').val(),
                reference_url: $('#reference_url').val(),
                test_url: $('#test_url').val(),
                test_id: 'single-test-' + Date.now() // Unique ID for the test
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                console.log('AJAX Response Data:', response.data);
                if (response.success) {
                    alert('Single test completed successfully! Report URL: ' + response.data.report_url);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while running the single test');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Test Connection (Single Test)
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        const referenceUrl = $('#reference_url').val();
        const testUrl = $('#test_url').val();
        const nonce = $('#single-test-form input[name="techops_visual_testing_nonce"]').val();
        const $btn = $(this);
        const $resultPara = $('#test-connection-result');
        if ($resultPara.length === 0) {
            $btn.parent().append('<p id="test-connection-result" style="margin-top:10px;"></p>');
        }
        $btn.prop('disabled', true).text('Testing...');
        $('#test-connection-result').removeClass('test-success test-fail').css('color', '').text('Testing connection...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'techops_test_connection',
                reference_url: referenceUrl,
                test_url: testUrl,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-connection-result').text('Test Connection Successful: Both URLs are accessible.').css({color: '#155724', background: '#d4edda', padding: '8px', 'border-radius': '4px', 'margin-top': '10px'}).addClass('test-success');
                } else {
                    $('#test-connection-result').text('Test Connection Failed: ' + (response.data && response.data.message ? response.data.message : 'Unknown error')).css({color: '#721c24', background: '#f8d7da', padding: '8px', 'border-radius': '4px', 'margin-top': '10px'}).addClass('test-fail');
                }
            },
            error: function() {
                $('#test-connection-result').text('An error occurred while testing the connection.').css({color: '#721c24', background: '#f8d7da', padding: '8px', 'border-radius': '4px', 'margin-top': '10px'}).addClass('test-fail');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Test Connection');
            }
        });
    });

    // Site-wide Audit form submission
    $('#site-wide-audit-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $button = $('#run-site-wide-audit');
        $button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'techops_run_site_wide_audit',
                'techops_visual_testing_nonce': $form.find('input[name="techops_visual_testing_nonce"]').val(),
                reference_sitemap_url: $('#reference_sitemap_url').val(),
                test_sitemap_url: $('#test_sitemap_url').val(),
                audit_id: 'site-wide-audit-' + Date.now() // Unique ID for the audit
            },
            success: function(response) {
                if (response.success) {
                    alert('Site-wide audit initiated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while running the site-wide audit');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Test Sitemap (Site-wide Audit)
    $('#test-sitemap').on('click', function(e) {
        e.preventDefault();
        alert('Test Sitemap functionality needs to be implemented in PHP.');
        // You would typically make an AJAX call here to test the sitemap URLs
    });

    // Existing screenshot comparison (if still needed, otherwise remove)
    // Keeping this for now, as it was in the original admin-page.php
    $('#compare-screenshots').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'techops_compare_screenshots',
                nonce: '<?php echo wp_create_nonce('techops_visual_testing'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Comparison completed successfully');
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
});
</script>