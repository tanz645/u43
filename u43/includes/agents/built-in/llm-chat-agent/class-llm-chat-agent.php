<?php
/**
 * LLM Chat Agent
 *
 * @package U43
 */

namespace U43\Agents\Built_In\LLM_Chat_Agent;

use U43\Agents\Agent_Base;
use U43\LLM\Providers\OpenAI\OpenAI_Provider;

class LLM_Chat_Agent extends Agent_Base {
    
    protected $llm_provider;
    
    /**
     * Constructor
     *
     * @param array $config Configuration
     */
    public function __construct($config = []) {
        parent::__construct($config);
        $this->init_llm_provider();
    }
    
    /**
     * Initialize LLM provider
     */
    private function init_llm_provider() {
        $provider_id = $this->config['settings']['provider'] ?? 'openai';
        $integration_config = $this->load_integration_config($provider_id);
        $this->llm_provider = new OpenAI_Provider($integration_config);
    }
    
    /**
     * Execute the agent
     *
     * @param array $inputs Input parameters
     * @return array
     */
    public function execute($inputs) {
        $message = $inputs['message'] ?? '';
        $conversation_history = $inputs['conversation_history'] ?? [];
        $system_prompt = $inputs['system_prompt'] ?? null;
        
        // Build messages for LLM
        $messages = [];
        
        // Add system prompt if provided (from inputs or config)
        $system_content = $system_prompt ?? $this->config['settings']['system_prompt'] ?? 'You are a helpful assistant.';
        if (!empty($system_content)) {
            $messages[] = [
                'role' => 'system',
                'content' => $system_content
            ];
        }
        
        // Add conversation history if provided
        if (!empty($conversation_history) && is_array($conversation_history)) {
            foreach ($conversation_history as $history_item) {
                // Ensure each history item has role and content
                if (isset($history_item['role']) && isset($history_item['content'])) {
                    $messages[] = [
                        'role' => $history_item['role'],
                        'content' => $history_item['content']
                    ];
                }
            }
        }
        
        // Add current user message
        if (!empty($message)) {
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];
        }
        
        // Ensure we have at least one message
        if (empty($messages)) {
            return [
                'response' => 'Error: No message provided.',
                'model_used' => $this->config['settings']['model'] ?? 'gpt-3.5-turbo',
                'tokens_used' => 0,
                'error' => 'No message provided',
            ];
        }
        
        // Call LLM with timeout
        try {
            // Set a timeout for the LLM call (30 seconds)
            $timeout = 30;
            $start_time = microtime(true);
            
            $response = $this->llm_provider->chat($messages, [
                'model' => $this->config['settings']['model'] ?? 'gpt-3.5-turbo',
                'temperature' => $this->config['settings']['temperature'] ?? 0.7,
                'timeout' => $timeout,
            ]);
            
            $elapsed = microtime(true) - $start_time;
            if ($elapsed > $timeout) {
                throw new \Exception("LLM API call timed out after {$timeout} seconds");
            }
            
            // Check if response is valid
            if (!isset($response['response'])) {
                throw new \Exception("Invalid response from LLM provider: missing 'response' field");
            }
            
        } catch (\Exception $e) {
            error_log("U43: LLM Chat API call failed: " . $e->getMessage());
            // Return error response
            return [
                'response' => 'Error: ' . $e->getMessage(),
                'model_used' => $this->config['settings']['model'] ?? 'gpt-3.5-turbo',
                'tokens_used' => 0,
                'error' => $e->getMessage(),
            ];
        }
        
        return [
            'response' => $response['response'] ?? '',
            'model_used' => $response['model_used'] ?? ($this->config['settings']['model'] ?? 'gpt-3.5-turbo'),
            'tokens_used' => $response['tokens_used'] ?? 0,
        ];
    }
    
    /**
     * Load integration configuration
     *
     * @param string $integration_id Integration ID
     * @return array
     */
    private function load_integration_config($integration_id) {
        $config_file = U43_PLUGIN_DIR . "configs/integrations/{$integration_id}.json";
        if (file_exists($config_file)) {
            return json_decode(file_get_contents($config_file), true);
        }
        return [];
    }
}

