jQuery(document).ready(function($) {

    // Handle form submission
    $('#vrt-site-wide-audit-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $loading = $form.find('.vrt-loading');
        const $result = $form.find('.vrt-result');
        const $button = $form.find('button[type="submit"]');
        
        // Reset UI
        $('.vrt-progress-container, .vrt-results-table').remove();
        $result.hide().empty();
        $loading.show();
        $button.prop('disabled', true);
        
        // Get form data
        const formData = new FormData($form[0]);
        formData.append('action', 'vrt_run_site_wide_audit_test');
        formData.append('nonce', vrtSiteWideAuditData.nonce);
        
        $.ajax({
            url: vrtSiteWideAuditData.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data && response.data.total_urls > 0) {
                    showProgressUI($form);
                    updateProgressBar(0, response.data.total_urls);
                    processBatch(response.data.audit_id, 0, response.data.total_urls);
                } else if (response.success && response.data && response.data.total_urls === 0) {
                    showError('No URLs to process.');
                } else {
                    showError(response.data || 'Unknown error.');
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to start audit: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    function showProgressUI($form) {
        $('.vrt-progress-container, .vrt-results-table').remove();
        const progressUI = `
            <div class="vrt-progress-container">
                <div class="vrt-progress-bar">
                    <div class="vrt-progress-bar-fill"></div>
                </div>
                <div class="vrt-progress-text">Processing: 0/0 (0%)</div>
            </div>
            <table class="vrt-results-table" style="display:none;">
                <thead>
                    <tr>
                        <th>Test URL</th>
                        <th>Reference URL</th>
                        <th>Status</th>
                        <th>Report</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;
        $form.after(progressUI);
    }

    function showError(message) {
        $('.vrt-loading').hide();
        $('.vrt-progress-container, .vrt-results-table').remove();
        const $result = $('.vrt-result');
        $result.html('<div class="vrt-notice vrt-notice-error">' + message + '</div>').show();
    }

    function showResultsTable() {
        $('.vrt-results-table').show();
    }

    // Process a batch of URLs
    function processBatch(auditId, processed, total) {
        const formData = new FormData();
        formData.append('action', 'vrt_process_audit_batch');
        formData.append('nonce', vrtSiteWideAuditData.nonce);
        formData.append('audit_id', auditId);

        // Add authentication credentials if they exist
        const referenceAuthUsername = document.getElementById('site-audit-reference-auth-username');
        const referenceAuthPassword = document.getElementById('site-audit-reference-auth-password');
        const testAuthUsername = document.getElementById('site-audit-test-auth-username');
        const testAuthPassword = document.getElementById('site-audit-test-auth-password');

        if (referenceAuthUsername && referenceAuthUsername.value) {
            formData.append('reference_auth_username', referenceAuthUsername.value);
        }
        if (referenceAuthPassword && referenceAuthPassword.value) {
            formData.append('reference_auth_password', referenceAuthPassword.value);
        }
        if (testAuthUsername && testAuthUsername.value) {
            formData.append('test_auth_username', testAuthUsername.value);
        }
        if (testAuthPassword && testAuthPassword.value) {
            formData.append('test_auth_password', testAuthPassword.value);
        }

        $.ajax({
            url: vrtSiteWideAuditData.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    updateProgressBar(data.processed, data.total);
                    updateResultsTable(data.batch_results);
                    if (data.processed > 0) showResultsTable();
                    if (!data.is_complete) {
                        setTimeout(() => processBatch(auditId, data.processed, data.total), 100);
                    } else {
                        const resultDiv = document.querySelector('.vrt-result');
                        if (resultDiv) {
                            resultDiv.innerHTML = '<div class="vrt-notice vrt-notice-success">Audit completed successfully!</div>';
                            resultDiv.style.display = 'block';
                        }
                        $('.vrt-loading').hide();
                    }
                } else {
                    showError(response.data || 'Unknown error during batch processing.');
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to process batch: ' + error);
            }
        });
    }

    function updateProgressBar(processed, total) {
        const progressBar = document.querySelector('.vrt-progress-bar-fill');
        const progressText = document.querySelector('.vrt-progress-text');
        const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
        
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }
        if (progressText) {
            progressText.textContent = `Processing: ${processed}/${total} (${percentage}%)`;
        }
    }

    function updateResultsTable(batchResults) {
        const tableBody = document.querySelector('.vrt-results-table tbody');
        if (!tableBody) return;
        batchResults.forEach(result => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${result.pair.test_url}</td>
                <td>${result.pair.reference_url || 'N/A'}</td>
                <td class="vrt-status-${result.status}">${result.status}</td>
                <td>${result.result && result.result.report_url ? `<a href="${result.result.report_url}" target="_blank">View Report</a>` : 'N/A'}</td>
                <td>${result.error || 'N/A'}</td>
            `;
            tableBody.appendChild(row);
        });
    }
}); 