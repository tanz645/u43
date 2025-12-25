<?php
/**
 * Base LLM Provider Class
 *
 * @package U43
 */

namespace U43\LLM;

use U43\Config\Settings_Manager;

abstract class LLM_Provider_Base {
    
    protected $config;
    protected $api_key;
    
    /**
     * Constructor
     *
     * @param array $config Configuration
     */
    public function __construct($config) {
        $this->config = $config;
        $this->api_key = $this->get_api_key();
    }
    
    /**
     * Send chat request to LLM
     *
     * @param array $messages Messages
     * @param array $options Options
     * @return array
     */
    abstract public function chat($messages, $options = []);
    
    /**
     * Get API key
     *
     * @return string
     */
    protected function get_api_key() {
        $integration_id = $this->config['id'] ?? '';
        return Settings_Manager::get("u43_{$integration_id}_api_key", '');
    }
}

