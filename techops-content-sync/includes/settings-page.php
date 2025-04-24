<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('techops_content_sync_settings');
        do_settings_sections('techops_content_sync_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="github_token"><?php _e('GitHub Personal Access Token', 'techops-content-sync'); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="github_token" 
                           name="techops_github_token" 
                           value="<?php echo esc_attr(get_option('techops_github_token')); ?>" 
                           class="regular-text"
                    />
                    <p class="description">
                        <?php _e('Enter your GitHub Personal Access Token. This token will be used to authenticate with the GitHub API.', 'techops-content-sync'); ?>
                        <a href="https://github.com/settings/tokens" target="_blank"><?php _e('Generate a new token', 'techops-content-sync'); ?></a>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_activate"><?php _e('Auto Activate', 'techops-content-sync'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="auto_activate" 
                           name="techops_sync_preferences[auto_activate]" 
                           value="1" 
                           <?php checked(1, get_option('techops_sync_preferences')['auto_activate'] ?? 1); ?>
                    />
                    <p class="description">
                        <?php _e('Automatically activate plugins and themes after installation.', 'techops-content-sync'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="backup_before_sync"><?php _e('Backup Before Sync', 'techops-content-sync'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="backup_before_sync" 
                           name="techops_sync_preferences[backup_before_sync]" 
                           value="1" 
                           <?php checked(1, get_option('techops_sync_preferences')['backup_before_sync'] ?? 1); ?>
                    />
                    <p class="description">
                        <?php _e('Create a backup before syncing plugins and themes.', 'techops-content-sync'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="sync_interval"><?php _e('Sync Interval', 'techops-content-sync'); ?></label>
                </th>
                <td>
                    <select id="sync_interval" name="techops_sync_preferences[sync_interval]">
                        <option value="hourly" <?php selected('hourly', get_option('techops_sync_preferences')['sync_interval'] ?? 'hourly'); ?>>
                            <?php _e('Hourly', 'techops-content-sync'); ?>
                        </option>
                        <option value="twicedaily" <?php selected('twicedaily', get_option('techops_sync_preferences')['sync_interval'] ?? 'hourly'); ?>>
                            <?php _e('Twice Daily', 'techops-content-sync'); ?>
                        </option>
                        <option value="daily" <?php selected('daily', get_option('techops_sync_preferences')['sync_interval'] ?? 'hourly'); ?>>
                            <?php _e('Daily', 'techops-content-sync'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('How often to check for updates.', 'techops-content-sync'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div> 