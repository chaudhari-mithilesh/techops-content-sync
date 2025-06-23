jQuery(document).ready(function($) {
    // Handle tab switching
    $('.vrt-tab').on('click', function() {
        $('.vrt-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.vrt-tab-content').removeClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });

    // Handle test connection button click
    $('#vrt-test-connection').on('click', function(e) {
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
            action: 'vrt_test_connection',
            nonce: vrtSingleTestData.nonce,
            reference_url: $form.find('#single-test-reference-url').val(),
            test_url: $form.find('#single-test-test-url').val(),
            reference_auth_username: $form.find('#single-test-reference-auth-username').val(),
            reference_auth_password: $form.find('#single-test-reference-auth-password').val(),
            test_auth_username: $form.find('#single-test-test-auth-username').val(),
            test_auth_password: $form.find('#single-test-test-auth-password').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vrtSingleTestData.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html(response.data.message).show();
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
                $result.removeClass('success').addClass('error').html('An error occurred while testing the connection.').show();
            },
            complete: function() {
                $loading.hide();
            }
        });
    });

    // Handle form submission
    $('#vrt-single-test-form').on('submit', function(e) {
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
            action: 'vrt_run_single_test',
            nonce: vrtSingleTestData.nonce,
            reference_url: $form.find('#single-test-reference-url').val(),
            test_url: $form.find('#single-test-test-url').val(),
            reference_auth_username: $form.find('#single-test-reference-auth-username').val(),
            reference_auth_password: $form.find('#single-test-reference-auth-password').val(),
            test_auth_username: $form.find('#single-test-test-auth-username').val(),
            test_auth_password: $form.find('#single-test-test-auth-password').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: vrtSingleTestData.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html(response.data.message).show();
                    
                    // Show report button if report URL is available
                    if (response.data.report_url) {
                        $('#vrt-view-report a').attr('href', response.data.report_url);
                        $('#vrt-report-buttons').show();
                        $('#vrt-view-report').show();
                    }
                } else {
                    $result.removeClass('success').addClass('error').html(response.data).show();
                }
            },
            error: function() {
                $result.removeClass('success').addClass('error').html('An error occurred while running the test.').show();
            },
            complete: function() {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Remove the URL input event handler since we don't want to show auth fields automatically
}); 