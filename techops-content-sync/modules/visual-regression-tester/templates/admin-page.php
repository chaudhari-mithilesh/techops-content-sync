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
        <table class="wp-list-table widefat fixed striped" id="recent-tests-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Report</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="2">Loading...</td></tr>
            </tbody>
        </table>
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

    // Single Test form submission (custom API logic)
    $('#single-test-form').on('submit', function(e) {
        e.preventDefault();
        console.log('[Run Test] Form submit triggered');
        const $form = $(this);
        const $button = $('#run-single-test');
        let $result = $('#single-test-result');
        if ($result.length === 0) {
            $result = $('<div id="single-test-result" style="margin-top:10px;"></div>').insertAfter($form);
        }
        $button.prop('disabled', true);
        console.log('[Run Test] Button disabled');
        // Get form data
        const refUrl = $('#reference_url').val();
        const testUrl = $('#test_url').val();
        console.log('[Run Test] Reference URL:', refUrl);
        console.log('[Run Test] Test URL:', testUrl);
        const reqBody = {
            refUrl: refUrl,
            testUrl: testUrl,
            viewport: {
                width: 1280,
                height: 2041
            }
        };
        console.log('[Run Test] Request body:', reqBody);
        // Make API POST request
        fetch('http://localhost:3000/api/compare', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reqBody)
        })
        .then(response => {
            console.log('[Run Test] Raw response:', response);
            return response.json();
        })
        .then(data => {
            console.log('[Run Test] API Response:', data);
            const base = 'http://localhost:3000';
            if (typeof data.diffPercentage !== 'undefined') {
                // Visual diff result
                const refImgUrl = base + data.refImageUrl;
                const testImgUrl = base + data.testImageUrl;
                const diffImgUrl = base + data.diffImageUrl;
                $result.html(
                    '<b>Diff:</b> ' + data.diffPercentage + '%<br>' +
                    '<b>Reference:</b> <a href="' + refImgUrl + '" target="_blank">' + refImgUrl + '</a><br>' +
                    '<b>Test:</b> <a href="' + testImgUrl + '" target="_blank">' + testImgUrl + '</a><br>' +
                    '<b>Diff Image:</b> <a href="' + diffImgUrl + '" target="_blank">' + diffImgUrl + '</a>'
                ).css({background:'#f8f9fa',padding:'10px',border:'1px solid #ccc','border-radius':'6px'});
                console.log('[Run Test] Result displayed in UI');
            } else if (typeof data.reportUrl !== 'undefined') {
                // Report-only result
                const reportUrl = base + data.reportUrl;
                $result.html(
                    '<b>Visual Test Report:</b> <a href="' + reportUrl + '" target="_blank">' + reportUrl + '</a><br>' +
                    (typeof data.passed !== 'undefined' ? ('<b>Passed:</b> ' + (data.passed ? 'Yes' : 'No')) : '')
                ).css({background:'#f8f9fa',padding:'10px',border:'1px solid #ccc','border-radius':'6px'});
                console.log('[Run Test] Report URL displayed in UI');
            } else {
                $result.html('<span style="color:red;">Unexpected API response.</span>');
                console.warn('[Run Test] Unexpected API response:', data);
            }
        })
        .catch(error => {
            console.error('[Run Test] API Error:', error);
            $result.html('<span style="color:red;">API request failed.</span>');
        })
        .finally(() => {
            $button.prop('disabled', false);
            console.log('[Run Test] Button re-enabled');
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

    // Fetch and display recent tests from external API
    function loadRecentTests() {
        const $tbody = $('#recent-tests-table tbody');
        fetch('http://localhost:3000/api/tests/all')
            .then(res => res.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    $tbody.html('<tr><td colspan="2">No recent tests found.</td></tr>');
                    return;
                }
                // Show only the latest 10
                const latest = data.slice(0, 10);
                $tbody.empty();
                latest.forEach(test => {
                    const status = test.passed ? '<span style="color:green;font-weight:bold;">Passed</span>' : '<span style="color:red;font-weight:bold;">Failed</span>';
                    const reportUrl = 'http://localhost:3000' + test.reportUrl;
                    $tbody.append('<tr>' +
                        '<td>' + status + '</td>' +
                        '<td><a href="' + reportUrl + '" target="_blank">View Report</a></td>' +
                    '</tr>');
                });
            })
            .catch(err => {
                $tbody.html('<tr><td colspan="2" style="color:red;">Failed to load recent tests.</td></tr>');
                console.error('[Recent Tests] API error:', err);
            });
    }
    loadRecentTests();
});
</script>