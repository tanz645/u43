<?php
/**
 * LLM Agent (Unified Chat and Decision Agent)
 *
 * @package U43
 */

namespace U43\Agents\Built_In\LLM_Agent;

use U43\Agents\Agent_Base;
use U43\LLM\Providers\OpenAI\OpenAI_Provider;

class LLM_Agent extends Agent_Base {
    
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
        // Get mode from config (default: 'chat')
        $mode = $this->config['settings']['mode'] ?? 'chat';
        
        if ($mode === 'decision') {
            return $this->execute_decision_mode($inputs);
        } else {
            return $this->execute_chat_mode($inputs);
        }
    }
    
    /**
     * Execute in chat mode
     *
     * @param array $inputs Input parameters
     * @return array
     */
    private function execute_chat_mode($inputs) {
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
                'model_used' => $this->config['settings']['model'] ?? 'gpt-5.2',
                'tokens_used' => 0,
                'error' => 'No message provided',
            ];
        }
        
        // Call LLM with timeout
        try {
            // Set a timeout for the LLM call (30 seconds)
            $timeout = 30;
            $start_time = microtime(true);
            
            // Get model from config, ensuring we use default if empty or old model
            $model = $this->config['settings']['model'] ?? 'gpt-5.2';
            // If model is empty or is the old default, use the new default
            if (empty($model) || $model === 'gpt-3.5-turbo') {
                $model = 'gpt-5.2';
            }
            
            $response = $this->llm_provider->chat($messages, [
                'model' => $model,
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
                'model_used' => $model ?? 'gpt-5.2',
                'tokens_used' => 0,
                'error' => $e->getMessage(),
            ];
        }
        
        return [
            'response' => $response['response'] ?? '',
            'model_used' => $response['model_used'] ?? ($model ?? 'gpt-5.2'),
            'tokens_used' => $response['tokens_used'] ?? 0,
        ];
    }
    
    /**
     * Execute in decision mode
     *
     * @param array $inputs Input parameters
     * @return array
     */
    private function execute_decision_mode($inputs) {
        // Support both 'message' and 'prompt' for backward compatibility
        $prompt = $inputs['message'] ?? $inputs['prompt'] ?? '';
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
            
            // Get model from config, ensuring we use default if empty or old model
            $model = $this->config['settings']['model'] ?? 'gpt-5.2';
            // If model is empty or is the old default, use the new default
            if (empty($model) || $model === 'gpt-3.5-turbo') {
                $model = 'gpt-5.2';
            }
            
            $response = $this->llm_provider->chat($messages, [
                'model' => $model,
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
            error_log("U43: LLM Decision API call failed: " . $e->getMessage());
            // Return a default decision on error
            return [
                'response' => 'maybe',
                'reason' => 'Error: ' . $e->getMessage() . '. Defaulting to maybe (needs review).',
                'model_used' => $model ?? 'gpt-5.2',
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
        
        // Map decision/reasoning to response/reason
        return [
            'response' => $decision_data['decision'] ?? 'maybe',
            'reason' => $decision_data['reasoning'] ?? $response['response'],
            'model_used' => $response['model_used'] ?? ($model ?? 'gpt-5.2'),
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


