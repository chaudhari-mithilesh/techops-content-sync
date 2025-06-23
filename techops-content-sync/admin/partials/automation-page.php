<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>Automation Dashboard</h1>

    <div class="card">
        <div class="card-header">
            <h2>Automation Process Sequence</h2>
        </div>
        <div class="card-body">
            <ol>
                <li><strong>Fetch and Compare</strong>: Collects information about plugins and themes from both sites without displaying it here.</li>
                <li><strong>Sync Plugins</strong>: Ensures all plugins match the remote site's state by:
                    <ul>
                        <li>Installing missing plugins</li>
                        <li>Activating/deactivating plugins to match remote state</li>
                        <li>Ensuring all plugins are in sync</li>
                    </ul>
                </li>
                <li><strong>Update All (Meant to be performed only on staging site.)</strong>: Once plugins are synced, updates all plugins to their latest versions.</li>
            </ol>
        </div>
    </div>

    <div class="card">

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Start Automation Process</h2>
            <button id="start-automation-button" class="button button-primary">Start Automation</button>
        </div>
        <div class="card-body">
            <div id="automation-progress" style="display: none;">
                <div class="progress-bar"></div>
                <div class="current-step"></div>
                <div class="dependency-tree"></div>
                <div class="status-messages"></div>
            </div>
            <div id="automation-result" style="display: none;">
                <div class="result-summary"></div>
                <div class="error-messages"></div>
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loading-indicator" style="display: none;">
        <p>Loading...</p>
    </div>
</div>
