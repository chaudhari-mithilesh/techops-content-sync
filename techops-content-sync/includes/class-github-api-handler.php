<?php
namespace TechOpsContentSync;

/**
 * GitHub API Handler Class
 * 
 * Handles GitHub API requests and operations.
 */
class GitHub_API_Handler {
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var string GitHub API base URL
     */
    private $api_base = 'https://api.github.com';

    /**
     * @var array Repository information
     */
    private $repo_info = [];

    /**
     * @var array Rate limit information
     */
    private $rate_limit_info = null;

    /**
     * Constructor
     * 
     * @param Settings|null $settings Settings instance (optional)
     */
    public function __construct($settings = null) {
        $this->settings = $settings;
        $this->check_rate_limit();
    }

    /**
     * Parse repository URL
     * 
     * @param string $url Repository URL
     * @return array|WP_Error Repository information or error
     */
    public function parse_repository_url($url) {
        try {
            // Remove .git if present
            $url = str_replace('.git', '', $url);
            
            // Extract owner and repo name
            $pattern = '/github\.com\/([^\/]+)\/([^\/]+)/';
            if (!preg_match($pattern, $url, $matches)) {
                return new \WP_Error(
                    'invalid_repository_url',
                    'Invalid GitHub repository URL',
                    ['status' => 400]
                );
            }

            return [
                'owner' => $matches[1],
                'name' => $matches[2],
                'url' => $url
            ];
        } catch (\Exception $e) {
            return new \WP_Error(
                'url_parsing_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get authentication headers
     * 
     * @return array Authentication headers
     */
    private function get_auth_headers() {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress-TechOps-Content-Sync'
        ];

        // Get GitHub token from settings
        if ($this->settings) {
            $token = $this->settings->get_github_token();
            if (!empty($token)) {
                $headers['Authorization'] = 'token ' . $token;
            } else {
                error_log('TechOps Content Sync: No GitHub token found in settings');
            }
        } else {
            error_log('TechOps Content Sync: Settings instance not available');
        }

        return $headers;
    }

    /**
     * Make API request
     * 
     * @param string $endpoint API endpoint
     * @param string $method Request method
     * @param array|null $data Request data
     * @return array|WP_Error Response data or error
     */
    public function api_request($endpoint, $method = 'GET', $data = null) {
        try {
            if (!$this->check_rate_limit()) {
                return new \WP_Error(
                    'rate_limit_exceeded',
                    'GitHub API rate limit exceeded',
                    ['status' => 429]
                );
            }

            $url = $this->api_base . $endpoint;
            $args = [
                'method' => $method,
                'headers' => $this->get_auth_headers(),
                'timeout' => 30
            ];

            if ($data) {
                $args['body'] = json_encode($data);
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            // Update rate limit info
            $this->update_rate_limit_info($response);

            if ($response_code >= 400) {
                return new \WP_Error(
                    'github_api_error',
                    $body['message'] ?? 'GitHub API request failed',
                    ['status' => $response_code]
                );
            }

            return $body;
        } catch (\Exception $e) {
            return new \WP_Error(
                'api_request_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Update rate limit information from response headers
     * 
     * @param array $response Response data
     */
    private function update_rate_limit_info($response) {
        $headers = wp_remote_retrieve_headers($response);
        if (isset($headers['x-ratelimit-limit']) && isset($headers['x-ratelimit-remaining'])) {
            $this->rate_limit_info = [
                'limit' => (int)$headers['x-ratelimit-limit'],
                'remaining' => (int)$headers['x-ratelimit-remaining'],
                'reset' => isset($headers['x-ratelimit-reset']) ? (int)$headers['x-ratelimit-reset'] : null
            ];
        }
    }

    /**
     * Check rate limit
     * 
     * @return bool Whether requests can be made
     */
    public function check_rate_limit() {
        if ($this->rate_limit_info === null) {
            $rate_limit = $this->get_rate_limit();
            if (is_wp_error($rate_limit)) {
                return false;
            }
            $this->rate_limit_info = $rate_limit;
        }

        return $this->rate_limit_info['remaining'] > 0;
    }

    /**
     * Get repository branches
     * 
     * @param string $owner Repository owner
     * @param string $repo Repository name
     * @return array|WP_Error List of branches or error
     */
    public function get_branches($owner, $repo) {
        return $this->api_request("/repos/{$owner}/{$repo}/branches");
    }

    /**
     * Get repository tags
     * 
     * @param string $owner Repository owner
     * @param string $repo Repository name
     * @return array|WP_Error List of tags or error
     */
    public function get_tags($owner, $repo) {
        return $this->api_request("/repos/{$owner}/{$repo}/tags");
    }

    /**
     * Get repository packages (plugins/themes)
     * 
     * @param string $owner Repository owner
     * @param string $repo Repository name
     * @return array|WP_Error List of packages or error
     */
    public function get_packages($owner, $repo) {
        $packages = [];
        
        // Get repository contents
        $contents = $this->api_request("/repos/{$owner}/{$repo}/contents");
        if (is_wp_error($contents)) {
            return $contents;
        }

        foreach ($contents as $item) {
            if ($item['type'] === 'dir') {
                // Check if directory contains plugin or theme files
                $package_type = $this->detect_package_type($owner, $repo, $item['path']);
                if ($package_type) {
                    $packages[] = [
                        'type' => $package_type,
                        'name' => $item['name'],
                        'path' => $item['path']
                    ];
                }
            }
        }

        return $packages;
    }

    /**
     * Detect if a directory contains a plugin or theme
     * 
     * @param string $owner Repository owner
     * @param string $repo Repository name
     * @param string $path Directory path
     * @return string|false Package type or false if not a plugin or theme
     */
    private function detect_package_type($owner, $repo, $path) {
        // Check for plugin files
        $plugin_files = $this->api_request("/repos/{$owner}/{$repo}/contents/{$path}");
        if (is_wp_error($plugin_files)) {
            return false;
        }

        foreach ($plugin_files as $file) {
            if ($file['name'] === 'plugin.php' || $file['name'] === 'style.css') {
                return $file['name'] === 'plugin.php' ? 'plugin' : 'theme';
            }
        }

        return false;
    }

    /**
     * Test GitHub token
     * 
     * @param string $token GitHub token
     * @return bool|WP_Error Test result or error
     */
    public function test_token($token) {
        $headers = [
            'Authorization' => 'token ' . $token,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress-TechOps-Content-Sync'
        ];

        $response = wp_remote_get($this->api_base . '/user', [
            'headers' => $headers,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new \WP_Error(
                'invalid_token',
                'Invalid GitHub token',
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * Get rate limit information
     * 
     * @return array|WP_Error Rate limit information or error
     */
    public function get_rate_limit() {
        $response = $this->api_request('/rate_limit');
        if (is_wp_error($response)) {
            return $response;
        }

        return [
            'limit' => $response['resources']['core']['limit'],
            'remaining' => $response['resources']['core']['remaining'],
            'reset' => $response['resources']['core']['reset']
        ];
    }

    /**
     * Handle Git repository download request
     * 
     * @param string $repo_url Repository URL
     * @param string $folder_path Path to download
     * @return array|WP_Error Download result or error
     */
    public function handle_git_download($repo_url, $folder_path) {
        try {
            $repo_info = $this->parse_repository_url($repo_url);
            if (is_wp_error($repo_info)) {
                return $repo_info;
            }

            // Get repository contents
            $contents = $this->api_request("/repos/{$repo_info['owner']}/{$repo_info['name']}/contents/{$folder_path}");
            if (is_wp_error($contents)) {
                return $contents;
            }

            // Create temporary directory
            $temp_dir = wp_upload_dir()['basedir'] . '/techops-temp';
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }

            $download_dir = $temp_dir . '/' . uniqid('git-download-');
            wp_mkdir_p($download_dir);

            // Download files
            foreach ($contents as $item) {
                if ($item['type'] === 'file') {
                    $file_path = $download_dir . '/' . $item['name'];
                    $file_content = $this->api_request($item['url']);
                    if (is_wp_error($file_content)) {
                        $this->cleanup($download_dir);
                        return $file_content;
                    }
                    file_put_contents($file_path, base64_decode($file_content['content']));
                }
            }

            return [
                'success' => true,
                'download_path' => $download_dir,
                'cleanup' => function() use ($download_dir) {
                    $this->cleanup($download_dir);
                }
            ];

        } catch (\Exception $e) {
            if (isset($download_dir)) {
                $this->cleanup($download_dir);
            }
            return new \WP_Error(
                'git_download_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Clean up temporary files
     * 
     * @param string $path Path to clean up
     */
    private function cleanup($path) {
        if (file_exists($path)) {
            $this->recursive_remove_directory($path);
        }
    }

    /**
     * Recursively remove directory
     * 
     * @param string $dir Directory to remove
     */
    private function recursive_remove_directory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursive_remove_directory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
} 