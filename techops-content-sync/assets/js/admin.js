jQuery(document).ready(function($) {
    const form = $('#git-repo-form');
    const statusDiv = $('#status-message');
    const logContainer = $('#operation-log');
    const logEntries = logContainer.find('.log-entries');
    
    function addLogEntry(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const entry = `
            <div class="log-entry log-${type}">
                <span class="log-time">[${timestamp}]</span>
                <span class="log-message">${message}</span>
            </div>
        `;
        logEntries.append(entry);
        logContainer.show();
        logEntries.scrollTop(logEntries[0].scrollHeight);
    }
    
    function showMessage(message, type = 'info') {
        statusDiv.html(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
            </div>
        `);
        addLogEntry(message, type);
    }
    
    function showError(message) {
        showMessage(message, 'error');
    }
    
    function showSuccess(message) {
        showMessage(message, 'success');
    }
    
    function showLoading() {
        form.find('button[type="submit"]').prop('disabled', true);
        showMessage('Processing request... Please wait.', 'info');
    }
    
    function hideLoading() {
        form.find('button[type="submit"]').prop('disabled', false);
    }
    
    function refreshRecentOperations() {
        $.ajax({
            url: techops_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'techops_get_recent_operations'
            },
            success: function(response) {
                if (response.success) {
                    $('#recent-operations').html(response.data);
                }
            }
        });
    }

    form.on('submit', function(e) {
        e.preventDefault();
        
        const repoUrl = $('#repo-url').val().trim();
        const folderPath = $('#folder-path').val().trim();
        
        if (!repoUrl) {
            showError('Please enter a Git repository URL');
            return;
        }
        
        // Clear previous logs
        logEntries.empty();
        logContainer.show();
        showLoading();
        
        addLogEntry('Starting repository validation...', 'info');
        
        // First validate the repository URL
        $.ajax({
            url: techops_ajax.api_url + 'techops/v1/github/repository/info',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', techops_ajax.nonce);
            },
            data: {
                repository_url: repoUrl,
                include_branches: true
            },
            success: function(response) {
                addLogEntry('Repository validated successfully', 'success');
                addLogEntry('Starting download process...', 'info');
                
                // Now start the sync process
                $.ajax({
                    url: techops_ajax.api_url + 'techops/v1/github/sync/start',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', techops_ajax.nonce);
                    },
                    data: {
                        repository: {
                            url: repoUrl,
                            path: folderPath
                        }
                    },
                    success: function(syncResponse) {
                        if (syncResponse.sync_id) {
                            addLogEntry(`Sync process started with ID: ${syncResponse.sync_id}`, 'info');
                            checkSyncStatus(syncResponse.sync_id);
                        } else {
                            showError('Failed to start sync process');
                            addLogEntry('Sync process failed to start', 'error');
                            hideLoading();
                        }
                    },
                    error: function(xhr, status, error) {
                        const errorMsg = xhr.responseJSON?.message || error;
                        showError('Failed to start sync: ' + errorMsg);
                        addLogEntry('Sync process failed: ' + errorMsg, 'error');
                        hideLoading();
                    }
                });
            },
            error: function(xhr, status, error) {
                const errorMsg = xhr.responseJSON?.message || error;
                showError('Invalid repository: ' + errorMsg);
                addLogEntry('Repository validation failed: ' + errorMsg, 'error');
                hideLoading();
            }
        });
    });
    
    function checkSyncStatus(syncId) {
        addLogEntry('Checking sync status...', 'info');
        
        $.ajax({
            url: techops_ajax.api_url + `techops/v1/github/sync/status/${syncId}`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', techops_ajax.nonce);
            },
            success: function(response) {
                if (response.status === 'completed') {
                    showSuccess('Sync completed successfully!');
                    addLogEntry('Sync process completed successfully', 'success');
                    hideLoading();
                    refreshRecentOperations();
                } else if (response.status === 'failed') {
                    showError('Sync failed: ' + response.error_message);
                    addLogEntry('Sync process failed: ' + response.error_message, 'error');
                    hideLoading();
                    refreshRecentOperations();
                } else {
                    // Still in progress, check again in 2 seconds
                    addLogEntry(`Current status: ${response.status}`, 'info');
                    setTimeout(() => checkSyncStatus(syncId), 2000);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = xhr.responseJSON?.message || error;
                showError('Failed to check sync status: ' + errorMsg);
                addLogEntry('Status check failed: ' + errorMsg, 'error');
                hideLoading();
            }
        });
    }
}); 