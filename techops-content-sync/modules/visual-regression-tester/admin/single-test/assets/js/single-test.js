jQuery(document).ready(function($) {
    // Tab switching for nav-tab
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-panel').hide();
        $('#' + $(this).attr('id').replace('-tab', '-panel')).show();
    });

    // Initial display
    $('#single-test-tab').click();

    // Test Connection button
    $('#test-connection').on('click', function(e) {
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

});