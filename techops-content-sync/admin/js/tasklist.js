console.log('[TasklistUI Debug] Script file loaded and executing.'); // Debug log
// Tasklist UI Implementation
class TasklistUI {
    constructor() {
        this.selectedItems = new Set();
        this.authToken = null;
        this.currentSiteUrl = window.location.origin;
        this.remoteSiteUrl = '';
        this.plugins = {
            current: [],
            remote: []
        };
        this.themes = {
            current: [],
            remote: []
        };
        this.init();
    }

    init() {
        // Initialize bulk actions
        this.initBulkActions();
        
        // Initialize checkbox selection
        this.initCheckboxSelection();
        
        // Initialize fetch and compare button listener
        this.initFetchCompareButton();
        
        // Initialize action buttons
        this.initActionButtons();
        
        // Initialize run automation button
        this.initRunAutomationButton();
        
        // Automatically load data on page load
        this.loadData();
    }

    initRunAutomationButton() {
        const btn = document.getElementById('run-automation-button');
        if (btn) {
            btn.addEventListener('click', () => this.runFullAutomation());
        }
    }

    initFetchCompareButton() {
        const button = document.getElementById('fetch-compare-button');
        if (button) {
            button.addEventListener('click', () => {
                this.loadData(); // Load data when the button is clicked
            });
        }
    }

    initBulkActions() {
        const bulkActionButton = document.querySelector('.tablenav .button.action');
        if (bulkActionButton) {
            bulkActionButton.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default form submission
                const action = document.querySelector('#bulk-action-selector-top').value;
                if (action === '-1') {
                    this.showNotice('Please select a bulk action.', 'error');
                    return;
                }

                if (this.selectedItems.size === 0) {
                    this.showNotice('Please select items to perform the action.', 'error');
                    return;
                }

                this.performBulkAction(action);
            });
        }
    }

    /**
   * Orchestrates the three steps:
   *  1) Fetch & compare
   *  2) Sync plugins
   *  3) Update all plugins
   */
    async runFullAutomation() {
    try {
        // --- STEP 1: fetch & compare ---
        console.log('[Full Automation Debug] Running full automation…');
        await this.loadData();
        console.log('[Full Automation Debug] Data loaded successfully.');
        this.showNotice('Running comparison…', 'info');

        // --- STEP 2: sync Breeze plugin ---
        console.log('[Full Automation Debug] Syncing Breeze plugin…');
        this.showNotice('Syncing Breeze plugin…', 'info');
            
        const syncPromises = [];
            
        // Find Breeze in remote plugins
        const remoteBreeze = this.plugins.remote.find(p => p.slug === 'breeze');
        const localBreeze = this.plugins.current.find(p => p.slug === 'breeze');
            
        if (remoteBreeze) {
            console.log('[Full Automation Debug] Found remote Breeze:', remoteBreeze);
            console.log('[Full Automation Debug] Found local Breeze:', localBreeze);
                
            // If Breeze is not installed locally
            if (!localBreeze) {
                syncPromises.push(
                    this.performSingleAction('breeze', 'plugin', 'install')
                        .then(() => this.performSingleAction('breeze', 'plugin', remoteBreeze.active ? 'activate' : 'deactivate'))
                );
            }
            // If Breeze is installed but activation state differs
            else if (localBreeze && remoteBreeze.active !== localBreeze.active) {
                syncPromises.push(
                    this.performSingleAction('breeze', 'plugin', remoteBreeze.active ? 'activate' : 'deactivate')
                );
            }
            // If Breeze version differs
            else if (localBreeze && remoteBreeze.version !== localBreeze.version) {
                syncPromises.push(
                    this.performSingleAction('breeze', 'plugin', 'update-version', {
                        version: remoteBreeze.version
                    })
                );
            }
        }

        await Promise.all(syncPromises);
        this.showNotice('Breeze plugin sync complete.', 'success');
            
        // --- STEP 3: update Breeze if needed ---
        console.log('[Full Automation Debug] Updating Breeze plugin if needed…');
        this.showNotice('Updating Breeze plugin if needed…', 'info');
            
        const updatePromises = [];
        // Check if Breeze has an update available
        const breezeToUpdate = this.plugins.current.find(p => p.slug === 'breeze' && p.has_update);
        if (breezeToUpdate) {
            updatePromises.push(
                this.performSingleAction('breeze', 'plugin', 'update')
            );
        }
            
        await Promise.all(updatePromises);
        if (updatePromises.length > 0) {
            this.showNotice('Breeze plugin updated to latest version.', 'success');
        } else {
            this.showNotice('Breeze plugin is already up-to-date.', 'info');
        }
            
        // Finally, refresh the display
        await this.loadData();
    }
    catch (err) {
      console.error('Automation error:', err);
      this.showNotice('Automation failed: ' + err.message, 'error');
    }
    }

    async performBulkAction(action) {
        const itemsIdentifiers = Array.from(this.selectedItems);
        const results = [];
        
        // Fetch settings to get current site auth token
        const settings = await this.fetchSettings();
        if (!settings || !settings.current_site_auth) {
            this.showNotice('Current site credentials not available. Please configure settings.', 'error');
            return;
        }
        const currentSiteAuth = settings.current_site_auth;

        // console.log(`Performing bulk action: ${action} on ${itemsIdentifiers.length} items`); // Added log to show action and count

        for (const itemIdentifier of itemsIdentifiers) {
            const [type, slug] = itemIdentifier.split(':');
            
            try {
                // Determine endpoint based on item type and action
                let endpoint = `${type}s/${action}`;
                
                // Determine body parameter based on item type and action
                let body = {};

                // console.log(`Processing item for bulk action: ${itemIdentifier}, Action: ${action}, Type: ${type}, Slug: ${slug}`); // Detailed item log

                if (action === 'activate' || action === 'deactivate') {
                    // For activate/deactivate, use 'plugin' or 'theme' parameter
                    if (type === 'plugin') {
                        body = { plugin: slug };
                    } else if (type === 'theme') {
                        body = { theme: slug };
                    }
                    // console.log(`Body for ${action}:`, body); // Log specific body
                } else if (action === 'install') {
                     // For bulk install (assuming from .org), use 'slug' parameter
                     endpoint = `${type}s/install`; // Use the specific /install endpoint for bulk
                     body = { slug: slug };
                    //  console.log(`Body for ${action} (to ${endpoint}):`, body); // Log specific body
                } else if (action === 'update') {
                     // For bulk update (from WP.org), use 'slug' parameter
                     endpoint = `${type}s/update`; // Use the specific /update endpoint for bulk
                     body = { slug: slug };
                    //  console.log(`Body for ${action} (to ${endpoint}):`, body); // Log specific body
                } else if (action === 'sync') {
                     // For bulk sync (from remote site), use 'slug' and 'version' parameters
                     // Need to get the remote version from the checkbox data attribute
                     const checkbox = document.querySelector(`#cb-select-${type}-${slug}`);
                     const remoteVersion = checkbox ? checkbox.dataset.remoteVersion : null;

                     if (!remoteVersion) {
                         // console.error(`Skipping sync for ${itemIdentifier}: Missing remote version data.`);
                         results.push({ item: itemIdentifier, success: false, message: `Skipped: Missing remote version data.` });
                         continue; // Skip to the next item
                     }

                     // Reuse the install logic which calls update-version with version
                     endpoint = `${type}s/update-version`; // Use the specific /update-version endpoint
                     body = { slug: slug, version: remoteVersion };
                     // console.log(`Body for ${action} (to ${endpoint}):`, body); // Log specific body
                }
                else {
                    // Default case or other actions might use 'slug'
                    body = { slug: slug };
                    // console.log(`Body for default case (${action}):`, body); // Log specific body
                }

                const headers = {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': techopsContentSync.restNonce,
                        'Authorization': `Basic ${currentSiteAuth}` // Use current site auth
                    };
                 // console.log(`Making bulk action fetch request. Endpoint: ${techopsContentSync.restUrl + endpoint}, Method: POST, Body:`, JSON.stringify(body), 'Headers:', headers); // Added log
                const response = await fetch(techopsContentSync.restUrl + endpoint, {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify(body)
                });

                const result = await response.json();
                 if (response.ok && result.success) {
                    results.push({ item: itemIdentifier, success: true, message: result.message });
                } else {
                     const errorMessage = result.message || result.data.message || 'An unknown error occurred.';
                    results.push({ item: itemIdentifier, success: false, message: errorMessage });
                     // console.error(`Bulk action failed for ${itemIdentifier}: ${errorMessage}`, result); // Log detailed error for item
                }
            } catch (error) {
                results.push({ item: itemIdentifier, success: false, message: error.message });
                 // console.error(`Bulk action fetch error for ${itemIdentifier}:`, error); // Log fetch error for item
            }
        }

        // Show results
        const successCount = results.filter(r => r.success).length;
        const failCount = results.length - successCount;

        if (successCount > 0) {
             // Adjust success message for sync action
             const successMessageAction = action === 'sync' ? 'sync' : `${action}ed`;
            this.showNotice(`${successCount} items ${successMessageAction} successfully.`, 'success');
        }
        if (failCount > 0) {
             // Adjust fail message for sync action
             const failMessageAction = action === 'sync' ? 'sync' : action;
             const failMessages = results.filter(r => !r.success).map(r => `${r.item}: ${r.message}`).join('<br>');
            this.showNotice(`${failCount} items failed to ${failMessageAction}. Details:<br>${failMessages}`, 'error');
        }

        // Refresh data after bulk action
        this.loadData();
        
        // Clear selected items after action
        this.selectedItems.clear();
        document.querySelectorAll('#comparison-table-plugins-body input[type="checkbox"], #comparison-table-themes-body input[type="checkbox"]').forEach(checkbox => {
             checkbox.checked = false;
        });
         const selectAllCheckbox = document.querySelector('#cb-select-all-1');
         if(selectAllCheckbox) selectAllCheckbox.checked = false;
         const selectAllCheckbox2 = document.querySelector('#cb-select-all-2');
         if(selectAllCheckbox2) selectAllCheckbox2.checked = false;
    }

    initCheckboxSelection() {
        // Use event delegation for select-all checkboxes in both tables
         document.querySelectorAll('.wp-list-table thead .check-column input[type="checkbox"]').forEach(selectAll => {
             selectAll.addEventListener('change', (e) => {
                 // Find the tbody associated with this thead's table
                 const table = selectAll.closest('.wp-list-table');
                 if (table) {
                     const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
                     checkboxes.forEach(checkbox => {
                         checkbox.checked = e.target.checked;
                         this.updateSelectedItems(checkbox);
                     });
                 }
             });
         });

        // Use event delegation for individual checkboxes in both table bodies
        document.querySelectorAll('#comparison-table-plugins-body, #comparison-table-themes-body').forEach(tbody => {
             tbody.addEventListener('change', (e) => {
                 if (e.target.matches('input[type="checkbox"]')) {
                     this.updateSelectedItems(e.target);
                 }
             });
         });
    }

    updateSelectedItems(checkbox) {
        const row = checkbox.closest('tr');
        const slug = row.dataset.id;
        const type = row.dataset.type;
        const itemIdentifier = `${type}:${slug}`;
        
        if (checkbox.checked) {
            this.selectedItems.add(itemIdentifier);
        } else {
            this.selectedItems.delete(itemIdentifier);
        }
        // console.log('Selected items:', this.selectedItems);
    }

     async fetchSettings() {
         try {
             const response = await fetch(techopsContentSync.ajaxUrl, {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/x-www-form-urlencoded',
                 },
                 body: new URLSearchParams({
                     action: 'techops_get_settings',
                     nonce: techopsContentSync.nonce,
                 })
             });

             const result = await response.json();
             if (result.success) {
                 return result.data;
             } else {
                 this.showNotice(result.data.message || 'Error fetching settings.', 'error');
                 return null;
             }
         } catch (error) {
             this.showNotice('Error fetching settings: ' + error.message, 'error');
             return null;
         }
     }

    async fetchSiteData() {
        try {
            // Fetch settings (includes auth tokens and remote URL)
            console.log('[TasklistUI Debug] Fetching settings...'); // Debug log
            const settings = await this.fetchSettings();
            console.log('[TasklistUI Debug] Settings received:', settings); // Debug log
            
            if (!settings) {
                throw new Error('Settings not loaded. Cannot fetch comparison data.');
            }

            const currentSiteAuth = settings.current_site_auth;
            const remoteSiteAuth = settings.remote_site_auth;
            const remoteSiteUrl = settings.remote_site_url || ''; // Ensure it's a string

            // Fetch current site data (Plugins and Themes)
            const currentSiteHeaders = {
                'X-WP-Nonce': techopsContentSync.restNonce,
                'Authorization': `Basic ${currentSiteAuth}`
            };
            
            console.log('[TasklistUI Debug] Making current site API calls...'); // Debug log
            const currentPluginsPromise = fetch(techopsContentSync.restUrl + 'plugins/list', {
                headers: currentSiteHeaders
            }).then(async res => {
                console.log('[TasklistUI Debug] Current plugins response status:', res.status); // Debug log
                if (!res.ok) {
                    const errorText = await res.text();
                    throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                }
                const responseText = await res.text();
                const data = JSON.parse(responseText);
                return Array.isArray(data) ? data : [];
            });

            const currentThemesPromise = fetch(techopsContentSync.restUrl + 'themes/list', {
                headers: currentSiteHeaders
            }).then(async res => {
                console.log('[TasklistUI Debug] Current themes response status:', res.status); // Debug log
                if (!res.ok) {
                    const errorText = await res.text();
                    throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                }
                const responseText = await res.text();
                const data = JSON.parse(responseText);
                return Array.isArray(data) ? data : [];
            });

            console.log('[TasklistUI Debug] Waiting for current site data...'); // Debug log
            const [currentPlugins, currentThemes] = await Promise.all([currentPluginsPromise, currentThemesPromise]);
            console.log('[TasklistUI Debug] Current site data received:', { // Debug log
                plugins: currentPlugins,
                pluginsCount: currentPlugins.length
            });

            // Fetch remote site data (Plugins and Themes)
            const remoteSiteHeaders = {
                'X-WP-Nonce': techopsContentSync.restNonce,
                'Authorization': `Basic ${remoteSiteAuth}`
            };
            console.log('[TasklistUI Debug] Fetching remote site data from', remoteSiteUrl, '. Headers:', remoteSiteHeaders); // Debug log

            const remotePluginsPromise = fetch(remoteSiteUrl + '/wp-json/techops/v1/plugins/list', {
                headers: remoteSiteHeaders
            }).then(async res => {
                console.log('[TasklistUI Debug] Remote plugins response status:', res.status); // Debug log
                if (!res.ok) {
                    const errorText = await res.text();
                    throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                }
                const responseText = await res.text();
                const data = JSON.parse(responseText);
                return Array.isArray(data) ? data : [];
            });

            const remoteThemesPromise = fetch(remoteSiteUrl + '/wp-json/techops/v1/themes/list', {
                headers: remoteSiteHeaders
            }).then(async res => {
                console.log('[TasklistUI Debug] Remote themes response status:', res.status); // Debug log
                if (!res.ok) {
                    const errorText = await res.text();
                    throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                }
                const responseText = await res.text();
                const data = JSON.parse(responseText);
                return Array.isArray(data) ? data : [];
            });

            console.log('[TasklistUI Debug] Waiting for remote site data...'); // Debug log
            const [remotePlugins, remoteThemes] = await Promise.all([remotePluginsPromise, remoteThemesPromise]);
            console.log('[TasklistUI Debug] Remote site data received:', { // Debug log
                plugins: remotePlugins,
                pluginsCount: remotePlugins.length
            });

            return {
                currentPlugins,
                currentThemes,
                remotePlugins,
                remoteThemes
            };
        } catch (error) {
            console.error('[TasklistUI Debug] Error in fetchSiteData:', error); // Debug log
            throw error;
        }
    }

    async loadData() {
        console.log('[TasklistUI Debug] Starting loadData...'); // Debug log

        try {
            // Fetch settings (includes auth tokens and remote URL)
            console.log('[TasklistUI Debug] Fetching settings...'); // Debug log
            const settings = await this.fetchSettings();
            console.log('[TasklistUI Debug] Settings received:', settings); // Debug log
            
            if (!settings) {
                // console.error('Settings fetch failed or returned null');
                this.showNotice('Settings not loaded. Cannot fetch comparison data.', 'error');
                return;
            }

            const currentSiteAuth = settings.current_site_auth;
            const remoteSiteAuth = settings.remote_site_auth;
            const remoteSiteUrl = settings.remote_site_url || ''; // Ensure it's a string

            // console.log('Auth tokens and URL:', {
            //     currentSiteAuth: currentSiteAuth ? 'Present' : 'Missing',
            //     remoteSiteAuth: remoteSiteAuth ? 'Present' : 'Missing',
            //     remoteSiteUrl: remoteSiteUrl || 'Missing'
            // });

            // Proceed even if remote URL/auth are missing, comparison will just show local items

            // Step 1: Trigger WordPress update checks (optional, doesn't block data loading)
            console.log('[TasklistUI Debug] Attempting to trigger WordPress update checks...'); // Debug log
            const updateCheckPromise = fetch(techopsContentSync.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'techops_check_updates',
                    nonce: techopsContentSync.nonce,
                })
            }).then(async response => {
                 // Check if the response is OK (status 200-299)
                 if (!response.ok) {
                     const errorBody = await response.text();
                     // console.error('AJAX Error triggering update checks:', response.status, response.statusText, errorBody);
                     this.showNotice(`Warning: Failed to trigger update checks (Status: ${response.status}). See console for details.`, 'warning');
                     return Promise.reject(new Error(`HTTP error! status: ${response.status}`)); // Propagate error
                 }
                 // Attempt to parse JSON
                 try {
                     const result = await response.json();
                      if (result.success) {
                          // console.log('WordPress update checks triggered successfully.', result.data);
                      } else {
                          // console.error('Failed to trigger WordPress update checks.', result.data);
                          this.showNotice('Warning: Failed to refresh update information.' + (result.data ? (result.data.message || JSON.stringify(result.data)) : ''), 'warning');
                      }
                 } catch (jsonError) {
                      // console.error('JSON parsing error for update check response:', jsonError, 'Response:', await response.text());
                      this.showNotice('Warning: Received invalid response from update check.', 'warning');
                 }
            }).catch(error => {
                // This catch handles network errors or errors explicitly rejected in the previous .then()
                console.error('[TasklistUI Debug] Error during WordPress update check fetch:', error); // Debug log
                 // Avoid accessing error.message directly in case error is not a standard Error object
                 const errorMessage = error instanceof Error ? error.message : 'An unknown error occurred.';
                this.showNotice(`Warning: Error refreshing update information: ${errorMessage}`, 'warning');
            });

            // Do NOT await updateCheckPromise here. Allow it to run in the background.
            // The list endpoints now fetch update info directly if transients are stale.
            console.log('[TasklistUI Debug] Proceeding to fetch list data...'); // Debug log

            // Fetch current site data (Plugins and Themes)
            const currentSiteHeaders = {
                'X-WP-Nonce': techopsContentSync.restNonce,
                'Authorization': `Basic ${currentSiteAuth}`
            };
            // console.log('Fetching current site data. Headers:', currentSiteHeaders);
            // console.log('REST URL:', techopsContentSync.restUrl);
            
            console.log('[TasklistUI Debug] Making current site API calls...'); // Debug log
            try {
                const currentPluginsPromise = fetch(techopsContentSync.restUrl + 'plugins/list', {
                    headers: currentSiteHeaders
                }).then(async res => {
                    console.log('[TasklistUI Debug] Current plugins response status:', res.status); // Debug log
                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('[TasklistUI Debug] Current plugins API error:', { // Debug log
                            status: res.status,
                            statusText: res.statusText,
                            response: errorText
                        });
                        throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                    }
                    const responseText = await res.text();
                    console.log('[TasklistUI Debug] Raw Current plugins response text:', responseText); // Debug log
                    // Attempt to extract JSON part from the response text
                    try {
                        // Find the position of the first '[' or '{'
                        const jsonStartIndex = responseText.search(/[\[{]/);
                        if (jsonStartIndex !== -1) {
                            // Extract the potential JSON string from that point onwards
                            const potentialJsonString = responseText.substring(jsonStartIndex);
                            console.log('[TasklistUI Debug] Potential JSON string (Current plugins):', potentialJsonString); // Debug log
                            const data = JSON.parse(potentialJsonString);
                            console.log('[TasklistUI Debug] Parsed Current plugins data:', data); // Debug log
                             // Ensure the parsed data is an array (for plugin/theme lists)
                             if (Array.isArray(data)) {
                                return data;
                             } else {
                                 console.error('[TasklistUI Debug] Parsed data is not an array (Current plugins): ', data); // Debug log
                                 throw new Error('Invalid JSON response format from current plugins API: Expected array.');
                             }
                        } else {
                            console.error('[TasklistUI Debug] Could not find start of JSON in current plugins response:', responseText); // Debug log
                            throw new Error('Invalid JSON response from current plugins API: No JSON found.');
                        }
                    } catch (jsonError) {
                        console.error('[TasklistUI Debug] JSON parsing error for current plugins:', jsonError, 'Response text:', responseText); // Debug log
                        throw new Error('Failed to parse JSON from current plugins response: ' + jsonError.message);
                    }
                }).catch(error => {
                    console.error('[TasklistUI Debug] Error in current plugins fetch:', error); // Debug log
                    throw error;
                });

                const currentThemesPromise = fetch(techopsContentSync.restUrl + 'themes/list', {
                    headers: currentSiteHeaders
                }).then(async res => {
                    console.log('[TasklistUI Debug] Current themes response status:', res.status); // Debug log
                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('[TasklistUI Debug] Current themes API error:', { // Debug log
                            status: res.status,
                            statusText: res.statusText,
                            response: errorText
                        });
                        throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                    }
                    const responseText = await res.text();
                    console.log('[TasklistUI Debug] Raw Current themes response text:', responseText); // Debug log
                     // Attempt to extract JSON part from the response text
                    try {
                         // Find the position of the first '[' or '{'
                         const jsonStartIndex = responseText.search(/[\[{]/);
                         if (jsonStartIndex !== -1) {
                             // Extract the potential JSON string from that point onwards
                             const potentialJsonString = responseText.substring(jsonStartIndex);
                             console.log('[TasklistUI Debug] Potential JSON string (Current themes):', potentialJsonString); // Debug log
                             const data = JSON.parse(potentialJsonString);
                             console.log('[TasklistUI Debug] Parsed Current themes data:', data); // Debug log
                             // Ensure the parsed data is an array (for plugin/theme lists)
                             if (Array.isArray(data)) {
                                 return data;
                             } else {
                                  console.error('[TasklistUI Debug] Parsed data is not an array (Current themes): ', data); // Debug log
                                  throw new Error('Invalid JSON response format from current themes API: Expected array.');
                             }
                         } else {
                             console.error('[TasklistUI Debug] Could not find start of JSON in current themes response:', responseText); // Debug log
                             throw new Error('Invalid JSON response from current themes API: No JSON found.');
                         }
                    } catch (jsonError) {
                        console.error('[TasklistUI Debug] JSON parsing error for current themes:', jsonError, 'Response text:', responseText); // Debug log
                        throw new Error('Failed to parse JSON from current themes response: ' + jsonError.message);
                    }
                }).catch(error => {
                    console.error('[TasklistUI Debug] Error in current themes fetch:', error); // Debug log
                    throw error;
                });

                console.log('[TasklistUI Debug] Waiting for current site data...'); // Debug log
                const [currentPlugins, currentThemes] = await Promise.all([currentPluginsPromise, currentThemesPromise]);
                console.log('[TasklistUI Debug] Current site data received:', { // Debug log
                    plugins: currentPlugins,
                    themes: currentThemes,
                    pluginsCount: currentPlugins ? currentPlugins.length : 0,
                    themesCount: currentThemes ? currentThemes.length : 0 // Corrected typo here
                });

                // Fetch remote site data (Plugins and Themes)
                const remoteSiteHeaders = {
                    'X-WP-Nonce': techopsContentSync.restNonce,
                    'Authorization': `Basic ${remoteSiteAuth}`
                };
                console.log('[TasklistUI Debug] Fetching remote site data from', remoteSiteUrl, '. Headers:', remoteSiteHeaders); // Debug log

                console.log('[TasklistUI Debug] Making remote site API calls...'); // Debug log
                const remotePluginsPromise = fetch(remoteSiteUrl + '/wp-json/techops/v1/plugins/list', {
                    headers: remoteSiteHeaders
                }).then(async res => {
                    console.log('[TasklistUI Debug] Remote plugins response status:', res.status); // Debug log
                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('[TasklistUI Debug] Remote plugins API error:', { // Debug log
                            status: res.status,
                            statusText: res.statusText,
                            response: errorText
                        });
                        throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                    }
                    const responseText = await res.text();
                    console.log('[TasklistUI Debug] Raw Remote plugins response text:', responseText); // Debug log
                    // Attempt to extract JSON part from the response text
                    try {
                         // Find the position of the first '[' or '{'
                         const jsonStartIndex = responseText.search(/[\[{]/);
                         if (jsonStartIndex !== -1) {
                            // Extract the potential JSON string from that point onwards
                            const potentialJsonString = responseText.substring(jsonStartIndex);
                            console.log('[TasklistUI Debug] Potential JSON string (Remote plugins):', potentialJsonString); // Debug log
                            const data = JSON.parse(potentialJsonString);
                            console.log('[TasklistUI Debug] Parsed Remote plugins data:', data); // Debug log
                            // Ensure the parsed data is an array (for plugin/theme lists)
                             if (Array.isArray(data)) {
                                return data;
                             } else {
                                 console.error('[TasklistUI Debug] Parsed data is not an array (Remote plugins): ', data); // Debug log
                                 throw new Error('Invalid JSON response format from remote plugins API: Expected array.');
                             }
                         } else {
                             console.error('[TasklistUI Debug] Could not find start of JSON in remote plugins response:', responseText); // Debug log
                             throw new Error('Invalid JSON response from remote plugins API: No JSON found.');
                         }
                    } catch (jsonError) {
                        console.error('[TasklistUI Debug] JSON parsing error for remote plugins:', jsonError, 'Response text:', responseText); // Debug log
                        throw new Error('Failed to parse JSON from remote plugins response: ' + jsonError.message);
                    }
                }).catch(error => {
                    console.error('[TasklistUI Debug] Error in remote plugins fetch:', error); // Debug log
                    throw error;
                });

                const remoteThemesPromise = fetch(remoteSiteUrl + '/wp-json/techops/v1/themes/list', {
                    headers: remoteSiteHeaders
                }).then(async res => {
                    console.log('[TasklistUI Debug] Remote themes response status:', res.status); // Debug log
                    if (!res.ok) {
                        const errorText = await res.text();
                        console.error('[TasklistUI Debug] Remote themes API error:', { // Debug log
                            status: res.status,
                            statusText: res.statusText,
                            response: errorText
                        });
                        throw new Error(`HTTP error! status: ${res.status}, message: ${errorText}`);
                    }
                    const responseText = await res.text();
                    console.log('[TasklistUI Debug] Raw Remote themes response text:', responseText); // Debug log
                         // Attempt to extract JSON part from the response text
                    try {
                         // Find the position of the first '[' or '{'
                         const jsonStartIndex = responseText.search(/[\[{]/);
                         if (jsonStartIndex !== -1) {
                             // Extract the potential JSON string from that point onwards
                             const potentialJsonString = responseText.substring(jsonStartIndex);
                             console.log('[TasklistUI Debug] Potential JSON string (Remote themes):', potentialJsonString); // Debug log
                             const data = JSON.parse(potentialJsonString);
                             console.log('[TasklistUI Debug] Parsed Remote themes data:', data); // Debug log
                             // Ensure the parsed data is an array (for plugin/theme lists)
                             if (Array.isArray(data)) {
                                 return data;
                             } else {
                                  console.error('[TasklistUI Debug] Parsed data is not an array (Remote themes): ', data); // Debug log
                                  throw new Error('Invalid JSON response format from remote themes API: Expected array.');
                             }
                         } else {
                             console.error('[TasklistUI Debug] Could not find start of JSON in remote themes response:', responseText); // Debug log
                             throw new Error('Invalid JSON response from remote themes API: No JSON found.');
                         }
                    } catch (jsonError) {
                        console.error('[TasklistUI Debug] JSON parsing error for remote themes:', jsonError, 'Response text:', responseText); // Debug log
                        throw new Error('Failed to parse JSON from remote themes response: ' + jsonError.message);
                    }
                }).catch(error => {
                    console.error('[TasklistUI Debug] Error in remote themes fetch:', error); // Debug log
                    throw error;
                });

                console.log('[TasklistUI Debug] Waiting for remote site data...'); // Debug log
                const [remotePlugins, remoteThemes] = await Promise.all([remotePluginsPromise, remoteThemesPromise]);
                console.log('[TasklistUI Debug] Remote site data received:', { // Debug log
                    plugins: remotePlugins,
                    themes: remoteThemes,
                    pluginsCount: remotePlugins ? remotePlugins.length : 0,
                    themesCount: remoteThemes ? remoteThemes.length : 0
                });
                // Store remote site data
                this.plugins.remote = remotePlugins || [];
                this.themes.remote = remoteThemes || [];
                
                // Combine and render comparison

                // Combine and render comparison
                console.log('[TasklistUI Debug] Preparing data for comparison...'); // Debug log
                const currentItems = [
                    ...(currentPlugins || []).map(p => ({ ...p, type: 'plugin' })),
                    ...(currentThemes || []).map(t => ({ ...t, type: 'theme' }))
                ];
                const remoteItems = [
                    ...(remotePlugins || []).map(p => ({ ...p, type: 'plugin' })),
                    ...(remoteThemes || []).map(t => ({ ...t, type: 'theme' }))
                ];

                console.log('[TasklistUI Debug] Combined data for renderComparison:', { // Debug log
                    currentItemsCount: currentItems.length,
                    remoteItemsCount: remoteItems.length,
                    currentItems: currentItems.slice(0, 5), // Log a sample
                    remoteItems: remoteItems.slice(0, 5) // Log a sample
                });

                console.log('[TasklistUI Debug] Calling renderComparison...'); // Debug log
                this.renderComparison(currentItems, remoteItems);

            } catch (error) {
                console.error('[TasklistUI Debug] Error during API calls or data processing in loadData:', error); // Debug log
                // Pass specific error to showNotice if it's a network/API error
                const errorMessage = error instanceof Error ? error.message : 'An unknown API or processing error occurred.';
                this.showNotice('Error fetching comparison data: ' + errorMessage, 'error');
                // Do NOT re-throw, let the finally block handle loading indicator
            }

        } catch (error) {
            console.error('[TasklistUI Debug] Error in loadData (main catch):', error); // Debug log
            this.showNotice('Error loading data: ' + error.message, 'error');
        } finally {
            console.log('[TasklistUI Debug] loadData finished. Hiding loading indicator.'); // Debug log
            loadingIndicator.style.display = 'none';
        }
    }

    renderComparison(currentData, remoteData) {
        console.log('[TasklistUI Debug] Starting renderComparison...'); // Debug log
        // console.log('Starting renderComparison...', { // Commented out
        //     currentDataCount: currentData.length,
        //     remoteDataCount: remoteData.length,
        //     currentDataSample: currentData.slice(0, 5), // Log a sample
        //     remoteDataSample: remoteData.slice(0, 5) // Log a sample
        // });

        // Get references to the separate table bodies
        const pluginsTbody = document.querySelector('#comparison-table-plugins-body');
        const themesTbody = document.querySelector('#comparison-table-themes-body');

        if (!pluginsTbody || !themesTbody) {
            console.error('[TasklistUI Debug] One or both comparison table bodies not found!'); // Debug log
            // console.error('One or both comparison table bodies not found! Ensure HTML has #comparison-table-plugins-body and #comparison-table-themes-body.'); // Commented out
            // Fallback to a single body if only one is found, or return if none
            if (pluginsTbody) {
                 pluginsTbody.innerHTML = ''; // Clear if it exists
                 // Render all into plugins body as a fallback?
                 // For now, just log error and return if both are not found for proper separation
                 return;
            } else if (themesTbody) {
                 themesTbody.innerHTML = ''; // Clear if it exists
                 return; // Need both for separation
            } else {
                 console.error('[TasklistUI Debug] Neither plugins nor themes table body found. Cannot render.'); // Debug log
                 // console.error('Neither plugins nor themes table body found. Cannot render.'); // Commented out
                 return;
            }
        }
        
        // Clear existing rows from both tables
        pluginsTbody.innerHTML = '';
        themesTbody.innerHTML = '';

        // Create a map of remote data for easy lookup
        const remoteMap = new Map(remoteData.map(item => [item.slug + ':' + item.type, item]));
        console.log('[TasklistUI Debug] Remote map created with', remoteMap.size, 'items'); // Debug log
        // console.log('Remote map created with', remoteMap.size, 'items'); // Commented out

        // Combine current and remote slugs to ensure all items are listed
        const allSlugs = new Set([
            ...currentData.map(item => item.slug + ':' + item.type),
            ...remoteData.map(item => item.slug + ':' + item.type)
        ]);

        const sortedSlugs = Array.from(allSlugs).sort((a, b) => a.localeCompare(b));
        console.log('[TasklistUI Debug] Sorted slugs:', sortedSlugs); // Debug log
        console.log('[TasklistUI Debug] Number of unique slugs to render:', sortedSlugs.length); // Debug log
        // console.log('Sorted slugs:', sortedSlugs); // Commented out
        // console.log('Number of unique slugs to render:', sortedSlugs.length); // Commented out

        if (sortedSlugs.length === 0) {
            console.log('[TasklistUI Debug] No items to display.'); // Debug log
            // Optionally add messages to each table body indicating no items
            pluginsTbody.innerHTML = '<tr><td colspan="6">No plugins found.</td></tr>';
            themesTbody.innerHTML = '<tr><td colspan="6">No themes found.</td></tr>'; // Corrected closing tag
            return;
        }

        console.log('[TasklistUI Debug] Starting to render rows...'); // Debug log
        try { // Add try...catch around the loop
            sortedSlugs.forEach(slugWithType => {
                const [slug, type] = slugWithType.split(':');
                console.log(`[TasklistUI Debug] Processing item for rendering: ${type}:${slug}`); // Debug log

                const currentItem = currentData.find(item => item.slug === slug && item.type === type);
                const remoteItem = remoteMap.get(slugWithType);

                console.log('[TasklistUI Debug] Found items for row:', { // Debug log
                    slug: slug,
                    type: type,
                    currentItemExists: !!currentItem,
                    remoteItemExists: !!remoteItem,
                    currentItemVersion: currentItem ? currentItem.version : 'N/A',
                    remoteItemVersion: remoteItem ? remoteItem.version : 'N/A'
                });
                // console.log('Found items for row:', { // Commented out
                //     slug: slug,
                //     type: type,
                //     currentItemExists: !!currentItem,
                //     remoteItemExists: !!remoteItem,
                //     currentItemVersion: currentItem ? currentItem.version : 'N/A',
                //     remoteItemVersion: remoteItem ? remoteItem.version : 'N/A'
                // });

                const row = document.createElement('tr');
                row.dataset.id = slug;
                row.dataset.type = type;

                // Determine status
                let status = 'Unknown';
                let statusClass = '';

                if (!currentItem && remoteItem) {
                    status = 'Missing on Current';
                    statusClass = 'missing';
                } else if (currentItem && !remoteItem) {
                    status = 'Missing on Remote';
                    statusClass = 'missing';
                } else if (currentItem && remoteItem) {
                    if (currentItem.version !== remoteItem.version) {
                        status = 'Not Synced';
                        statusClass = 'needs-update';
                    } else {
                        status = 'Synced';
                        statusClass = 'synced';
                    }
                    if (currentItem.active) {
                        status += ', Active';
                    } else {
                        status += ', Inactive';
                    }
                } else {
                    status = 'Not Found';
                }

                row.className = statusClass;

                const rowHtml = `
                    <td class="check-column">
                        <input type="checkbox" id="cb-select-${type}-${slug}" data-slug="${slug}" data-type="${type}" ${remoteItem ? `data-remote-version="${remoteItem.version}"` : ''}>
                    </td>
                    <td class="column-name">
                        <strong>${currentItem ? currentItem.name : (remoteItem ? remoteItem.name + ' (Remote)' : slug)}</strong><br>
                        <em>Type: ${type}</em>
                    </td>
                    <td class="column-version">${currentItem ? currentItem.version : 'N/A'}</td>
                    <td class="column-remote-version">${remoteItem ? remoteItem.version : 'N/A'}</td>
                    <td class="column-sync-status">
                        <span class="status-${statusClass}">${status}</span>
                    </td>
                    <td class="column-update-status">
                        <span class="status-${currentItem && currentItem.has_update ? 'needs-update' : 'synced'}">
                            ${currentItem && currentItem.has_update ? `Update Available (${currentItem.update_version})` : 'Up to Date'}
                        </span>
                    </td>
                    <td class="column-actions">
                        ${this.getActionButtons(currentItem, remoteItem, slug, type)}
                    </td>
                    <td class="column-version-manager" id="version-manager-${type}-${slug}">
                        Loading versions...
                    </td>
                `;

                row.innerHTML = rowHtml;

                // Append row to the correct table body based on type
                if (type === 'plugin') {
                    pluginsTbody.appendChild(row);
                } else if (type === 'theme') {
                    themesTbody.appendChild(row);
                }
                console.log(`[TasklistUI Debug] Appended row for: ${type}:${slug}`); // Debug log

                // --- Debugging Logs Start ---
                console.log(`[TasklistUI Debug] Preparing to fetch versions for ${type}:${slug}`);
                // --- Debugging Logs End ---

                // Fetch and populate available versions for the new column
                this.fetchAvailableVersions(type, slug).then(versions => {
                    console.log(`[TasklistUI Debug] Received versions for ${type}:${slug}:`, versions); // Debug log
                    const versionManagerCell = row.querySelector(`#version-manager-${type}-${slug}`);
                    if (versionManagerCell) {
                        if (versions.length > 0) {
                            const selectElement = document.createElement('select');
                            selectElement.classList.add('version-select');
                            selectElement.dataset.slug = slug;
                            selectElement.dataset.type = type;

                            // Add a default option or the current version as the first option
                            const currentVersion = currentItem ? currentItem.version : 'N/A';
                            const defaultOption = document.createElement('option');
                            defaultOption.value = '';
                            defaultOption.textContent = `Select version (${currentVersion} current)`;
                            selectElement.appendChild(defaultOption);

                            versions.forEach(version => {
                                const option = document.createElement('option');
                                option.value = version;
                                option.textContent = version;
                                selectElement.appendChild(option);
                            });
                            versionManagerCell.innerHTML = ''; // Clear loading text
                            versionManagerCell.appendChild(selectElement);
                        } else {
                            versionManagerCell.textContent = 'No versions found';
                            console.log(`[TasklistUI Debug] No versions found for ${type}:${slug}`); // Debug log
                        }
                    }
                }).catch(error => {
                     console.error(`[TasklistUI Debug] Error populating versions for ${type}:${slug}:`, error);
                     const versionManagerCell = row.querySelector(`#version-manager-${type}-${slug}`);
                     if (versionManagerCell) {
                         versionManagerCell.textContent = 'Error loading versions';
                     }
                });

            }); // End of forEach loop

        } catch (e) { // Catch any errors within the forEach loop
            console.error('[TasklistUI Debug] Error during rendering loop:', e); // Debug log
            this.showNotice('An error occurred while rendering the table: ' + e.message, 'error');
        }

        console.log('[TasklistUI Debug] Finished renderComparison.'); // Debug log
        // console.log('Finished rendering rows'); // Commented out
    }

    getActionButtons(currentItem, remoteItem, slug, type) {
        const buttons = [];

        // Activate/Deactivate buttons (based on current status)
        if (currentItem && !currentItem.active) {
            buttons.push(`<button class="button activate" data-slug="${slug}" data-type="${type}">Activate</button>`);
        } else if (currentItem && currentItem.active) {
             buttons.push(`<button class="button deactivate" data-slug="${slug}" data-type="${type}">Deactivate</button>`);
        }

        // Sync button (based on Sync Status - if versions differ with remote)
        if (currentItem && remoteItem && currentItem.version !== remoteItem.version) {
             // Use a specific class like 'sync' for this action
            buttons.push(`<button class="button sync" data-slug="${slug}" data-type="${type}" data-remote-version="${remoteItem.version}">Sync</button>`);
        }

        // Update button (based on Update Status - if update available from WP.org)
        if (currentItem && currentItem.has_update) {
             // Keep the existing 'update' class for this action
            buttons.push(`<button class="button update" data-slug="${slug}" data-type="${type}">Update</button>`);
        }

        // Install button (based on Sync Status - if item not found locally but exists remotely)
        if (!currentItem && remoteItem) {
             // Ensure data-remote-version is present for the install call (which uses update-version endpoint)
             buttons.push(`<button class="button install" data-slug="${slug}" data-type="${type}" data-remote-version="${remoteItem.version}">Install</button>`);
        }

        // Add a download button if item exists on current site
         if (currentItem) {
             buttons.push(`<a href="${techopsContentSync.restUrl}${type}s/download/${slug}" class="button" download>Download</a>`);
         }

        return buttons.join(' ');
    }
    
     initActionButtons() {
         // Add event listeners for dynamically created action buttons in both tables
         document.querySelectorAll('#comparison-table-plugins-body, #comparison-table-themes-body').forEach(tbody => {
              tbody.addEventListener('click', async (e) => {
                 const target = e.target;
                 if (!target.classList.contains('button') && !target.classList.contains('sync') && !target.classList.contains('install') && !target.classList.contains('update') && !target.classList.contains('activate') && !target.classList.contains('deactivate')) {
                     return; // Not an action button
                 }

                 const slug = target.dataset.slug;
                 const type = target.dataset.type;
                 const action = target.classList.contains('sync') ? 'sync' : target.classList.contains('install') ? 'install' : target.classList.contains('update') ? 'update' : target.classList.contains('activate') ? 'activate' : target.classList.contains('deactivate') ? 'deactivate' : null;

                 if (!action) return; // Should not happen with the check above, but as a safeguard

                 if (action === 'activate') {
                     await this.performSingleAction(slug, type, 'activate');
                 } else if (action === 'deactivate') {
                     await this.performSingleAction(slug, type, 'deactivate');
                 } else if (action === 'update') {
                     // Update button now specifically means update from WP.org
                     await this.performSingleAction(slug, type, 'update');
                 } else if (action === 'install') {
                     // Install button still uses update-version endpoint with remote version
                     const remoteVersion = target.dataset.remoteVersion;
                     if (!remoteVersion) {
                          this.showNotice(`Cannot install ${type} ${slug}: Missing remote version data.`, 'error');
                          return;
                      }
                     await this.performSingleAction(slug, type, 'install', { remoteVersion: remoteVersion });
                 } else if (action === 'sync') {
                      // Sync button action: download from remote and install/update locally
                     const remoteVersion = target.dataset.remoteVersion; // Get remote version for potential update-version call
                     if (!remoteVersion) {
                          this.showNotice(`Cannot sync ${type} ${slug}: Missing remote version data.`, 'error');
                          return;
                     }
                     // Call the custom download endpoint from remote source, then potentially activate
                     // We need a new endpoint for syncing or reuse download_plugin_from_source/download_theme_from_source
                     // For now, let's assume we call update-version with the remote version
                      this.showNotice(`Syncing ${type}: ${slug}... (Attempting update-version to remote ${remoteVersion})`, 'info');
                     await this.performSingleAction(slug, type, 'install', { remoteVersion: remoteVersion }); // Reusing 'install' logic which uses update-version
                 }
             });
         });

        // Add event listener for version select dropdowns
        document.querySelectorAll('#comparison-table-plugins-body, #comparison-table-themes-body').forEach(tbody => {
             tbody.addEventListener('change', async (e) => {
                 const target = e.target;
                 if (target.classList.contains('version-select')) {
                     const slug = target.dataset.slug;
                     const type = target.dataset.type;
                     const selectedVersion = target.value;

                     if (selectedVersion) { // Only perform action if a version is selected (not the default option)
                         // Determine the action based on whether the item is currently installed
                         // For simplicity, we'll reuse the 'install' action which handles updates based on version
                         await this.performSingleAction(slug, type, 'install', { remoteVersion: selectedVersion });
                     }
                 }
             });
         });
     }

     async performSingleAction(slug, type, action, additionalParams = {}) {
         this.showNotice(`Performing ${action} on ${type}: ${slug}...`, 'info');
         const loadingIndicator = document.querySelector('#loading-indicator');
         loadingIndicator.style.display = 'block';

         try {
             const settings = await this.fetchSettings();
             if (!settings || !settings.current_site_auth) {
                 this.showNotice('Current site credentials not available.', 'error');
                 loadingIndicator.style.display = 'none';
                 return;
             }
              const currentSiteAuth = settings.current_site_auth;

             let endpoint = '';
             let method = 'POST';
             let body = {};
             let headers = {
                 'X-WP-Nonce': techopsContentSync.restNonce,
                 'Authorization': `Basic ${currentSiteAuth}`,
                 'Content-Type': 'application/json'
             };

             // console.log(`Preparing ${action} request for ${type} ${slug}`); // Debug log

             if (action === 'activate' || action === 'deactivate') {
                 endpoint = `${type}s/${action}`; // e.g., plugins/activate or themes/deactivate
                 if (type === 'plugin') {
                     body = { plugin: slug };
                 } else if (type === 'theme') {
                     body = { theme: theme }; // Corrected typo here
                 }
                 // console.log(`Making ${action} request to endpoint: ${endpoint}`, { body, headers }); // Debug log
             } else if (action === 'update') {
                  endpoint = `${type}s/update`; // e.g., plugins/update or themes/update
                  body = { slug: slug };
                  // console.log(`Making update request to endpoint: ${endpoint}`, { body, headers }); // Debug log
             } else if (action === 'install') {
                  // Changed: Call update-version endpoint with remote version
                 endpoint = `${type}s/update-version`; // e.g., plugins/update-version or themes/update-version
                 const remoteVersion = additionalParams.remoteVersion; // Get from passed parameters
                 body = { slug: slug, version: remoteVersion };

                 // Ensure current site auth is used for this call
                 headers['Authorization'] = `Basic ${currentSiteAuth}`;

                 // console.log(`Making Install (via update-version) request to endpoint: ${endpoint}`, { body, headers }); // Debug log

             }

             // console.log(`Making ${method} request to ${techopsContentSync.restUrl + endpoint}`); // Debug log
             const response = await fetch(techopsContentSync.restUrl + endpoint, {
                 method: method,
                 headers: headers,
                 body: JSON.stringify(body)
             });

             // console.log(`Response status: ${response.status}`); // Debug log
             const result = await response.json();
             // console.log(`Response data:`, result); // Debug log

             if (response.ok && result.success) {
                 this.showNotice(`${type} ${slug} ${action}ed successfully.`, 'success');
                 await this.loadData();
             } else {
                 const errorMessage = result.message || result.data.message || `Failed to ${action} ${type}: ${slug}`;
                 this.showNotice(`Error: ${errorMessage}`, 'error');
                 // console.error(`API Error during ${action} on ${type} ${slug}:`, result);
             }

         } catch (error) {
             this.showNotice(`Error performing ${action} on ${type} ${slug}: ${error.message}`, 'error');
              // console.error(`Fetch Error during ${action} on ${type} ${slug}:`, error);
         } finally {
             // console.log('loadData finished. Hiding loading indicator.'); // Added log
             loadingIndicator.style.display = 'none';
         }
     }

    showNotice(message, type = 'info') {
        // Clear existing notices
        document.querySelectorAll('.wrap > .notice').forEach(notice => notice.remove());

        const notice = document.createElement('div');
        notice.className = `notice notice-${type} is-dismissible`;
        notice.innerHTML = `<p>${message}</p>`;

        const container = document.querySelector('.wrap');
        container.insertBefore(notice, container.firstChild);

        // Auto-dismiss after 10 seconds for success/info, keep errors visible
        if (type !== 'error') {
             setTimeout(() => {
                 notice.remove();
             }, 10000); // Increased timeout
        }
    }

    async fetchAvailableVersions(type, slug) {
        console.log(`[TasklistUI Debug] Inside fetchAvailableVersions for ${type}:${slug}`); // Debug log
        try {
            // Need to define a new AJAX action in PHP for this
            console.log(`[TasklistUI Debug] Making AJAX call for versions for ${type}:${slug}`); // Debug log
            const response = await fetch(techopsContentSync.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'techops_get_available_versions', // Define this action in PHP
                    nonce: techopsContentSync.nonce,
                    item_type: type,
                    item_slug: slug,
                })
            });

            console.log(`[TasklistUI Debug] Received AJAX response status for ${type}:${slug}:`, response.status); // Debug log
            const result = await response.json();
            console.log(`[TasklistUI Debug] fetchAvailableVersions response for ${type}:${slug}:`, result); // Debug log
            if (response.ok && result.success && Array.isArray(result.data)) { // Check response.ok as well
                console.log(`[TasklistUI Debug] Successfully fetched versions for ${type}:${slug}:`, result.data); // Debug log
                return result.data; // Should be an array of version strings
            } else {
                 console.error(`[TasklistUI Debug] Failed to fetch available versions for ${type}:${slug}:`, result.data ? (result.data.message || result) : 'Unknown error or non-success response'); // Improved debug log
                return []; // Return empty array if no versions or error
            }
        } catch (error) {
            console.error(`[TasklistUI Debug] Catch block in fetchAvailableVersions for ${type}:${slug}:`, error); // Debug log
            return []; // Return empty array on fetch error
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('[TasklistUI Debug] DOMContentSycn Loaded, initializing TasklistUI'); // Debug log
    new TasklistUI();
}); 