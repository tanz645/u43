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
     * Send chat request
     *
     * @param array $messages Messages
     * @param array $options Options
     * @return array
     * @throws \Exception
     */
    public function chat($messages, $options = []) {
        $model = $options['model'] ?? $this->config['settings']['default_model'] ?? 'gpt-3.5-turbo';
        $temperature = $options['temperature'] ?? $this->config['settings']['default_temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? $this->config['settings']['default_max_tokens'] ?? 1000;
        
        if (empty($this->api_key)) {
            throw new \Exception('OpenAI API key is not configured');
        }
        
        $response = wp_remote_post($this->base_url . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $max_tokens
            ]),
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

