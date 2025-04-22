jQuery(document).ready(function($) {
    $('#git-repo-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $statusMessage = $('#status-message');
        
        // Disable submit button and show loading state
        $submitButton.prop('disabled', true).text('Processing...');
        $statusMessage.html('<div class="notice notice-info"><p>Processing your request...</p></div>');
        
        // Get form data
        const formData = new FormData($form[0]);
        formData.append('action', 'techops_git_download');
        formData.append('techops_git_nonce', $('#techops_git_nonce').val());
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $statusMessage.html(
                        '<div class="notice notice-success">' +
                        '<p>' + response.data.message + '</p>' +
                        '</div>'
                    );
                } else {
                    $statusMessage.html(
                        '<div class="notice notice-error">' +
                        '<p>Error: ' + response.data.message + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $statusMessage.html(
                    '<div class="notice notice-error">' +
                    '<p>Error: ' + error + '</p>' +
                    '</div>'
                );
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false).text('Download and Install');
            }
        });
    });
}); 