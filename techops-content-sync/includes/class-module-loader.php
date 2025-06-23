<?php
namespace TechOpsContentSync;
error_log('TechOps Content Sync: DEBUG: class-module-loader.php loaded.');

class Module_Loader {
    private static $instance = null;
    private $modules = [];

    private function __construct() {
        $this->load_modules();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_modules() {
        $modules_dir = TECHOPS_CONTENT_SYNC_DIR . 'modules';
        
        if (!is_dir($modules_dir)) {
            return;
        }

        $module_folders = glob($modules_dir . '/*', GLOB_ONLYDIR);
        
        foreach ($module_folders as $module_folder) {
            $module_name = basename($module_folder);
            $module_file = $module_folder . '/module.php';
            
            if (file_exists($module_file)) {
                require_once $module_file;
                $this->modules[$module_name] = [
                    'path' => $module_folder,
                    'url' => TECHOPS_CONTENT_SYNC_URL . 'modules/' . $module_name,
                    'loaded' => true
                ];
            }
        }
    }

    public function get_modules() {
        return $this->modules;
    }

    public function get_module_path($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name]['path'] : null;
    }

    public function get_module_url($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name]['url'] : null;
    }
} 