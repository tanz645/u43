<?php
/**
 * Configuration Loader
 *
 * @package U43
 */

namespace U43\Config;

use U43\Registry\Tools_Registry;
use U43\Registry\Agents_Registry;
use U43\Registry\Triggers_Registry;

class Config_Loader {
    
    private $tools_registry;
    private $agents_registry;
    private $triggers_registry;
    
    /**
     * Constructor
     *
     * @param Tools_Registry $tools_registry
     * @param Agents_Registry $agents_registry
     * @param Triggers_Registry $triggers_registry
     */
    public function __construct($tools_registry, $agents_registry, $triggers_registry) {
        $this->tools_registry = $tools_registry;
        $this->agents_registry = $agents_registry;
        $this->triggers_registry = $triggers_registry;
    }
    
    /**
     * Load all configurations
     */
    public function load_all() {
        $this->load_tools();
        $this->load_agents();
        $this->load_triggers();
    }
    
    /**
     * Load tool configurations
     */
    private function load_tools() {
        $tools_dir = U43_PLUGIN_DIR . 'configs/tools/';
        if (!is_dir($tools_dir)) {
            return;
        }
        
        $files = glob($tools_dir . '*.json');
        
        foreach ($files as $file) {
            $config = $this->load_config_file($file);
            if ($config && isset($config['handler'])) {
                $this->load_tool($config, $this->tools_registry);
            }
        }
    }
    
    /**
     * Load agent configurations
     */
    private function load_agents() {
        $agents_dir = U43_PLUGIN_DIR . 'configs/agents/';
        if (!is_dir($agents_dir)) {
            return;
        }
        
        $files = glob($agents_dir . '*.json');
        
        foreach ($files as $file) {
            $config = $this->load_config_file($file);
            if ($config && isset($config['handler'])) {
                $this->load_agent($config, $this->agents_registry);
            }
        }
    }
    
    /**
     * Load trigger configurations
     */
    private function load_triggers() {
        $triggers_dir = U43_PLUGIN_DIR . 'configs/triggers/';
        if (!is_dir($triggers_dir)) {
            return;
        }
        
        $files = glob($triggers_dir . '*.json');
        
        foreach ($files as $file) {
            $config = $this->load_config_file($file);
            if ($config && isset($config['handler'])) {
                $this->load_trigger($config, $this->triggers_registry);
            }
        }
    }
    
    /**
     * Load a tool
     *
     * @param array $config Configuration
     * @param Tools_Registry $registry Registry
     */
    private function load_tool($config, $registry) {
        $handler_class = $config['handler'];
        if (class_exists($handler_class)) {
            $tool = new $handler_class($config);
            $registry->register($config['id'], $tool);
        }
    }
    
    /**
     * Load an agent
     *
     * @param array $config Configuration
     * @param Agents_Registry $registry Registry
     */
    private function load_agent($config, $registry) {
        $handler_class = $config['handler'];
        if (class_exists($handler_class)) {
            $agent = new $handler_class($config);
            $registry->register($config['id'], $agent);
        }
    }
    
    /**
     * Load a trigger
     *
     * @param array $config Configuration
     * @param Triggers_Registry $registry Registry
     */
    private function load_trigger($config, $registry) {
        $handler_class = $config['handler'];
        if (class_exists($handler_class)) {
            $trigger = new $handler_class($config);
            $registry->register($config['id'], $trigger);
        }
    }
    
    /**
     * Load configuration file
     *
     * @param string $file File path
     * @return array|null
     */
    private function load_config_file($file) {
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        $config = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('U43: Invalid JSON in config file: ' . $file);
            return null;
        }
        
        return $config;
    }
}

