jQuery(document).ready(function($) {
    const automation = {
    currentStep: 0,
    totalSteps: 3,
    progress: 0,
    dependencies: [],
    errors: [],
    results: {},
    tasklist: null,

    init: function() {
        this.tasklist = new TasklistUI();
        this.bindEvents();
        this.setupUI();
    },

        bindEvents: function() {
            $('#start-automation-button').on('click', this.startAutomation.bind(this));
        },

        setupUI: function() {
            this.$progressBar = $('.progress-bar');
            this.$currentStep = $('.current-step');
            this.$dependencyTree = $('.dependency-tree');
            this.$statusMessages = $('.status-messages');
            this.$resultSummary = $('.result-summary');
            this.$errorMessages = $('.error-messages');
            this.$loadingIndicator = $('#loading-indicator');
        },

        startAutomation: async function(e) {
            e.preventDefault();
            
            if (!this.canStart()) return;

            this.showLoading();
            
            try {
                await this.runAutomation();
            } catch (error) {
                this.showError(error);
            }
        },

        canStart: function() {
            // Add any pre-checks here
            return true;
        },

        showLoading: function() {
            this.$loadingIndicator.show();
            $('#start-automation-button').prop('disabled', true);
        },

        hideLoading: function() {
            this.$loadingIndicator.hide();
            $('#start-automation-button').prop('disabled', false);
        },

        updateProgress: function(step, message) {
            this.currentStep = step;
            this.progress = (step / this.totalSteps) * 100;
            
            this.$progressBar.css('width', this.progress + '%');
            this.$currentStep.text(`Step ${step}/${this.totalSteps}: ${message}`);
        },

        showError: function(error) {
            this.hideLoading();
            this.$errorMessages.html(`<div class="error">${error.message}</div>`);
            this.$errorMessages.show();
        },

        updateDependencyTree: function(dependencies) {
            try {
                console.log('[Automation Process] Starting updateDependencyTree');
                console.log('[Automation Process] Dependencies object type:', typeof dependencies);
                console.log('[Automation Process] Dependencies object keys:', Object.keys(dependencies));
                console.log('[Automation Process] Dependencies object value:', dependencies);

                // Validate dependencies object
                if (!dependencies || typeof dependencies !== 'object') {
                    console.error('[Automation Process] Invalid dependencies object type:', dependencies);
                    this.$dependencyTree.html('<div class="error">Invalid dependencies object type</div>');
                    return;
                }

                // Validate dependencies array
                const dependenciesArray = dependencies.dependencies;
                console.log('[Automation Process] Raw dependencies array:', dependenciesArray);

                if (!Array.isArray(dependenciesArray)) {
                    console.error('[Automation Process] Dependencies is not an array:', dependenciesArray);
                    console.error('[Automation Process] Dependencies type:', typeof dependenciesArray);
                    this.$dependencyTree.html('<div class="error">Dependencies is not an array</div>');
                    return;
                }

                // If no dependencies, show empty message
                if (dependenciesArray.length === 0) {
                    console.log('[Automation Process] No dependencies to display');
                    this.$dependencyTree.html('<div class="info">No dependencies found</div>');
                    return;
                }

                console.log('[Automation Process] Number of dependencies:', dependenciesArray.length);
                console.log('[Automation Process] First dependency data:', dependenciesArray[0]);

                // Clear existing tree and add header
                this.$dependencyTree.html('<h3>Dependency Tree:</h3>');
                const container = $('<div>').addClass('dependency-tree-container');
                this.$dependencyTree.append(container);

                // Render the dependency tree
                console.log('[Automation Process] Starting to render dependency tree');
                this.renderDependencyTree(dependencies, container);

                console.log('[Automation Process] Dependency tree rendering completed successfully');
            } catch (error) {
                console.error('[Automation Process] Error in updateDependencyTree:', error);
                console.error('[Automation Process] Full error stack:', error.stack);
                this.$dependencyTree.html(`<div class="error">Error rendering dependency tree: ${error.message}</div>`);
                throw error;
            }
        },

        renderDependencyTree: function(dependencies, container) {
            if (!dependencies || !dependencies.dependencies) {
                console.error('[Automation Process] Invalid dependencies structure:', dependencies);
                return;
            }

            const deps = dependencies.dependencies;
            if (!Array.isArray(deps)) {
                console.error('[Automation Process] Dependencies is not an array:', deps);
                return;
            }

            deps.forEach(dep => {
                const div = $('<div>').addClass('dependency-node').text(dep.name);
                if (dep.dependencies && dep.dependencies.length > 0) {
                    const subDiv = $('<div>').addClass('sub-dependencies');
                    this.renderDependencyTree({ dependencies: dep.dependencies }, subDiv);
                    div.append(subDiv);
                }
                container.append(div);
            });
        },

        runAutomation: async function() {
            try {
        console.log('[Automation Process] Starting automation process');
        
        // Step 1: Fetch and Compare
        this.updateProgress(1, 'Fetching and comparing plugins and themes...');
        console.log('[Automation Process] Step 1: Fetching and comparing plugins and themes');
        await this.tasklist.loadData();
        console.log('[Automation Process] Step 1 completed');

        // Step 2: Sync Plugins and Themes
        this.updateProgress(2, 'Syncing plugins and themes...');
        console.log('[Automation Process] Step 2: Syncing plugins and themes');
        
        // Get all items that need to be synced
        const pluginsToSync = this.tasklist.plugins.current
            .filter(plugin => plugin.remoteVersion && plugin.remoteVersion !== plugin.version)
            .map(plugin => `plugin:${plugin.slug}`);

        const themesToSync = this.tasklist.themes.current
            .filter(theme => theme.remoteVersion && theme.remoteVersion !== theme.version)
            .map(theme => `theme:${theme.slug}`);

        // Perform sync for plugins
        if (pluginsToSync.length > 0) {
            console.log(`[Automation Process] Syncing ${pluginsToSync.length} plugins`);
            await this.tasklist.performBulkAction('sync', pluginsToSync);
        }

        // Perform sync for themes
        if (themesToSync.length > 0) {
            console.log(`[Automation Process] Syncing ${themesToSync.length} themes`);
            await this.tasklist.performBulkAction('sync', themesToSync);
        }

        console.log('[Automation Process] Step 2 completed');

        // Step 3: Update All Plugins and Themes (only on staging site)
        //if (this.isStagingSite()) {
            this.updateProgress(3, 'Updating plugins and themes...');
            console.log('[Automation Process] Step 3: Updating plugins and themes');
            
            // Get all updatable plugins
            const pluginsToUpdate = this.tasklist.plugins.current
                .filter(plugin => plugin.updateAvailable)
                .map(plugin => `plugin:${plugin.slug}`);

            // Get all updatable themes
            const themesToUpdate = this.tasklist.themes.current
                .filter(theme => theme.updateAvailable)
                .map(theme => `theme:${theme.slug}`);

            // Perform updates for plugins
            if (pluginsToUpdate.length > 0) {
                console.log(`[Automation Process] Updating ${pluginsToUpdate.length} plugins`);
                await this.tasklist.performBulkAction('update', pluginsToUpdate);
            }

            // Perform updates for themes
            if (themesToUpdate.length > 0) {
                console.log(`[Automation Process] Updating ${themesToUpdate.length} themes`);
                await this.tasklist.performBulkAction('update', themesToUpdate);
            }

            console.log('[Automation Process] Step 3 completed');
        //}

        // Final Step
        this.updateProgress(3, 'Automation completed successfully');
        console.log('[Automation Process] Automation process completed successfully');
        this.showResults();
            } catch (error) {
                console.error('[Automation Process] Error in automation process:', error);
                throw error;
            }
        },

        fetchAndCompare: async function() {
            try {
                console.log('[Automation Process] Starting fetchAndCompare');
                
                // Initialize TasklistUI instance
                const tasklist = new TasklistUI();
                console.log('[Automation Process] Initializing TasklistUI');
                
                // Get both current and remote site data using tasklist
                await tasklist.loadData();
                console.log('[Automation Process] loadData completed. Current plugins:', tasklist.plugins.current.length, 'Remote plugins:', tasklist.plugins.remote.length);

                // Compare data
                const comparison = {
                    plugins: this.comparePlugins(tasklist.plugins.current, tasklist.plugins.remote),
                    themes: this.compareThemes(tasklist.themes.current, tasklist.themes.remote)
                };
                console.log('[Automation Process] Comparison completed. Plugin differences:', comparison.plugins.length, 'Theme differences:', comparison.themes.length);

                return comparison;
            } catch (error) {
                console.error('[Automation Process] Error in fetchAndCompare:', error);
                throw new Error('Error in fetchAndCompare: ' + error.message);
            }
        },

        syncPluginsWithDependencies: async function(dependenciesObject) {
            try {
                console.log('[Automation Process] Starting syncPluginsWithDependencies');
                console.log('[Automation Process] Dependencies object type:', typeof dependenciesObject);
                console.log('[Automation Process] Dependencies object keys:', Object.keys(dependenciesObject));
                console.log('[Automation Process] Dependencies object value:', JSON.stringify(dependenciesObject, null, 2));
                
                // Check if we received an object
                if (typeof dependenciesObject !== 'object' || dependenciesObject === null) {
                    console.error('[Automation Process] Invalid dependencies object type:', dependenciesObject);
                    throw new Error('Expected an object but received: ' + typeof dependenciesObject);
                }
                
                // Extract the dependencies array from the object
                const dependencies = dependenciesObject.dependencies;
                console.log('[Automation Process] Raw dependencies:', JSON.stringify(dependencies, null, 2));
                
                // Check if dependencies is an array
                if (!Array.isArray(dependencies)) {
                    console.error('[Automation Process] Dependencies is not an array:', dependencies);
                    console.error('[Automation Process] Dependencies type:', typeof dependencies);
                    throw new Error('Dependencies is not an array');
                }
                
                // If no plugins need processing, return empty results
                if (dependencies.length === 0) {
                    console.log('[Automation Process] No plugins need processing');
                    return [];
                }
                
                console.log('[Automation Process] Number of plugins to process:', dependencies.length);
                console.log('[Automation Process] First plugin data:', JSON.stringify(dependencies[0], null, 2));
                
                // Initialize TasklistUI instance
                const tasklist = new TasklistUI();
                console.log('[Automation Process] Initializing TasklistUI for sync');
                
                // Get current site auth token
                const settings = await tasklist.fetchSettings();
                if (!settings || !settings.current_site_auth) {
                    console.error('[Automation Process] No current site credentials available');
                    throw new Error('Current site credentials not available');
                }
                console.log('[Automation Process] Current site credentials fetched successfully');
                
                // Process plugins in dependency order
                const results = [];
                
                try {
                    console.log('[Automation Process] Starting to iterate over dependencies');
                    console.log('[Automation Process] Dependencies array:', JSON.stringify(dependencies, null, 2));
                    
                    // Check if dependencies array is empty
                    if (dependencies.length === 0) {
                        console.log('[Automation Process] Dependencies array is empty');
                        return [];
                    }

                    for (const plugin of dependencies) {
                        try {
                            console.log('[Automation Process] Processing plugin:', JSON.stringify(plugin, null, 2));
                            
                            if (!plugin || typeof plugin !== 'object') {
                                console.error('[Automation Process] Invalid plugin type:', plugin);
                                continue;
                            }
                            
                            if (!plugin.slug || !plugin.name) {
                                console.error('[Automation Process] Missing required plugin properties:', plugin);
                                continue;
                            }
                            
                            console.log('[Automation Process] Valid plugin:', {
                                name: plugin.name,
                                slug: plugin.slug
                            });
                            
                            // Check plugin state
                            const isInstalled = await this.isPluginInstalled(plugin.slug);
                            const isActive = await this.isPluginActive(plugin.slug);
                            console.log('[Automation Process] Plugin state:', plugin.name, 'Installed:', isInstalled, 'Active:', isActive);

                            // Process plugin based on its state
                            if (!isInstalled) {
                                console.log('[Automation Process] Installing plugin:', plugin.name);
                                const result = await tasklist.performBulkAction('install', plugin.slug);
                                results.push({
                                    plugin: plugin.name,
                                    action: 'install',
                                    success: result.success,
                                    message: result.message
                                });
                            } else if (!isActive) {
                                console.log('[Automation Process] Activating plugin:', plugin.name);
                                const result = await tasklist.performBulkAction('activate', plugin.slug);
                                results.push({
                                    plugin: plugin.name,
                                    action: 'activate',
                                    success: result.success,
                                    message: result.message
                                });
                            } else {
                                console.log('[Automation Process] Updating plugin:', plugin.name);
                                const result = await tasklist.performBulkAction('update', plugin.slug);
                                results.push({
                                    plugin: plugin.name,
                                    action: 'update',
                                    success: result.success,
                                    message: result.message
                                });
                            }
                            
                            console.log('[Automation Process] Plugin processed successfully:', plugin.name);
                        } catch (error) {
                            console.error('[Automation Process] Error processing plugin:', plugin.name, error);
                            results.push({
                                plugin: plugin.name,
                                action: 'error',
                                success: false,
                                message: error.message
                            });
                        }
                    }
                } catch (iterationError) {
                    console.error('[Automation Process] Error during iteration:', iterationError);
                    throw iterationError;
                }

                console.log('[Automation Process] All plugins processed. Results:', JSON.stringify(results, null, 2));
                return results;
            } catch (error) {
                console.error('[Automation Process] Error in syncPluginsWithDependencies:', error);
                console.error('[Automation Process] Full error stack:', error.stack);
                throw new Error('Error in syncPluginsWithDependencies: ' + error.message);
            }
        },

        updatePlugins: async function() {
            try {
                console.log('[Automation Process] Starting updatePlugins');
                
                // Verify staging site
                // if (!this.isStagingSite()) {
                //     console.error('[Automation Process] Not a staging site - updates not allowed');
                //     throw new Error('Plugin updates can only be performed on staging sites');
                // }
                console.log('[Automation Process] Verified staging site');

                // Initialize TasklistUI instance
                const tasklist = new TasklistUI();
                console.log('[Automation Process] Initializing TasklistUI for updates');
                
                // Get current site auth token
                const settings = await tasklist.fetchSettings();
                if (!settings || !settings.current_site_auth) {
                    console.error('[Automation Process] No current site credentials available');
                    throw new Error('Current site credentials not available');
                }
                console.log('[Automation Process] Current site credentials fetched successfully');

                // Get plugins that need updates
                const plugins = tasklist.plugins.current;
                const updates = this.getPluginUpdates(plugins);
                console.log('[Automation Process] Found plugins needing updates:', updates.length);

                // Process updates in dependency order
                const results = [];
                for (const plugin of updates) {
                    try {
                        console.log('[Automation Process] Updating plugin:', plugin.name);
                        const result = await tasklist.performBulkAction('sync', plugin.slug);
                        results.push({
                            plugin: plugin.name,
                            ...result[0]
                        });
                        console.log('[Automation Process] Plugin updated successfully:', plugin.name);
                    } catch (error) {
                        console.error('[Automation Process] Error updating plugin:', plugin.name, error);
                        results.push({
                            plugin: plugin.name,
                            success: false,
                            message: error.message
                        });
                    }
                }

                console.log('[Automation Process] All updates processed. Results:', results);
                return results;
            } catch (error) {
                console.error('[Automation Process] Error in updatePlugins:', error);
                throw new Error('Error in updatePlugins: ' + error.message);
            }
        },

        fetchAndCompare: async function() {
            try {
                // Initialize TasklistUI instance
                const tasklist = new TasklistUI();
                
                // Get both current and remote site data using tasklist
                await tasklist.loadData();

                // Compare data
                const comparison = {
                    plugins: this.comparePlugins(tasklist.plugins.current, tasklist.plugins.remote),
                    themes: this.compareThemes(tasklist.themes.current, tasklist.themes.remote)
                };

                return comparison;
            } catch (error) {
                throw new Error('Error in fetchAndCompare: ' + error.message);
            }
        },

        // Remove these helper functions since they're no longer needed
        getRemotePlugins: null,
        getRemoteThemes: null,

        analyzeDependencies: async function(comparison) {
            try {
                console.log('[Automation Process] Starting analyzeDependencies');
                console.log('[Automation Process] Comparison data:', comparison);
                
                // Build dependency tree
                console.log('[Automation Process] Building dependency tree...');
                const dependencies = this.buildDependencyTree(comparison);
                console.log('[Automation Process] Raw dependencies:', dependencies);
                
                // Sort plugins in dependency order
                console.log('[Automation Process] Sorting plugins by dependency order...');
                const sortedDependencies = this.resolveDependencyOrder(dependencies);
                console.log('[Automation Process] Sorted dependencies:', sortedDependencies);
                
                // Generate dependency tree
                console.log('[Automation Process] Generating dependency tree...');
                const dependencyTree = this.generateDependencyTree(sortedDependencies);
                console.log('[Automation Process] Generated dependency tree:', dependencyTree);
                
                return {
                    dependencies: sortedDependencies,
                    dependencyTree: dependencyTree
                };
            } catch (error) {
                console.error('[Automation Process] Error in analyzeDependencies:', error);
                console.error('[Automation Process] Full error stack:', error.stack);
                throw new Error('Error in analyzeDependencies: ' + error.message);
            }
        },

        syncPluginsWithDependencies: async function(dependenciesObject) {
            try {
                console.log('[Automation Process] Starting syncPluginsWithDependencies');
                console.log('[Automation Process] Dependencies object type:', typeof dependenciesObject);
                console.log('[Automation Process] Dependencies object:', JSON.stringify(dependenciesObject, null, 2));
                
                // Extract dependencies array from the object
                const dependencies = dependenciesObject.dependencies;
                console.log('[Automation Process] Raw dependencies array:', JSON.stringify(dependencies, null, 2));
                
                // Validate dependencies array
                if (!dependencies || !Array.isArray(dependencies)) {
                    console.error('[Automation Process] Invalid dependencies array:', dependencies);
                    throw new Error('Dependencies array must be an array');
                }
                
                // If no items need processing, return empty results
                if (dependencies.length === 0) {
                    console.log('[Automation Process] No items need processing');
                    return [];
                }
                
                // Initialize TasklistUI instance
                const tasklist = new TasklistUI();
                console.log('[Automation Process] Initialized TasklistUI');
                
                // Get current site auth token
                console.log('[Automation Process] Fetching site settings...');
                const settings = await tasklist.fetchSettings();
                console.log('[Automation Process] Settings received:', JSON.stringify(settings, null, 2));
                
                if (!settings || !settings.current_site_auth) {
                    console.error('[Automation Process] No site credentials available');
                    throw new Error('Current site credentials not available');
                }
                console.log('[Automation Process] Site credentials validated successfully');
                
                // Process items (plugins and themes) in dependency order
                const results = [];
                console.log('[Automation Process] Starting to process', dependencies.length, 'items');
                
                for (const item of dependencies) {
                    try {
                        console.log('[Automation Process] Processing item:', item.name);
                        
                        // Validate item data
                        if (!item || !item.slug || !item.name) {
                            console.error('[Automation Process] Invalid item data:', item);
                            results.push({
                                name: item.name || 'Unknown',
                                type: item.type || 'unknown',
                                action: 'error',
                                success: false,
                                message: 'Invalid item data'
                            });
                            continue;
                        }

                        // Process item based on type
                        if (item.type === 'plugin') {
                            // Check plugin state
                            console.log('[Automation Process] Checking plugin state:', item.name);
                            const isInstalled = await this.isPluginInstalled(item.slug);
                            const isActive = await this.isPluginActive(item.slug);
                            console.log('[Automation Process] Plugin state:', {
                                name: item.name,
                                installed: isInstalled,
                                active: isActive
                            });

                            // Process plugin based on its state
                            if (!isInstalled) {
                                console.log('[Automation Process] Installing plugin:', item.name);
                                const result = await tasklist.performBulkAction('install', item.slug);
                                console.log('[Automation Process] Install result:', result);
                                results.push({
                                    name: item.name,
                                    type: 'plugin',
                                    action: 'install',
                                    ...result
                                });
                            } else if (!isActive) {
                                console.log('[Automation Process] Activating plugin:', item.name);
                                const result = await tasklist.performBulkAction('activate', item.slug);
                                console.log('[Automation Process] Activate result:', result);
                                results.push({
                                    name: item.name,
                                    type: 'plugin',
                                    action: 'activate',
                                    ...result
                                });
                            } else {
                                console.log('[Automation Process] Updating plugin:', item.name);
                                const result = await tasklist.performBulkAction('update', item.slug);
                                console.log('[Automation Process] Update result:', result);
                                results.push({
                                    name: item.name,
                                    type: 'plugin',
                                    action: 'update',
                                    ...result
                                });
                            }
                        } else if (item.type === 'theme') {
                            // Check theme state
                            console.log('[Automation Process] Checking theme state:', item.name);
                            const isInstalled = await this.isThemeInstalled(item.slug);
                            const isActive = await this.isThemeActive(item.slug);
                            console.log('[Automation Process] Theme state:', {
                                name: item.name,
                                installed: isInstalled,
                                active: isActive
                            });

                            // Process theme based on its state
                            if (!isInstalled) {
                                console.log('[Automation Process] Installing theme:', item.name);
                                const result = await tasklist.performBulkAction('install-theme', item.slug);
                                console.log('[Automation Process] Install result:', result);
                                results.push({
                                    name: item.name,
                                    type: 'theme',
                                    action: 'install',
                                    ...result
                                });
                            } else if (!isActive) {
                                console.log('[Automation Process] Activating theme:', item.name);
                                const result = await tasklist.performBulkAction('activate-theme', item.slug);
                                console.log('[Automation Process] Activate result:', result);
                                results.push({
                                    name: item.name,
                                    type: 'theme',
                                    action: 'activate',
                                    ...result
                                });
                            } else {
                                console.log('[Automation Process] Updating theme:', item.name);
                                const result = await tasklist.performBulkAction('update-theme', item.slug);
                                console.log('[Automation Process] Update result:', result);
                                results.push({
                                    name: item.name,
                                    type: 'theme',
                                    action: 'update',
                                    ...result
                                });
                            }
                        } else {
                            console.error('[Automation Process] Unknown item type:', item.type);
                            results.push({
                                name: item.name,
                                type: item.type || 'unknown',
                                action: 'error',
                                success: false,
                                message: 'Unknown item type'
                            });
                        }
                        
                        console.log('[Automation Process] Item processed successfully:', item.name);
                    } catch (error) {
                        console.error('[Automation Process] Error processing item:', item.name, error);
                        results.push({
                            name: item.name,
                            type: item.type || 'unknown',
                            action: 'error',
                            success: false,
                            message: error.message
                        });
                    }
                }

                console.log('[Automation Process] All items processed. Results:', JSON.stringify(results, null, 2));
                return results;
            } catch (error) {
                console.error('[Automation Process] Error in syncPluginsWithDependencies:', error);
                console.error('[Automation Process] Full error stack:', error.stack);
                throw new Error('Error in syncPluginsWithDependencies: ' + error.message);
            }
        },

        // Helper function for tasklist bulk actions
        async performBulkAction(action, slug) {
            const tasklist = new TasklistUI();
            const settings = await tasklist.fetchSettings();
            if (!settings || !settings.current_site_auth) {
                throw new Error('Current site credentials not available');
            }

            // Temporarily select the item
            tasklist.selectedItems.add(`plugin:${slug}`);
            
            // Perform the action
            const result = await tasklist.performBulkAction(action);
            
            // Clear selection
            tasklist.selectedItems.clear();
            
            return result[0]; // Return the first result since we processed a single item
        },

        updatePlugins: async function() {
            try {
                // Verify staging site
                // if (!this.isStagingSite()) {
                //     throw new Error('Plugin updates can only be performed on staging sites');
                // }

                // Initialize TasklistUI instance
                const tasklist = new TasklistUI();
                
                // Get current site auth token
                const settings = await tasklist.fetchSettings();
                if (!settings || !settings.current_site_auth) {
                    throw new Error('Current site credentials not available');
                }

                // Get plugins that need updates
                const plugins = tasklist.plugins.current;
                const updates = this.getPluginUpdates(plugins);

                // Process updates in dependency order
                const results = [];
                for (const plugin of updates) {
                    try {
                        // Use tasklist's sync functionality
                        const result = await tasklist.performBulkAction('sync', plugin.slug);
                        results.push({
                            plugin: plugin.name,
                            ...result[0]
                        });
                    } catch (error) {
                        results.push({
                            plugin: plugin.name,
                            success: false,
                            message: error.message
                        });
                    }
                }

                return results;
            } catch (error) {
                throw new Error('Error in updatePlugins: ' + error.message);
            }
        },

        getSitePlugins: async function() {
            return await $.ajax({
                url: techopsContentSync.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techops_get_plugins',
                    nonce: techopsContentSync.nonce
                }
            });
        },

        getSiteThemes: async function() {
            return await $.ajax({
                url: techopsContentSync.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'techops_get_themes',
                    nonce: techopsContentSync.nonce
                }
            });
        },

        comparePlugins: function(current, remote) {
            const comparison = [];
            for (const [slug, plugin] of Object.entries(current)) {
                const remotePlugin = remote[slug];
                comparison.push({
                    slug,
                    name: plugin.name,
                    current_version: plugin.version,
                    remote_version: remotePlugin ? remotePlugin.version : null,
                    is_active: plugin.active,
                    requires: plugin.requires || []
                });
            }
            return comparison;
        },

        compareThemes: function(current, remote) {
            const comparison = [];
            for (const theme of current) {
                const remoteTheme = remote[theme.stylesheet];
                comparison.push({
                    stylesheet: theme.stylesheet,
                    name: theme.name,
                    current_version: theme.version,
                    remote_version: remoteTheme ? remoteTheme.version : null,
                    is_active: theme.stylesheet === get_stylesheet()
                });
            }
            return comparison;
        },

        buildDependencyTree: function(comparison) {
            const dependencies = {};
            for (const plugin of comparison.plugins) {
                dependencies[plugin.slug] = {
                    name: plugin.name,
                    version: plugin.current_version,
                    dependencies: plugin.requires
                };
            }
            return dependencies;
        },

        resolveDependencyOrder: function(dependencies) {
            const sorted = [];
            const visited = {};
            const tempMark = {};

            for (const plugin in dependencies) {
                if (!visited[plugin]) {
                    this.visit(plugin, dependencies, visited, tempMark, sorted);
                }
            }

            return sorted.reverse();
        },

        visit: function(plugin, dependencies, visited, tempMark, sorted) {
            if (tempMark[plugin]) {
                throw new Error(`Circular dependency detected for plugin: ${plugin}`);
            }

            if (!visited[plugin]) {
                tempMark[plugin] = true;
                
                const pluginDeps = dependencies[plugin].dependencies;
                for (const dep of pluginDeps) {
                    if (dependencies[dep]) {
                        this.visit(dep, dependencies, visited, tempMark, sorted);
                    }
                }

                visited[plugin] = true;
                delete tempMark[plugin];
                sorted.push(dependencies[plugin]);
            }
        },

        generateDependencyTree: function(sortedDependencies) {
            const tree = {};
            for (const plugin of sortedDependencies) {
                tree[plugin.name] = {
                    version: plugin.version,
                    dependencies: plugin.dependencies
                };
            }
            return tree;
        },

        getPluginInfo: async function(slug) {
            try {
                const response = await $.ajax({
                    url: techopsContentSync.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'fetch_and_compare',
                        nonce: techopsContentSync.nonce
                    }
                });
                return response.data.plugins.find(p => p.slug === slug);
            } catch (error) {
                throw new Error('Failed to get plugin info: ' + error.message);
            }
        },

        isPluginInstalled: async function(slug) {
            try {
                const plugin = await this.getPluginInfo(slug);
                return plugin ? true : false;
            } catch (error) {
                throw new Error('Failed to check plugin installation: ' + error.message);
            }
        },

        isPluginActive: async function(slug) {
            try {
                const plugin = await this.getPluginInfo(slug);
                return plugin ? plugin.is_active : false;
            } catch (error) {
                throw new Error('Failed to check plugin activation: ' + error.message);
            }
        },

        // isStagingSite: function() {
        //     const siteUrl = window.location.origin;
        //     return siteUrl.includes('staging.') || 
        //            siteUrl.includes('-staging.') ||
        //            siteUrl.includes('-dev.');
        // },

        getPluginUpdates: function(plugins) {
            return plugins.filter(plugin => plugin.update_available);
        },

        showResults: function() {
            this.$resultSummary.html(
                `<div class="success">
                    <h3>Automation Complete</h3>
                    <p>Status: ${this.results.status}</p>
                    <p>Plugins Updated: ${this.results.updatedPlugins}</p>
                </div>`
            );
            this.$resultSummary.show();
            this.hideLoading();
        }
    };

    automation.init();
});