jQuery(document).ready(function($) {
    const $form = $('#vrt-site-wide-audit-form');
    const $testConnectionBtn = $('#vrt-test-sitemap-connection');
    const $runAuditBtn = $('#vrt-run-audit');
    const $loading = $('#vrt-loading');
    const $results = $('#vrt-results');
    const $resultsContent = $('#vrt-results-content');

    // Handle test connection button click
    $testConnectionBtn.on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $form = $button.closest('form');
        const $loading = $form.find('.vrt-loading');
        const $result = $form.find('.vrt-result');
        
        // Show loading indicator
        $loading.show();
        $result.hide();
        
        // Get form data
        const formData = {
            action: 'vrt_test_sitemap_connection',
            nonce: vrtSiteWideAuditData.nonce,
            reference_sitemap: $form.find('#reference-sitemap').val(),
            test_sitemap: $form.find('#test-sitemap').val(),
            reference_auth_username: $form.find('#reference-auth-username').val(),
            reference_auth_password: $form.find('#reference-auth-password').val(),
            test_auth_username: $form.find('#test-auth-username').val(),
            test_auth_password: $form.find('#test-auth-password').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vrtSiteWideAuditData.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html(response.data.message).show();
                    
                    // Update sitemap URLs if they were modified
                    if (response.data.reference_sitemap) {
                        $form.find('#reference-sitemap').val(response.data.reference_sitemap);
                    }
                    if (response.data.test_sitemap) {
                        $form.find('#test-sitemap').val(response.data.test_sitemap);
                    }
                } else {
                    $result.removeClass('success').addClass('error').html(response.data.message).show();
                    
                    // Show auth fields if needed
                    if (response.data.reference_needs_auth) {
                        $form.find('.reference-auth-fields').show();
                    }
                    if (response.data.test_needs_auth) {
                        $form.find('.test-auth-fields').show();
                    }
                }
            },
            error: function() {
                $result.removeClass('success').addClass('error').html('An error occurred while testing the sitemap connection.').show();
            },
            complete: function() {
                $loading.hide();
            }
        });
    });

    // Handle form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $loading = $form.find('.vrt-loading');
        const $result = $form.find('.vrt-result');
        
        // Disable submit button and show loading indicator
        $button.prop('disabled', true);
        $loading.show();
        $result.hide();
        
        // Get form data
        const formData = {
            action: 'vrt_run_site_wide_audit',
            nonce: vrtSiteWideAuditData.nonce,
            reference_sitemap: $form.find('#reference-sitemap').val(),
            test_sitemap: $form.find('#test-sitemap').val(),
            reference_auth_username: $form.find('#reference-auth-username').val(),
            reference_auth_password: $form.find('#reference-auth-password').val(),
            test_auth_username: $form.find('#test-auth-username').val(),
            test_auth_password: $form.find('#test-auth-password').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vrtSiteWideAuditData.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html('Audit completed successfully. Report generated.').show();
                } else {
                    $result.removeClass('success').addClass('error').html(response.data).show();
                }
            },
            error: function() {
                $result.removeClass('success').addClass('error').html('An error occurred while running the audit.').show();
            },
            complete: function() {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Toggle auth fields based on sitemap URL input
    $('#reference-sitemap, #test-sitemap').on('input', function() {
        const $input = $(this);
        const $authFields = $input.closest('.vrt-form-group').next('.auth-fields');
        
        if ($input.val().includes('://')) {
            $authFields.show();
        } else {
            $authFields.hide();
        }
    });
}); 