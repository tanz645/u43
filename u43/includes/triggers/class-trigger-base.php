<?php
/**
 * Base Trigger Class
 *
 * @package U43
 */

namespace U43\Triggers;

abstract class Trigger_Base {
    
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
     * Register the trigger
     */
    abstract public function register();
    
    /**
     * Get output schema
     *
     * @return array
     */
    abstract public function get_output_schema();
}

