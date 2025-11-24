<?php
/**
 * Base Tool Class
 *
 * @package U43
 */

namespace U43\Tools;

abstract class Tool_Base {
    
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
     * Execute the tool
     *
     * @param array $inputs Input parameters
     * @return mixed
     */
    abstract public function execute($inputs);
}

