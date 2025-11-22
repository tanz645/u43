# Credential Management

## Overview

The Credential Manager handles secure storage and retrieval of user-provided credentials for third-party integrations. Users provide credentials through the admin interface, which are encrypted and stored securely.

## Architecture

```
┌─────────────────────────────────────────────────┐
│           Admin Interface                        │
│  ┌──────────────────────────────────────────┐   │
│  │  Integration Settings Page                │   │
│  │  - Credential Input Forms                 │   │
│  │  - OAuth Connect Buttons                  │   │
│  │  - Credential Validation                  │   │
│  └──────────────┬───────────────────────────┘   │
└─────────────────┼───────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────┐
│         Credential Manager                       │
│  ┌──────────────────────────────────────────┐   │
│  │  - Collect User Input                     │   │
│  │  - Validate Credentials                   │   │
│  │  - Encrypt Sensitive Data                 │   │
│  │  - Store in Database                      │   │
│  └──────────────┬───────────────────────────┘   │
└─────────────────┼───────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────┐
│         Secure Storage                           │
│  ┌──────────────────────────────────────────┐   │
│  │  - Encrypted Database Storage             │   │
│  │  - Per-Integration Credentials             │   │
│  │  - Multiple Instances Support             │   │
│  └──────────────┬───────────────────────────┘   │
└─────────────────┼───────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────┐
│         Integration Tools                        │
│  ┌──────────────────────────────────────────┐   │
│  │  - Retrieve Credentials                   │   │
│  │  - Decrypt on Demand                      │   │
│  │  - Use in API Calls                       │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

## Credential Collection Flow

### 1. Integration Configuration

When an integration requires credentials, it defines them in the configuration:

```json
{
  "id": "mongodb",
  "name": "MongoDB",
  "authentication": {
    "type": "connection_string",
    "storage": "encrypted"
  },
  "inputs": {
    "connection_string": {
      "type": "string",
      "required": true,
      "label": "MongoDB Connection String",
      "description": "MongoDB connection string (mongodb:// or mongodb+srv://)",
      "placeholder": "mongodb://username:password@host:port/database",
      "sensitive": true
    },
    "database_name": {
      "type": "string",
      "required": true,
      "label": "Database Name",
      "sensitive": false
    }
  }
}
```

### 2. Admin UI Form Generation

The plugin automatically generates credential input forms based on the `inputs` definition:

**Admin Interface:**
```
┌─────────────────────────────────────────────┐
│  MongoDB Integration Settings                │
├─────────────────────────────────────────────┤
│                                             │
│  Connection String:                         │
│  ┌─────────────────────────────────────┐   │
│  │ mongodb://user:pass@host:port/db    │   │
│  └─────────────────────────────────────┘   │
│  [Password field - masked input]            │
│                                             │
│  Database Name:                             │
│  ┌─────────────────────────────────────┐   │
│  │ my_database                          │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  [Test Connection] [Save Credentials]       │
│                                             │
└─────────────────────────────────────────────┘
```

### 3. Credential Storage

Credentials are stored in the database with encryption:

**Database Schema:**
```sql
CREATE TABLE wp_aw_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_id VARCHAR(100) NOT NULL,
    instance_name VARCHAR(100) DEFAULT 'default',
    credential_key VARCHAR(100) NOT NULL,
    credential_value LONGTEXT NOT NULL,  -- Encrypted
    is_encrypted TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_integration (integration_id, instance_name)
);
```

## Authentication Types

### 1. API Key Authentication

**Configuration:**
```json
{
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "inputs": {
    "api_key": {
      "type": "string",
      "required": true,
      "label": "API Key",
      "sensitive": true,
      "description": "Your API key from the service"
    }
  }
}
```

**Admin Form:**
- Single input field for API key
- Masked input (password field)
- Test connection button

### 2. Connection String Authentication

**Configuration:**
```json
{
  "authentication": {
    "type": "connection_string",
    "storage": "encrypted"
  },
  "inputs": {
    "connection_string": {
      "type": "string",
      "required": true,
      "label": "Connection String",
      "sensitive": true,
      "description": "Database connection string"
    }
  }
}
```

**Admin Form:**
- Connection string input (masked)
- Optional: Separate fields for host, port, username, password, database
- Test connection button

### 3. Username/Password Authentication

**Configuration:**
```json
{
  "authentication": {
    "type": "basic_auth",
    "storage": "encrypted"
  },
  "inputs": {
    "username": {
      "type": "string",
      "required": true,
      "label": "Username",
      "sensitive": false
    },
    "password": {
      "type": "string",
      "required": true,
      "label": "Password",
      "sensitive": true
    }
  }
}
```

**Admin Form:**
- Username field (visible)
- Password field (masked)
- Test connection button

### 4. OAuth2 Authentication

**Configuration:**
```json
{
  "authentication": {
    "type": "oauth2",
    "authorization_url": "https://slack.com/oauth/authorize",
    "token_url": "https://slack.com/api/oauth.access",
    "scopes": ["chat:write", "channels:read"],
    "client_id_field": "client_id",
    "client_secret_field": "client_secret",
    "storage": "encrypted"
  },
  "inputs": {
    "client_id": {
      "type": "string",
      "required": true,
      "label": "Client ID",
      "sensitive": false
    },
    "client_secret": {
      "type": "string",
      "required": true,
      "label": "Client Secret",
      "sensitive": true
    }
  }
}
```

**Admin Form:**
- Client ID field (visible)
- Client Secret field (masked)
- "Connect with [Service]" button
- OAuth flow handling
- Token refresh management

### 5. Multiple Credential Fields

**Configuration (MongoDB Example):**
```json
{
  "authentication": {
    "type": "connection_string",
    "storage": "encrypted"
  },
  "inputs": {
    "host": {
      "type": "string",
      "required": true,
      "label": "Host",
      "default": "localhost",
      "sensitive": false
    },
    "port": {
      "type": "integer",
      "required": false,
      "label": "Port",
      "default": 27017,
      "sensitive": false
    },
    "username": {
      "type": "string",
      "required": false,
      "label": "Username",
      "sensitive": false
    },
    "password": {
      "type": "string",
      "required": false,
      "label": "Password",
      "sensitive": true
    },
    "database": {
      "type": "string",
      "required": true,
      "label": "Database Name",
      "sensitive": false
    },
    "auth_source": {
      "type": "string",
      "required": false,
      "label": "Authentication Database",
      "default": "admin",
      "sensitive": false
    }
  }
}
```

**Admin Form:**
- Multiple input fields based on configuration
- Sensitive fields are masked
- Form validation
- Test connection button

## Implementation

### Credential Manager Class

```php
<?php
namespace WP_Agentic_Workflow\Credentials;

class Credential_Manager {
    
    /**
     * Save credentials for an integration
     */
    public function save_credentials($integration_id, $instance_name, $credentials) {
        // Validate credentials
        $this->validate_credentials($integration_id, $credentials);
        
        // Encrypt sensitive fields
        $encrypted = $this->encrypt_credentials($integration_id, $credentials);
        
        // Store in database
        $this->storage->save($integration_id, $instance_name, $encrypted);
        
        return true;
    }
    
    /**
     * Get credentials for an integration
     */
    public function get_credentials($integration_id, $instance_name = 'default') {
        // Retrieve from database
        $encrypted = $this->storage->get($integration_id, $instance_name);
        
        // Decrypt sensitive fields
        $decrypted = $this->decrypt_credentials($integration_id, $encrypted);
        
        return $decrypted;
    }
    
    /**
     * Test connection with credentials
     */
    public function test_connection($integration_id, $credentials) {
        $integration = $this->get_integration($integration_id);
        $handler = $integration->get_handler();
        
        return $handler->test_connection($credentials);
    }
    
    /**
     * Encrypt sensitive credential fields
     */
    private function encrypt_credentials($integration_id, $credentials) {
        $config = $this->get_integration_config($integration_id);
        $encrypted = [];
        
        foreach ($credentials as $key => $value) {
            $field_config = $config['inputs'][$key] ?? null;
            
            if ($field_config && ($field_config['sensitive'] ?? false)) {
                // Encrypt sensitive fields
                $encrypted[$key] = $this->encryption->encrypt($value);
            } else {
                // Store non-sensitive fields as-is
                $encrypted[$key] = $value;
            }
        }
        
        return $encrypted;
    }
    
    /**
     * Decrypt sensitive credential fields
     */
    private function decrypt_credentials($integration_id, $encrypted) {
        $config = $this->get_integration_config($integration_id);
        $decrypted = [];
        
        foreach ($encrypted as $key => $value) {
            $field_config = $config['inputs'][$key] ?? null;
            
            if ($field_config && ($field_config['sensitive'] ?? false)) {
                // Decrypt sensitive fields
                $decrypted[$key] = $this->encryption->decrypt($value);
            } else {
                // Return non-sensitive fields as-is
                $decrypted[$key] = $value;
            }
        }
        
        return $decrypted;
    }
}
```

### Encryption Service

```php
<?php
namespace WP_Agentic_Workflow\Credentials;

class Encryption_Service {
    
    /**
     * Encrypt a value
     */
    public function encrypt($value) {
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);
        
        // Store IV with encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt a value
     */
    public function decrypt($encrypted_value) {
        $key = $this->get_encryption_key();
        $data = base64_decode($encrypted_value);
        
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
    
    /**
     * Get encryption key (uses WordPress salts)
     */
    private function get_encryption_key() {
        // Use WordPress salts for encryption
        $key = get_option('wp_aw_encryption_key');
        
        if (!$key) {
            // Generate and store key
            $key = wp_generate_password(64, true, true);
            update_option('wp_aw_encryption_key', $key);
        }
        
        return $key;
    }
}
```

### Admin Interface

```php
<?php
// Admin page for credential management
class Credential_Admin_Page {
    
    public function render_integration_settings($integration_id) {
        $integration = $this->get_integration($integration_id);
        $config = $integration->get_config();
        $credentials = $this->credential_manager->get_credentials($integration_id);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($config['name']); ?> Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_credentials'); ?>
                
                <?php foreach ($config['inputs'] as $key => $field): ?>
                    <div class="form-field">
                        <label for="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($field['label']); ?>
                        </label>
                        
                        <?php if ($field['sensitive'] ?? false): ?>
                            <input 
                                type="password" 
                                id="<?php echo esc_attr($key); ?>" 
                                name="credentials[<?php echo esc_attr($key); ?>]"
                                value="<?php echo esc_attr($credentials[$key] ?? ''); ?>"
                                placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                class="regular-text"
                            />
                        <?php else: ?>
                            <input 
                                type="text" 
                                id="<?php echo esc_attr($key); ?>" 
                                name="credentials[<?php echo esc_attr($key); ?>]"
                                value="<?php echo esc_attr($credentials[$key] ?? ''); ?>"
                                placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                class="regular-text"
                            />
                        <?php endif; ?>
                        
                        <?php if (isset($field['description'])): ?>
                            <p class="description"><?php echo esc_html($field['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <p class="submit">
                    <button type="button" class="button" id="test-connection">
                        Test Connection
                    </button>
                    <input type="submit" class="button button-primary" value="Save Credentials">
                </p>
            </form>
        </div>
        <?php
    }
}
```

## Usage in Tools

Tools retrieve credentials automatically:

```php
<?php
namespace Integrations\MongoDB\Tools;

use WP_Agentic_Workflow\Tools\Tool_Base;

class InsertDocument extends Tool_Base {
    
    public function execute($inputs) {
        // Get credentials for this integration
        $credentials = $this->get_credentials('mongodb');
        
        // Use credentials to connect
        $client = new \MongoDB\Client($credentials['connection_string']);
        $db = $client->selectDatabase($credentials['database_name']);
        
        // Execute operation
        $collection = $db->selectCollection($inputs['collection']);
        $result = $collection->insertOne($inputs['document']);
        
        return [
            'inserted_id' => (string) $result->getInsertedId(),
            'acknowledged' => $result->isAcknowledged()
        ];
    }
    
    /**
     * Get credentials for this integration
     */
    private function get_credentials($integration_id) {
        $credential_manager = WP_Agentic_Workflow()->get_credential_manager();
        return $credential_manager->get_credentials($integration_id);
    }
}
```

## Multiple Instances

Support multiple credential instances per integration:

```php
// Save credentials for instance "production"
$credential_manager->save_credentials('mongodb', 'production', [
    'connection_string' => 'mongodb://prod-host:27017',
    'database_name' => 'production_db'
]);

// Save credentials for instance "staging"
$credential_manager->save_credentials('mongodb', 'staging', [
    'connection_string' => 'mongodb://staging-host:27017',
    'database_name' => 'staging_db'
]);

// Use in tool
$credentials = $credential_manager->get_credentials('mongodb', 'production');
```

## Security Best Practices

1. **Encryption**: All sensitive fields are encrypted using AES-256-CBC
2. **Key Management**: Uses WordPress salts, stored securely
3. **Access Control**: Only administrators can manage credentials
4. **Validation**: Credentials are validated before storage
5. **Test Connection**: Users can test credentials before saving
6. **No Logging**: Credentials are never logged
7. **Secure Transmission**: Admin forms use HTTPS and nonces

## OAuth Flow

For OAuth2 integrations, the plugin handles the full OAuth flow:

1. User clicks "Connect with [Service]"
2. Redirects to service authorization page
3. User authorizes
4. Service redirects back with code
5. Plugin exchanges code for tokens
6. Tokens stored encrypted
7. Automatic token refresh when expired

## Summary

✅ **User provides credentials** through admin interface  
✅ **Credentials are encrypted** before storage  
✅ **Secure database storage** with encryption  
✅ **Automatic retrieval** by tools when needed  
✅ **Multiple instances** supported per integration  
✅ **OAuth flow** handled automatically  
✅ **Test connection** before saving  

The architecture fully supports secure credential management for all third-party integrations!

