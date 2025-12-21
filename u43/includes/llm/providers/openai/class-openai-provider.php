<?php
/**
 * OpenAI Provider
 *
 * @package U43
 */

namespace U43\LLM\Providers\OpenAI;

use U43\LLM\LLM_Provider_Base;

class OpenAI_Provider extends LLM_Provider_Base {
    
    protected $base_url = 'https://api.openai.com/v1';
    
    /**
     * Check if model requires max_completion_tokens instead of max_tokens
     * 
     * According to OpenAI documentation, GPT-5 models (gpt-5, gpt-5.1, gpt-5.2, etc.)
     * require max_completion_tokens instead of max_tokens.
     * Reference: https://platform.openai.com/docs/guides/latest-model
     *
     * @param string $model Model name
     * @return bool
     */
    private function requires_max_completion_tokens($model) {
        // Only GPT-5 models require max_completion_tokens
        // Pattern matches: gpt-5, gpt-5.1, gpt-5.2, gpt-5-turbo, etc.
        return preg_match('/^gpt-5/', $model) === 1;
    }
    
    /**
     * Check if model is GPT-5
     * GPT-5 models have fixed temperature (1) and don't support custom temperature values
     *
     * @param string $model Model name
     * @return bool
     */
    private function is_gpt5_model($model) {
        return preg_match('/^gpt-5/', $model) === 1;
    }
    
    /**
     * Send chat request
     *
     * @param array $messages Messages
     * @param array $options Options
     * @return array
     * @throws \Exception
     */
    public function chat($messages, $options = []) {
        $model = $options['model'] ?? $this->config['settings']['default_model'] ?? 'gpt-5.2';
        $temperature = $options['temperature'] ?? $this->config['settings']['default_temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? $this->config['settings']['default_max_tokens'] ?? 1000;
        
        if (empty($this->api_key)) {
            throw new \Exception('OpenAI API key is not configured');
        }
        
        // Build request body
        $request_body = [
            'model' => $model,
            'messages' => $messages,
        ];
        
        // GPT-5 models have fixed temperature (1) and don't support custom temperature values
        // Only include temperature parameter for non-GPT-5 models
        $is_gpt5 = $this->is_gpt5_model($model);
        if (!$is_gpt5) {
            // For non-GPT-5 models, include temperature parameter
            $request_body['temperature'] = $temperature;
        }
        // For GPT-5 models, omit temperature parameter to use model default (1)
        
        // Use max_completion_tokens for GPT-5 models, max_tokens for others
        $use_completion_tokens = $this->requires_max_completion_tokens($model);
        if ($use_completion_tokens) {
            $request_body['max_completion_tokens'] = $max_tokens;
        } else {
            $request_body['max_tokens'] = $max_tokens;
        }
        
        $response = wp_remote_post($this->base_url . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_body),
            'timeout' => $this->config['settings']['timeout'] ?? 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('OpenAI API error: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            
            // Check if error is about max_tokens requiring max_completion_tokens
            // If so, retry with max_completion_tokens
            if (!$use_completion_tokens && 
                isset($error_data['error']['code']) && 
                $error_data['error']['code'] === 'unsupported_parameter' &&
                isset($error_data['error']['param']) &&
                $error_data['error']['param'] === 'max_tokens' &&
                strpos($error_data['error']['message'], 'max_completion_tokens') !== false) {
                
                // Retry with max_completion_tokens
                unset($request_body['max_tokens']);
                $request_body['max_completion_tokens'] = $max_tokens;
                
                $response = wp_remote_post($this->base_url . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->api_key,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($request_body),
                    'timeout' => $this->config['settings']['timeout'] ?? 30
                ]);
                
                if (is_wp_error($response)) {
                    throw new \Exception('OpenAI API error: ' . $response->get_error_message());
                }
                
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code !== 200) {
                    $body = wp_remote_retrieve_body($response);
                    throw new \Exception('OpenAI API error: HTTP ' . $status_code . ' - ' . $body);
                }
            }
            // Check if error is about temperature not being supported (GPT-5 models)
            elseif (isset($error_data['error']['code']) && 
                $error_data['error']['code'] === 'unsupported_parameter' &&
                isset($error_data['error']['param']) &&
                $error_data['error']['param'] === 'temperature' &&
                strpos($error_data['error']['message'], 'temperature') !== false) {
                
                // Retry without temperature parameter (use model default)
                unset($request_body['temperature']);
                
                $response = wp_remote_post($this->base_url . '/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->api_key,
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($request_body),
                    'timeout' => $this->config['settings']['timeout'] ?? 30
                ]);
                
                if (is_wp_error($response)) {
                    throw new \Exception('OpenAI API error: ' . $response->get_error_message());
                }
                
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code !== 200) {
                    $body = wp_remote_retrieve_body($response);
                    throw new \Exception('OpenAI API error: HTTP ' . $status_code . ' - ' . $body);
                }
            } else {
                throw new \Exception('OpenAI API error: HTTP ' . $status_code . ' - ' . $body);
            }
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid response from OpenAI API');
        }
        
        return [
            'response' => $body['choices'][0]['message']['content'],
            'model_used' => $model,
            'tokens_used' => $body['usage']['total_tokens'] ?? 0,
        ];
    }
}

