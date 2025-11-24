<?php
/**
 * LLM Decision Agent
 *
 * @package U43
 */

namespace U43\Agents\Built_In\LLM_Decision_Agent;

use U43\Agents\Agent_Base;
use U43\LLM\Providers\OpenAI\OpenAI_Provider;

class LLM_Decision_Agent extends Agent_Base {
    
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
        $prompt = $inputs['prompt'] ?? '';
        $context = $inputs['context'] ?? [];
        
        // Get decision options from inputs (passed from executor) or config
        $decision_options = $inputs['decision_options'] ?? null;
        
        if ($decision_options === null) {
            // Fallback to config if not in inputs
            $custom_decisions = $this->config['custom_decisions'] ?? '';
            $decision_options = ['yes', 'no', 'maybe'];
            
            if (!empty($custom_decisions)) {
                // Parse custom decisions (comma-separated)
                $custom_list = array_map('trim', explode(',', $custom_decisions));
                $decision_options = array_merge($decision_options, $custom_list);
                $decision_options = array_unique($decision_options);
            }
        }
        
        $decision_options_str = implode(', ', $decision_options);
        $decision_options_json = json_encode($decision_options);
        
        // Build messages for LLM
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a decision-making assistant. Analyze the given information and make a decision. Respond with a JSON object containing "decision" and "reasoning" fields. The decision must be one of: ' . $decision_options_str . '. Use "yes" for positive/approve actions, "no" for negative/reject actions, and "maybe" for uncertain cases that need review.'
            ],
            [
                'role' => 'user',
                'content' => $prompt . "\n\nContext: " . json_encode($context, JSON_PRETTY_PRINT) . "\n\nDecision Options: " . $decision_options_json
            ]
        ];
        
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
            error_log("U43: LLM API call failed: " . $e->getMessage());
            // Return a default decision on error
            return [
                'decision' => 'maybe',
                'reasoning' => 'Error: ' . $e->getMessage() . '. Defaulting to maybe (needs review).',
                'model_used' => $this->config['settings']['model'] ?? 'gpt-3.5-turbo',
                'tokens_used' => 0,
                'error' => $e->getMessage(),
            ];
        }
        
        // Parse response
        $decision_data = json_decode($response['response'], true);
        
        if (!$decision_data) {
            // Fallback: try to extract decision from text
            $decision_data = $this->parse_decision_from_text($response['response'], $decision_options);
        }
        
        return [
            'decision' => $decision_data['decision'] ?? 'maybe',
            'reasoning' => $decision_data['reasoning'] ?? $response['response'],
            'model_used' => $response['model_used'] ?? ($this->config['settings']['model'] ?? 'gpt-3.5-turbo'),
            'tokens_used' => $response['tokens_used'] ?? 0,
        ];
    }
    
    /**
     * Parse decision from text response
     *
     * @param string $text Response text
     * @param array $decision_options Available decision options
     * @return array
     */
    private function parse_decision_from_text($text, $decision_options = ['yes', 'no', 'maybe']) {
        $text_lower = strtolower($text);
        
        // Check for each decision option in order of priority
        foreach ($decision_options as $option) {
            if (strpos($text_lower, strtolower($option)) !== false) {
                return ['decision' => $option, 'reasoning' => $text];
            }
        }
        
        // Fallback: check for yes/no variations
        if (strpos($text_lower, 'yes') !== false || strpos($text_lower, 'true') !== false || strpos($text_lower, 'approve') !== false || strpos($text_lower, '1') !== false) {
            return ['decision' => 'yes', 'reasoning' => $text];
        } elseif (strpos($text_lower, 'no') !== false || strpos($text_lower, 'false') !== false || strpos($text_lower, 'reject') !== false || strpos($text_lower, 'spam') !== false || strpos($text_lower, 'delete') !== false || strpos($text_lower, '0') !== false) {
            return ['decision' => 'no', 'reasoning' => $text];
        } elseif (strpos($text_lower, 'maybe') !== false || strpos($text_lower, 'uncertain') !== false || strpos($text_lower, 'review') !== false) {
            return ['decision' => 'maybe', 'reasoning' => $text];
        }
        
        // Default fallback
        return ['decision' => 'maybe', 'reasoning' => $text];
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

