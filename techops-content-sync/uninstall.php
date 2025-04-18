<?php
// If uninstall.php is not called by WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('techops_content_sync_rate_limit');

// Clean up any temporary files
$temp_dir = get_temp_dir();
$files = glob($temp_dir . '*.zip');
foreach ($files as $file) {
    // Only delete files that match our plugin's pattern
    if (strpos(basename($file), 'plugin-') === 0 || strpos(basename($file), 'theme-') === 0) {
        @unlink($file);
    }
}

// Clear any transients we might have set
$transients = [
    'techops_content_sync_last_check',
    'techops_content_sync_plugins_list',
    'techops_content_sync_themes_list'
];

foreach ($transients as $transient) {
    delete_transient($transient);
} 