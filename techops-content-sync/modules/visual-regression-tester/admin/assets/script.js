jQuery( document ).ready( function ( $ ) {
    // Get form and result elements
    const form = $( '#vrt-test-form' );
    const submitButton = $( '#submit' );
    const resultsContainer = $( '#vrt-results-container' );
    const loader = $( '#vrt-loader' );
    const results = $( '#vrt-results' );
    const reportButtons = $( '#vrt-report-buttons' );
    const viewReport = $( '#vrt-view-report' );

    // Hide results initially
    resultsContainer.hide();
    loader.hide();
    results.hide();
    reportButtons.hide();
    viewReport.hide();

    // Handle form submission
    form.on( 'submit', function ( e ) {
        e.preventDefault();

        // Show loader and hide previous results
        resultsContainer.show();
        loader.show();
        results.hide();
        reportButtons.hide();
        viewReport.hide();

        // Disable submit button
        submitButton.prop( 'disabled', true ).addClass( 'disabled' );

        // Get form data
        const referenceUrl = $( '#reference_url' ).val();
        const testUrl = $( '#test_url' ).val();
        const nonce = $( '#vrt_nonce' ).val();

        // Send AJAX request
        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vrt_run_test',
                reference_url: referenceUrl,
                test_url: testUrl,
                nonce: nonce
            },
            success: function ( response ) {
                // Hide loader
                loader.hide();

                if ( response.success ) {
                    // Show success message
                    results.show();
                    results.html( '<div class="notice notice-success"><p>' + response.data.message + '</p></div>' );

                    // Show report buttons and view report link
                    reportButtons.show();
                    viewReport.show();

                    // Update report link
                    $( '#vrt-view-report a' ).attr( 'href', response.data.report_url );
                } else {
                    // Show error message with mismatch details
                    results.show();
                    results.html( '<div class="notice notice-warning"><p>' + response.data.message + '</p><p>The report will show the exact differences found.</p></div>' );

                    // Show report buttons and view report link
                    reportButtons.show();
                    viewReport.show();

                    // Update report link
                    $( '#vrt-view-report a' ).attr( 'href', response.data.report_url );
                }
            },
            error: function ( xhr, status, error ) {
                // Hide loader
                loader.hide();

                // Show error message
                results.show();
                results.html( '<div class="notice notice-error"><p>Error: ' + error + '</p></div>' );
            },
            complete: function () {
                // Re-enable submit button
                submitButton.prop( 'disabled', false ).removeClass( 'disabled' );
            }
        } );
    } );

    // Tab switching functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        const target = $(this).attr('href');
        $('.tab-content').hide();
        $(target).show();
    });
    
    // Custom Page Audit form submission
    $('#vrt-test-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('input[type="submit"]');
        const $loader = $('#vrt-loader');
        const $results = $('#vrt-results');
        const $reportButtons = $('#vrt-report-buttons');
        const $viewReport = $('#vrt-view-report');
        
        // Disable submit button and show loader
        $submitButton.prop('disabled', true);
        $loader.show();
        $results.empty();
        $reportButtons.hide();
        
        // Prepare form data
        const formData = {
            action: 'vrt_run_test',
            nonce: $('#vrt_nonce').val(),
            reference_url: $('#reference_url').val(),
            test_url: $('#test_url').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $results.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    if (response.data.report_url) {
                        $viewReport.find('a').attr('href', response.data.report_url);
                        $viewReport.show();
                        $reportButtons.show();
                    }
                } else {
                    $results.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error"><p>An error occurred while running the test. Please try again.</p></div>');
            },
            complete: function() {
                $loader.hide();
                $submitButton.prop('disabled', false);
            }
        });
    });
    
    // Site-Wide Audit form submission
    $('#vrt-site-wide-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('input[type="submit"]');
        const $progress = $('#site-wide-progress');
        const $progressMessage = $('#site-wide-progress-message');
        const $progressBar = $('.progress-bar-fill');
        const $results = $('#site-wide-results');
        const $resultsMessage = $('#site-wide-results-message');
        const $reportButtons = $('#site-wide-report-buttons');
        const $viewReport = $('#site-wide-view-report');
        
        // Validate form
        const referenceSitemapUrl = $('#reference_sitemap_url').val();
        const testSitemapUrl = $('#test_sitemap_url').val();
        
        if (!referenceSitemapUrl || !testSitemapUrl) {
            $resultsMessage.html('<div class="notice notice-error"><p>Please provide both sitemap URLs.</p></div>');
            $results.show();
            return;
        }
        
        // Disable submit button and show progress
        $submitButton.prop('disabled', true);
        $progress.show();
        $progressBar.css('width', '0%');
        $results.hide();
        $reportButtons.hide();
        
        // Prepare form data
        const formData = {
            action: 'vrt_run_site_wide_test',
            nonce: $('#vrt_site_wide_nonce').val(),
            reference_sitemap_url: referenceSitemapUrl,
            test_sitemap_url: testSitemapUrl
        };
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (response.data.progress) {
                        // Update progress
                        $progressMessage.text(response.data.message);
                        $progressBar.css('width', response.data.progress + '%');
                        
                        if (response.data.completed) {
                            $progress.hide();
                            $resultsMessage.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            
                            if (response.data.report_url) {
                                $viewReport.find('a').attr('href', response.data.report_url);
                                $viewReport.show();
                                $reportButtons.show();
                            }
                            
                            $results.show();
                            $submitButton.prop('disabled', false);
                        }
                    } else {
                        // Final success response
                        $progress.hide();
                        $resultsMessage.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        
                        if (response.data.report_url) {
                            $viewReport.find('a').attr('href', response.data.report_url);
                            $viewReport.show();
                            $reportButtons.show();
                        }
                        
                        $results.show();
                        $submitButton.prop('disabled', false);
                    }
                } else {
                    $progress.hide();
                    $resultsMessage.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    $results.show();
                    $submitButton.prop('disabled', false);
                }
            },
            error: function() {
                $progress.hide();
                $resultsMessage.html('<div class="notice notice-error"><p>An error occurred while running the site-wide test. Please try again.</p></div>');
                $results.show();
                $submitButton.prop('disabled', false);
            }
        });
    });
} ); 