<?php
/**
 * Base Agent Class
 *
 * @package U43
 */

namespace U43\Agents;

abstract class Agent_Base {
    
    protected $config;
    
    /**
     * Constructor
     *
     * @param array $config Configuration
     */
    public function __construct($config = []) {
        $this->config = $config;
    }
    
    /**
     * Get config
     *
     * @return array
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Set config
     *
     * @param array $config Configuration
     */
    public function set_config($config) {
        $this->config = $config;
    }
    
    /**
     * Merge config
     *
     * @param array $additional_config Additional config to merge
     */
    public function merge_config($additional_config) {
        // Deep merge config, especially for nested arrays like 'settings'
        $this->config = $this->array_merge_recursive($this->config ?? [], $additional_config);
    }
    
    /**
     * Recursively merge arrays
     *
     * @param array $array1 First array
     * @param array $array2 Second array (takes precedence)
     * @return array Merged array
     */
    private function array_merge_recursive($array1, $array2) {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                // Recursively merge nested arrays
                $merged[$key] = $this->array_merge_recursive($merged[$key], $value);
            } else {
                // Overwrite with new value
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * Execute the agent
     *
     * @param array $inputs Input parameters
     * @return mixed
     */
    abstract public function execute($inputs);
}

