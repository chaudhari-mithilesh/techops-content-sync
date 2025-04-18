<?php
namespace TechOpsContentSync;

class File_Handler {
    /**
     * Create a ZIP file for a plugin
     */
    public function create_plugin_zip($slug) {
        error_log('TechOps Content Sync: Creating ZIP for plugin: ' . $slug);
        
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get all plugins
        $plugins = get_plugins();
        error_log('TechOps Content Sync: Available plugins: ' . print_r(array_keys($plugins), true));
        
        // Find the plugin path
        $plugin_path = '';
        foreach ($plugins as $path => $data) {
            // Check for exact match (for single-file plugins like hello.php)
            if ($path === $slug || $path === $slug . '.php') {
                $plugin_path = $path;
                break;
            }
            
            // Check if path starts with the slug
            if (strpos($path, $slug . '/') === 0) {
                $plugin_path = $path;
                break;
            }
        }
        
        if (empty($plugin_path)) {
            error_log('TechOps Content Sync: Plugin not found: ' . $slug);
            throw new \Exception('Plugin not found');
        }
        
        error_log('TechOps Content Sync: Found plugin path: ' . $plugin_path);
        
        // Get the plugin directory
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_path);
        if (!is_dir($plugin_dir)) {
            error_log('TechOps Content Sync: Plugin directory not found: ' . $plugin_dir);
            throw new \Exception('Plugin directory not found');
        }
        
        // Create temporary directory
        $temp_dir = get_temp_dir() . 'techops-content-sync';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        // Create ZIP file
        $zip_file = $temp_dir . '/' . $slug . '.zip';
        error_log('TechOps Content Sync: Creating ZIP file: ' . $zip_file);
        
        return $this->create_zip($plugin_dir, $zip_file);
    }
    
    /**
     * Create a ZIP file for a theme
     */
    public function create_theme_zip($slug) {
        error_log('TechOps Content Sync: Creating ZIP for theme: ' . $slug);
        
        // Get theme directory
        $theme = wp_get_theme($slug);
        if (!$theme->exists()) {
            error_log('TechOps Content Sync: Theme not found: ' . $slug);
            throw new \Exception('Theme not found');
        }
        
        $theme_dir = $theme->get_stylesheet_directory();
        if (!is_dir($theme_dir)) {
            error_log('TechOps Content Sync: Theme directory not found: ' . $theme_dir);
            throw new \Exception('Theme directory not found');
        }
        
        // Create temporary directory
        $temp_dir = get_temp_dir() . 'techops-content-sync';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        // Create ZIP file
        $zip_file = $temp_dir . '/' . $slug . '.zip';
        error_log('TechOps Content Sync: Creating ZIP file: ' . $zip_file);
        
        return $this->create_zip($theme_dir, $zip_file);
    }
    
    /**
     * Create a ZIP file from a directory
     */
    private function create_zip($source_path, $zip_file) {
        error_log('TechOps Content Sync: Creating ZIP from: ' . $source_path);
        
        // Check if source exists
        if (!file_exists($source_path)) {
            error_log('TechOps Content Sync: Source path not found: ' . $source_path);
            throw new \Exception('Source path not found');
        }
        
        // Create new ZIP archive
        $zip = new \ZipArchive();
        $result = $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        if ($result !== true) {
            error_log('TechOps Content Sync: Failed to create ZIP file: ' . $zip_file . ' (Error code: ' . $result . ')');
            throw new \Exception('Failed to create ZIP file (Error code: ' . $result . ')');
        }
        
        // Create recursive directory iterator
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source_path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $base_path = realpath($source_path);
        
        // Add files to ZIP
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($base_path) + 1);
                
                error_log('TechOps Content Sync: Adding file to ZIP: ' . $relative_path);
                
                if (!$zip->addFile($file_path, $relative_path)) {
                    $zip->close();
                    error_log('TechOps Content Sync: Failed to add file to ZIP: ' . $relative_path);
                    throw new \Exception('Failed to add file to ZIP: ' . $relative_path);
                }
            }
        }
        
        // Close ZIP file
        if (!$zip->close()) {
            error_log('TechOps Content Sync: Failed to close ZIP file');
            throw new \Exception('Failed to close ZIP file');
        }
        
        // Verify the ZIP file was created and is valid
        if (!file_exists($zip_file)) {
            error_log('TechOps Content Sync: ZIP file does not exist after creation: ' . $zip_file);
            throw new \Exception('ZIP file does not exist after creation');
        }
        
        $zip_size = filesize($zip_file);
        if ($zip_size === false || $zip_size === 0) {
            error_log('TechOps Content Sync: Created ZIP file is empty: ' . $zip_file);
            throw new \Exception('Created ZIP file is empty');
        }
        
        error_log('TechOps Content Sync: ZIP file created successfully: ' . $zip_file . ' (size: ' . $zip_size . ' bytes)');
        
        // Return file path and cleanup callback
        return [
            'file' => $zip_file,
            'cleanup' => function() use ($zip_file) {
                if (file_exists($zip_file)) {
                    unlink($zip_file);
                }
            }
        ];
    }
} 