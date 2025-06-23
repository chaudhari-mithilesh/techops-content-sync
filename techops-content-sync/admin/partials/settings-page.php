<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get saved settings for current and remote sites
$current_site_settings = get_option('techops_current_site_settings', [
    'username' => '',
    'app_password' => '',
    'auth_token' => ''
]);

$remote_site_settings = get_option('techops_remote_site_settings', [
    'username' => '',
    'app_password' => '',
    'auth_token' => '',
    'remote_url' => ''
]);

// GitHub settings are no longer displayed on this page
// $github_settings = get_option('techops_github_settings', [
//     'github_username' => '',
//     'github_repo' => '',
//     'github_token' => '',
//     'github_file_path' => '',
//     'github_download_path' => ''
// ]);
?>

<div class="wrap">
    <h1>TechOps Content Sync Settings</h1>

    <?php settings_errors(); // Show saved settings messages ?>

    <div class="card">
        <h2>Current Site Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'techops_current_site_settings_group' ); ?>
            <?php do_settings_sections( 'techops-content-sync-settings' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="current_wp_username">WP Admin Username</label>
                    </th>
                    <td>
                        <input type="text" id="current_wp_username" name="techops_current_site_settings[username]"
                               class="regular-text" value="<?php echo esc_attr($current_site_settings['username']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="current_app_password">WordPress Application Password</label>
                    </th>
                    <td>
                        <input type="password" id="current_app_password" name="techops_current_site_settings[app_password]"
                               class="regular-text" value="<?php echo esc_attr($current_site_settings['app_password']); ?>">
                        <p class="description">Generate this in your WordPress profile under Application Passwords.</p>
                    </td>
                </tr>
                 <tr>
                    <th scope="row">Base64 Auth Token</th>
                    <td>
                         <code><?php echo esc_html($current_site_settings['auth_token']); ?></code>
                         <p class="description">This token is generated automatically on saving credentials.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Current Site Settings'); ?>
        </form>
    </div>

    <div class="card">
        <h2>Remote Site Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'techops_remote_site_settings_group' ); ?>
            <?php do_settings_sections( 'techops-content-sync-settings' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="remote_wp_username">WP Admin Username</label>
                    </th>
                    <td>
                        <input type="text" id="remote_wp_username" name="techops_remote_site_settings[username]"
                               class="regular-text" value="<?php echo esc_attr($remote_site_settings['username']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="remote_app_password">WordPress Application Password</label>
                    </th>
                    <td>
                        <input type="password" id="remote_app_password" name="techops_remote_site_settings[app_password]"
                               class="regular-text" value="<?php echo esc_attr($remote_site_settings['app_password']); ?>">
                         <p class="description">Generate this in the remote WordPress profile under Application Passwords.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Remote Site URL</th>
                    <td>
                        <input type="url" id="remote_site_url" name="techops_remote_site_settings[remote_url]"
                               class="regular-text" value="<?php echo esc_url($remote_site_settings['remote_url']); ?>">
                        <p class="description">Enter the full URL of the remote WordPress site (e.g., https://example.com)</p>
                    </td>
                </tr>
                <?php if (!empty($remote_site_settings['auth_token'])): ?>
                <tr>
                    <th scope="row">Base64 Auth Token</th>
                    <td>
                        <code><?php echo esc_html($remote_site_settings['auth_token']); ?></code>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            <?php submit_button('Save Remote Site Settings'); ?>
        </form>
    </div>

</div> 