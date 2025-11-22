# AI Models & LLM Providers

The plugin supports multiple AI/LLM providers, allowing you to choose the best model for each workflow. All models are configured through JSON configuration files and can be easily switched or added.

## Supported Providers

- ✅ **OpenAI** (ChatGPT, GPT-4, GPT-3.5)
- ✅ **Anthropic** (Claude)
- ✅ **Google** (Gemini)
- ✅ **DeepSeek**
- ✅ **Grok** (xAI)
- ✅ **OpenRouter** (Unified API for multiple providers)
- ✅ **Custom/Open Source Models** (via API endpoints)

## Model Provider Configuration

Each LLM provider is configured as an integration. Here's how to set up different providers:

### OpenAI (ChatGPT) Configuration

Create `configs/integrations/openai.json`:

```json
{
  "id": "openai",
  "name": "OpenAI",
  "description": "OpenAI ChatGPT, GPT-4, GPT-3.5 integration",
  "version": "1.0.0",
  "icon": "openai",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "models": [
    {
      "id": "gpt-4",
      "name": "GPT-4",
      "description": "Most capable model",
      "max_tokens": 8192,
      "supports_streaming": true,
      "supports_functions": true,
      "cost_per_1k_tokens": 0.03
    },
    {
      "id": "gpt-4-turbo",
      "name": "GPT-4 Turbo",
      "description": "Faster GPT-4",
      "max_tokens": 128000,
      "supports_streaming": true,
      "supports_functions": true,
      "cost_per_1k_tokens": 0.01
    },
    {
      "id": "gpt-3.5-turbo",
      "name": "GPT-3.5 Turbo",
      "description": "Fast and cost-effective",
      "max_tokens": 16385,
      "supports_streaming": true,
      "supports_functions": true,
      "cost_per_1k_tokens": 0.0015
    }
  ],
  "api_base_url": "https://api.openai.com/v1",
  "endpoints": {
    "chat": "/chat/completions",
    "embeddings": "/embeddings"
  },
  "settings": {
    "default_model": "gpt-3.5-turbo",
    "default_temperature": 0.7,
    "default_max_tokens": 1000,
    "timeout": 30
  }
}
```

### Anthropic (Claude) Configuration

Create `configs/integrations/anthropic.json`:

```json
{
  "id": "anthropic",
  "name": "Anthropic Claude",
  "description": "Anthropic Claude AI integration",
  "version": "1.0.0",
  "icon": "anthropic",
  "authentication": {
    "type": "api_key",
    "header_name": "x-api-key",
    "header_format": "{api_key}",
    "storage": "encrypted"
  },
  "models": [
    {
      "id": "claude-3-opus",
      "name": "Claude 3 Opus",
      "description": "Most powerful Claude model",
      "max_tokens": 4096,
      "supports_streaming": true,
      "supports_functions": false,
      "cost_per_1k_tokens": 0.015
    },
    {
      "id": "claude-3-sonnet",
      "name": "Claude 3 Sonnet",
      "description": "Balanced performance and speed",
      "max_tokens": 4096,
      "supports_streaming": true,
      "supports_functions": false,
      "cost_per_1k_tokens": 0.003
    },
    {
      "id": "claude-3-haiku",
      "name": "Claude 3 Haiku",
      "description": "Fastest and most affordable",
      "max_tokens": 4096,
      "supports_streaming": true,
      "supports_functions": false,
      "cost_per_1k_tokens": 0.00025
    }
  ],
  "api_base_url": "https://api.anthropic.com/v1",
  "endpoints": {
    "chat": "/messages"
  },
  "settings": {
    "default_model": "claude-3-sonnet",
    "default_temperature": 0.7,
    "default_max_tokens": 1024,
    "timeout": 30
  }
}
```

### Google Gemini Configuration

Create `configs/integrations/google-gemini.json`:

```json
{
  "id": "google_gemini",
  "name": "Google Gemini",
  "description": "Google Gemini AI integration",
  "version": "1.0.0",
  "icon": "google",
  "authentication": {
    "type": "api_key",
    "header_name": "x-goog-api-key",
    "header_format": "{api_key}",
    "storage": "encrypted"
  },
  "models": [
    {
      "id": "gemini-pro",
      "name": "Gemini Pro",
      "description": "Google's advanced AI model",
      "max_tokens": 8192,
      "supports_streaming": true,
      "supports_functions": true,
      "cost_per_1k_tokens": 0.0005
    },
    {
      "id": "gemini-pro-vision",
      "name": "Gemini Pro Vision",
      "description": "Multimodal model with vision",
      "max_tokens": 4096,
      "supports_streaming": true,
      "supports_functions": true,
      "supports_images": true,
      "cost_per_1k_tokens": 0.0005
    }
  ],
  "api_base_url": "https://generativelanguage.googleapis.com/v1beta",
  "endpoints": {
    "chat": "/models/{model}:generateContent",
    "stream": "/models/{model}:streamGenerateContent"
  },
  "settings": {
    "default_model": "gemini-pro",
    "default_temperature": 0.7,
    "default_max_tokens": 2048,
    "timeout": 30
  }
}
```

### DeepSeek Configuration

Create `configs/integrations/deepseek.json`:

```json
{
  "id": "deepseek",
  "name": "DeepSeek",
  "description": "DeepSeek AI integration",
  "version": "1.0.0",
  "icon": "deepseek",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "models": [
    {
      "id": "deepseek-chat",
      "name": "DeepSeek Chat",
      "description": "DeepSeek's conversational model",
      "max_tokens": 4096,
      "supports_streaming": true,
      "supports_functions": false,
      "cost_per_1k_tokens": 0.00014
    },
    {
      "id": "deepseek-coder",
      "name": "DeepSeek Coder",
      "description": "Optimized for code generation",
      "max_tokens": 16384,
      "supports_streaming": true,
      "supports_functions": false,
      "cost_per_1k_tokens": 0.00014
    }
  ],
  "api_base_url": "https://api.deepseek.com/v1",
  "endpoints": {
    "chat": "/chat/completions"
  },
  "settings": {
    "default_model": "deepseek-chat",
    "default_temperature": 0.7,
    "default_max_tokens": 2048,
    "timeout": 30
  }
}
```

### Grok (xAI) Configuration

Create `configs/integrations/grok.json`:

```json
{
  "id": "grok",
  "name": "Grok (xAI)",
  "description": "xAI Grok integration",
  "version": "1.0.0",
  "icon": "grok",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "models": [
    {
      "id": "grok-beta",
      "name": "Grok Beta",
      "description": "xAI's Grok model",
      "max_tokens": 8192,
      "supports_streaming": true,
      "supports_functions": false,
      "cost_per_1k_tokens": 0.01
    }
  ],
  "api_base_url": "https://api.x.ai/v1",
  "endpoints": {
    "chat": "/chat/completions"
  },
  "settings": {
    "default_model": "grok-beta",
    "default_temperature": 0.7,
    "default_max_tokens": 2048,
    "timeout": 30
  }
}
```

### OpenRouter Configuration (Unified API)

OpenRouter provides access to multiple providers through a single API:

Create `configs/integrations/openrouter.json`:

```json
{
  "id": "openrouter",
  "name": "OpenRouter",
  "description": "Unified API for multiple LLM providers",
  "version": "1.0.0",
  "icon": "openrouter",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "models": [
    {
      "id": "openai/gpt-4",
      "name": "GPT-4 via OpenRouter",
      "provider": "openai",
      "max_tokens": 8192,
      "supports_streaming": true
    },
    {
      "id": "anthropic/claude-3-opus",
      "name": "Claude 3 Opus via OpenRouter",
      "provider": "anthropic",
      "max_tokens": 4096,
      "supports_streaming": true
    },
    {
      "id": "google/gemini-pro",
      "name": "Gemini Pro via OpenRouter",
      "provider": "google",
      "max_tokens": 8192,
      "supports_streaming": true
    },
    {
      "id": "meta-llama/llama-3-70b-instruct",
      "name": "Llama 3 70B via OpenRouter",
      "provider": "meta",
      "max_tokens": 8192,
      "supports_streaming": true
    }
  ],
  "api_base_url": "https://openrouter.ai/api/v1",
  "endpoints": {
    "chat": "/chat/completions"
  },
  "settings": {
    "default_model": "openai/gpt-3.5-turbo",
    "default_temperature": 0.7,
    "default_max_tokens": 1000,
    "timeout": 30
  }
}
```

## Agent Configuration with Model Selection

Agents can be configured to use specific models or allow model selection:

### Agent with Model Selection

Create `configs/agents/llm-agent.json`:

```json
{
  "id": "llm_agent",
  "name": "LLM Agent",
  "description": "Generic LLM agent with model selection",
  "version": "1.0.0",
  "category": "ai",
  "icon": "brain",
  "capabilities": [
    "text_generation",
    "decision_making",
    "text_analysis",
    "context_understanding"
  ],
  "inputs": {
    "prompt": {
      "type": "string",
      "required": true,
      "label": "Prompt",
      "description": "The prompt for the LLM"
    },
    "model_provider": {
      "type": "enum",
      "required": false,
      "default": "openai",
      "label": "Model Provider",
      "options": [
        "openai",
        "anthropic",
        "google_gemini",
        "deepseek",
        "grok",
        "openrouter"
      ],
      "description": "Select the AI provider"
    },
    "model": {
      "type": "string",
      "required": false,
      "label": "Model",
      "description": "Specific model ID (uses provider default if not specified)"
    },
    "temperature": {
      "type": "float",
      "required": false,
      "default": 0.7,
      "min": 0,
      "max": 2,
      "label": "Temperature",
      "description": "Controls randomness (0 = deterministic, 2 = very creative)"
    },
    "max_tokens": {
      "type": "integer",
      "required": false,
      "label": "Max Tokens",
      "description": "Maximum tokens in response"
    },
    "context": {
      "type": "object",
      "required": false,
      "label": "Context",
      "description": "Additional context for the agent"
    }
  },
  "outputs": {
    "response": {
      "type": "string",
      "label": "Response"
    },
    "model_used": {
      "type": "string",
      "label": "Model Used"
    },
    "tokens_used": {
      "type": "integer",
      "label": "Tokens Used"
    },
    "cost": {
      "type": "float",
      "label": "Estimated Cost"
    }
  },
  "handler": "Agents\\LLMAgent",
  "settings": {
    "fallback_provider": "openai",
    "fallback_model": "gpt-3.5-turbo",
    "enable_cost_tracking": true
  }
}
```

### Provider-Specific Agent Examples

#### ChatGPT Agent

Create `configs/agents/chatgpt-agent.json`:

```json
{
  "id": "chatgpt_agent",
  "name": "ChatGPT Agent",
  "description": "Agent using OpenAI ChatGPT",
  "version": "1.0.0",
  "category": "ai",
  "icon": "openai",
  "provider": "openai",
  "default_model": "gpt-4",
  "capabilities": ["text_generation", "code_generation", "analysis"],
  "inputs": {
    "prompt": {
      "type": "string",
      "required": true,
      "label": "Prompt"
    },
    "model": {
      "type": "enum",
      "required": false,
      "default": "gpt-4",
      "options": ["gpt-4", "gpt-4-turbo", "gpt-3.5-turbo"],
      "label": "Model"
    }
  },
  "outputs": {
    "response": {
      "type": "string",
      "label": "Response"
    }
  },
  "handler": "Agents\\OpenAI\\ChatGPTAgent"
}
```

#### Claude Agent

Create `configs/agents/claude-agent.json`:

```json
{
  "id": "claude_agent",
  "name": "Claude Agent",
  "description": "Agent using Anthropic Claude",
  "version": "1.0.0",
  "category": "ai",
  "icon": "anthropic",
  "provider": "anthropic",
  "default_model": "claude-3-sonnet",
  "capabilities": ["text_generation", "analysis", "reasoning"],
  "inputs": {
    "prompt": {
      "type": "string",
      "required": true,
      "label": "Prompt"
    },
    "model": {
      "type": "enum",
      "required": false,
      "default": "claude-3-sonnet",
      "options": ["claude-3-opus", "claude-3-sonnet", "claude-3-haiku"],
      "label": "Model"
    }
  },
  "outputs": {
    "response": {
      "type": "string",
      "label": "Response"
    }
  },
  "handler": "Agents\\Anthropic\\ClaudeAgent"
}
```

## Using Models in Workflows

### Workflow Example: Multi-Model Comparison

```
Trigger: Form Submitted → "Content Review"
  ↓
Tool: Get Submission Data
  ↓
Agent: ChatGPT Agent → Analyze Content
  ↓
Agent: Claude Agent → Analyze Content (Parallel)
  ↓
Agent: Gemini Agent → Analyze Content (Parallel)
  ↓
Tool: Compare Results
  ↓
Tool: Send Summary Report
```

### Workflow Example: Model Fallback

```
Trigger: User Request
  ↓
Agent: Try GPT-4
  ↓
Condition: Success?
  ├─ Yes → Return Response
  └─ No → Agent: Try Claude (Fallback)
      ↓
      Condition: Success?
      ├─ Yes → Return Response
      └─ No → Agent: Try Gemini (Final Fallback)
```

## Model Provider Handler Example

Create `includes/integrations/openai/class-openai-provider.php`:

```php
<?php
namespace Integrations\OpenAI;

use WP_Agentic_Workflow\Integrations\LLM_Provider_Base;

class OpenAI_Provider extends LLM_Provider_Base {
    
    protected $api_key;
    protected $base_url = 'https://api.openai.com/v1';
    
    public function __construct($config) {
        parent::__construct($config);
        $this->api_key = $this->get_api_key();
    }
    
    public function chat($messages, $options = []) {
        $model = $options['model'] ?? $this->config['settings']['default_model'];
        $temperature = $options['temperature'] ?? $this->config['settings']['default_temperature'];
        $max_tokens = $options['max_tokens'] ?? $this->config['settings']['default_max_tokens'];
        
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
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('OpenAI API error: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'response' => $body['choices'][0]['message']['content'],
            'model_used' => $model,
            'tokens_used' => $body['usage']['total_tokens'],
            'cost' => $this->calculate_cost($body['usage'], $model)
        ];
    }
    
    protected function calculate_cost($usage, $model) {
        $model_config = $this->get_model_config($model);
        $cost_per_1k = $model_config['cost_per_1k_tokens'] ?? 0;
        return ($usage['total_tokens'] / 1000) * $cost_per_1k;
    }
    
    protected function get_api_key() {
        // Retrieve encrypted API key from database
        return get_option('aw_openai_api_key', '');
    }
}
```

## Model Registry

The plugin maintains a registry of all available models:

```php
// Get model registry
$model_registry = WP_Agentic_Workflow()->get_model_registry();

// List all available models
$models = $model_registry->get_all_models();

// Get models by provider
$openai_models = $model_registry->get_models_by_provider('openai');

// Get model details
$model_info = $model_registry->get_model('gpt-4');
```

## Best Practices

1. **Cost Optimization**: Use cheaper models (GPT-3.5, Claude Haiku) for simple tasks
2. **Model Selection**: Choose models based on task requirements (code → DeepSeek Coder, analysis → Claude)
3. **Fallback Strategy**: Configure fallback models for reliability
4. **Token Management**: Set appropriate max_tokens to control costs
5. **Caching**: Cache responses for repeated queries
6. **Rate Limiting**: Respect API rate limits for each provider

## Adding Custom Models

To add a custom or open-source model:

1. Create integration config file
2. Implement LLM_Provider_Base class
3. Define model specifications
4. Register with model registry

Example for a custom API endpoint:

```json
{
  "id": "custom_llm",
  "name": "Custom LLM",
  "api_base_url": "https://your-api.com/v1",
  "endpoints": {
    "chat": "/chat"
  },
  "models": [
    {
      "id": "custom-model-1",
      "name": "Custom Model",
      "max_tokens": 4096
    }
  ]
}
```

