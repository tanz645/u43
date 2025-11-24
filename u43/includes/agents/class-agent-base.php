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
        $this->config = array_merge($this->config ?? [], $additional_config);
    }
    
    /**
     * Execute the agent
     *
     * @param array $inputs Input parameters
     * @return mixed
     */
    abstract public function execute($inputs);
}

