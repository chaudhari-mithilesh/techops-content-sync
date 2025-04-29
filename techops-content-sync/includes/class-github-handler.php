<?php
/**
 * GitHub Handler Class
 *
 * @package TechOpsContentSync
 */

namespace TechOpsContentSync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GitHub_Handler
 */
class GitHub_Handler {
    /**
     * GitHub API base URL
     */
    const GITHUB_API_BASE = 'https://api.github.com';

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('github_file_content', array($this, 'github_file_content_shortcode'));
    }

    /**
     * Get GitHub settings
     *
     * @return array GitHub settings
     */
    private function get_settings() {
        return get_option('techops_github_settings', array(
            'github_username' => '',
            'github_repo' => '',
            'github_token' => '',
            'github_file_path' => '',
            'github_download_path' => 'git-content/'
        ));
    }

    /**
     * Fetch file content from GitHub
     *
     * @param string $file_path Optional. The path to the file within the repository.
     * @param string $github_token Optional. A personal access token for higher rate limits and private repos.
     * @return string|WP_Error The content of the file on success, or a WP_Error object on failure.
     */
    public function read_github_file($file_path = '', $github_token = '') {
        $settings = $this->get_settings();
        
        // Use provided file path or default from settings
        $file_path = !empty($file_path) ? $file_path : $settings['github_file_path'];
        
        // Use provided token or default from settings
        $github_token = !empty($github_token) ? $github_token : $settings['github_token'];
        
        if (empty($settings['github_username']) || empty($settings['github_repo'])) {
            return new \WP_Error('invalid_settings', 'GitHub username and repository must be configured in settings.');
        }

        if (empty($file_path)) {
            return new \WP_Error('invalid_file_path', 'File path must be provided or configured in settings.');
        }

        $api_url = self::GITHUB_API_BASE . "/repos/{$settings['github_username']}/{$settings['github_repo']}/contents/{$file_path}";

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3.raw',
                'User-Agent' => 'WordPress Plugin',
            ),
        );

        if (!empty($github_token)) {
            $args['headers']['Authorization'] = 'token ' . $github_token;
        }

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if (200 !== $response_code) {
            return new \WP_Error('github_api_error', 'GitHub API error: ' . $response_body, $response_code);
        }

        return $response_body;
    }

    /**
     * Download file from GitHub
     *
     * @return string|WP_Error Path to downloaded file on success, WP_Error on failure
     */
    public function download_file() {
        $settings = $this->get_settings();
        
        if (empty($settings['github_username']) || empty($settings['github_repo'])) {
            return new \WP_Error('invalid_settings', 'GitHub username and repository must be configured in settings.');
        }

        if (empty($settings['github_file_path'])) {
            return new \WP_Error('invalid_file_path', 'File path must be configured in settings.');
        }

        // Get file content
        $file_content = $this->read_github_file();
        if (is_wp_error($file_content)) {
            return $file_content;
        }

        // Create download directory if it doesn't exist
        $download_dir = TECHOPS_CONTENT_SYNC_DIR . $settings['github_download_path'];
        if (!is_dir($download_dir)) {
            if (!wp_mkdir_p($download_dir)) {
                return new \WP_Error('directory_error', 'Could not create download directory.');
            }
        }

        // Generate filename from path
        $filename = basename($settings['github_file_path']);
        if (empty($filename)) {
            $filename = 'downloaded_file.json';
        }

        // Save file
        $file_path = $download_dir . $filename;
        $bytes_written = file_put_contents($file_path, $file_content);

        if (false === $bytes_written) {
            return new \WP_Error('write_error', 'Could not write file to disk.');
        }

        // Log the download
        error_log(sprintf(
            'TechOps Content Sync: Downloaded file from GitHub - %s/%s/%s',
            $settings['github_username'],
            $settings['github_repo'],
            $settings['github_file_path']
        ));

        return $file_path;
    }

    /**
     * Shortcode to display or download GitHub file content
     *
     * @param array $atts Shortcode attributes.
     * @return string The content of the file or an error message.
     */
    public function github_file_content_shortcode($atts) {
        $settings = $this->get_settings();
        
        $atts = shortcode_atts(
            array(
                'path' => $settings['github_file_path'],
                'token' => $settings['github_token'],
                'display' => 'text',
                'action' => '',
                'filename' => 'result.json',
            ),
            $atts,
            'github_file_content'
        );

        $file_path = sanitize_text_field($atts['path']);
        $github_token = sanitize_text_field($atts['token']);
        $display_type = sanitize_key($atts['display']);
        $action = sanitize_key($atts['action']);
        $download_filename = sanitize_file_name($atts['filename']);

        if (empty($file_path)) {
            return '<p>Error: Please provide a file path or configure it in the settings.</p>';
        }

        $file_content = $this->read_github_file($file_path, $github_token);

        if (is_wp_error($file_content)) {
            return '<p>Error fetching file from GitHub: ' . esc_html($file_content->get_error_message()) . '</p>';
        }

        if ('download' === $action && 'json' === $display_type) {
            $plugin_dir = TECHOPS_CONTENT_SYNC_DIR . $settings['github_download_path'];
            if (!is_dir($plugin_dir)) {
                wp_mkdir_p($plugin_dir);
            }
            $file_path_local = $plugin_dir . $download_filename;
            $bytes_written = file_put_contents($file_path_local, $file_content);

            if (false !== $bytes_written) {
                return '<p>Successfully downloaded JSON content to: <code>' . esc_html($file_path_local) . '</code></p>';
            } else {
                return '<p>Error: Could not save JSON content to the plugin directory.</p>';
            }
        } elseif ('json' === $display_type) {
            $decoded_json = json_decode($file_content);
            if (json_last_error() === JSON_ERROR_NONE) {
                return '<pre>' . esc_html(wp_json_encode($decoded_json, JSON_PRETTY_PRINT)) . '</pre>';
            } else {
                return '<p>Error: Could not decode JSON content.</p><pre>' . esc_html($file_content) . '</pre>';
            }
        } else {
            return '<pre>' . esc_html($file_content) . '</pre>';
        }
    }
} 