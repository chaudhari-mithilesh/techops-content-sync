<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>TechOps Content Sync Functionality</h1>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Plugin & Theme Comparison</h2>
            <button id="fetch-compare-button" class="button button-primary">Fetch and Compare</button>
            <button id="run-automation-button" class="button button-primary">Run Full Automation</button>
        </div>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select id="bulk-action-selector-top">
                    <option value="-1">Bulk Actions</option>
                    <option value="install">Install</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="update">Update</option>
                    <option value="sync">Sync</option>
                </select>
                <input type="submit" class="button action" value="Apply">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th scope="col" class="manage-column column-name">Name</th>
                    <th scope="col" class="manage-column column-version">Current Version</th>
                    <th scope="col" class="manage-column column-remote-version">Remote Version</th>
                    <th scope="col" class="manage-column column-status">Status</th>
                    <th scope="col" class="manage-column column-update-status">Update Status</th>
                    <th scope="col" class="manage-column column-actions">Actions</th>
                    <th scope="col" class="manage-column column-version-manager">Version Manager</th>
                </tr>
            </thead>
            <tbody id="comparison-table-plugins-body">
                <!-- Plugin content will be populated by JavaScript -->
            </tbody>
            <tbody id="comparison-table-themes-body">
                <!-- Theme content will be populated by JavaScript -->
            </tbody>
        </table>

        <div id="loading-indicator" style="display: none;">
            <span class="spinner is-active"></span>
            Loading data...
        </div>
    </div>
</div>
