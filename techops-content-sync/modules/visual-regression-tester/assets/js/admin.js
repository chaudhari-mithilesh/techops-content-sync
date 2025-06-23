(function($) {
    'use strict';

    const TechOpsVisualTesting = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#run-visual-test').on('click', this.runVisualTest);
            $('#compare-screenshots').on('click', this.compareScreenshots);
            $('.view-test').on('click', this.viewTest);
        },

        runVisualTest: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'techops_run_visual_test',
                    nonce: techopsVisualTesting.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test completed successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while running the test');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        compareScreenshots: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'techops_compare_screenshots',
                    nonce: techopsVisualTesting.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Comparison completed successfully');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while comparing screenshots');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        viewTest: function(e) {
            e.preventDefault();
            
            const testId = $(this).data('id');
            
            // Implement test view logic here
            // This could open a modal or navigate to a detailed view
            console.log('Viewing test:', testId);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TechOpsVisualTesting.init();
    });

})(jQuery); 