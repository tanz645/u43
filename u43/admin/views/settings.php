<?php
/**
 * Settings View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$openai_api_key = \U43\Config\Settings_Manager::get('u43_openai_api_key', '');

// Get WhatsApp settings
$whatsapp_phone_number = \U43\Config\Settings_Manager::get('u43_whatsapp_phone_number', '');
$whatsapp_phone_number_id = \U43\Config\Settings_Manager::get('u43_whatsapp_phone_number_id', '');
$whatsapp_api_token = \U43\Config\Settings_Manager::get('u43_whatsapp_api_token', '');
$whatsapp_business_id = \U43\Config\Settings_Manager::get('u43_whatsapp_business_id', '');
$whatsapp_webhook_url = \U43\Config\Settings_Manager::get('u43_whatsapp_webhook_url', '');
$whatsapp_webhook_verify_token = \U43\Config\Settings_Manager::get('u43_whatsapp_webhook_verify_token', '');
$whatsapp_auth_method = \U43\Config\Settings_Manager::get('u43_whatsapp_auth_method', 'phone_token');

// Generate webhook URL if not set - force HTTPS for Meta webhook requirement
if (empty($whatsapp_webhook_url)) {
    $whatsapp_webhook_url = rest_url('u43/v1/webhooks/whatsapp');
    // Force HTTPS - Meta requires SSL for webhook verification
    $whatsapp_webhook_url = set_url_scheme($whatsapp_webhook_url, 'https');
}

// Get connection status
$whatsapp_connection_status = \U43\Config\Settings_Manager::get('u43_whatsapp_connection_status', 'disconnected');
?>

<div class="wrap">
    <h1><?php esc_html_e('U43 Settings', 'u43'); ?></h1>
    
    <!-- OpenAI Settings Section -->
    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=u43-settings')); ?>">
        <?php wp_nonce_field('u43_settings'); ?>
        
        <h2><?php esc_html_e('OpenAI Configuration', 'u43'); ?></h2>
        <p><?php esc_html_e('Configure your OpenAI API key to enable AI-powered workflows.', 'u43'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="openai_api_key"><?php esc_html_e('OpenAI API Key', 'u43'); ?></label>
                </th>
                <td>
                    <input type="password" name="openai_api_key" id="openai_api_key" class="regular-text" value="<?php echo esc_attr($openai_api_key); ?>" placeholder="sk-...">
                    <p class="description">
                        <?php esc_html_e('Get your API key from', 'u43'); ?>
                        <a href="https://platform.openai.com/api-keys" target="_blank"><?php esc_html_e('OpenAI Platform', 'u43'); ?></a>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="u43_save_settings" class="button button-primary" value="<?php esc_attr_e('Save OpenAI Settings', 'u43'); ?>">
        </p>
    </form>
    
    <hr class="wp-header-end" style="margin: 30px 0;">
    
    <!-- WhatsApp Settings Section -->
    <div class="u43-whatsapp-settings">
        <h2><?php esc_html_e('WhatsApp Integration', 'u43'); ?></h2>
        <p><?php esc_html_e('Configure WhatsApp integration for messaging and automation workflows.', 'u43'); ?></p>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=u43-settings')); ?>">
            <?php wp_nonce_field('u43_whatsapp_settings'); ?>
            
            <h3><?php esc_html_e('Connection Method', 'u43'); ?></h3>
            <p><?php esc_html_e('Choose how you want to connect to WhatsApp:', 'u43'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="whatsapp_auth_method"><?php esc_html_e('Authentication Method', 'u43'); ?></label>
                    </th>
                    <td>
                        <select name="whatsapp_auth_method" id="whatsapp_auth_method" class="regular-text">
                            <option value="phone_token" <?php selected($whatsapp_auth_method, 'phone_token'); ?>>
                                <?php esc_html_e('Phone Number + API Token', 'u43'); ?>
                            </option>
                            <option value="webhook_business" <?php selected($whatsapp_auth_method, 'webhook_business'); ?>>
                                <?php esc_html_e('Webhook + Business ID', 'u43'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the authentication method you want to use for WhatsApp integration.', 'u43'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <!-- Phone Number + API Token Method -->
            <div id="phone_token_method" class="auth-method-section">
                <h3><?php esc_html_e('Phone Number + API Token', 'u43'); ?></h3>
                <p><?php esc_html_e('Use this method if you have a WhatsApp Business API account with an API token.', 'u43'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_phone_number"><?php esc_html_e('Phone Number', 'u43'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                name="whatsapp_phone_number" 
                                id="whatsapp_phone_number" 
                                class="regular-text" 
                                value="<?php echo esc_attr($whatsapp_phone_number); ?>" 
                                placeholder="+1234567890"
                            >
                            <p class="description">
                                <?php esc_html_e('WhatsApp phone number with country code (e.g., +1234567890)', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_api_token"><?php esc_html_e('API Token', 'u43'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="password" 
                                name="whatsapp_api_token" 
                                id="whatsapp_api_token" 
                                class="regular-text" 
                                value="<?php echo esc_attr($whatsapp_api_token); ?>" 
                                placeholder="Your API token"
                            >
                            <p class="description">
                                <?php esc_html_e('Your WhatsApp Business API access token. This will be encrypted and stored securely.', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_phone_number_id"><?php esc_html_e('Phone Number ID', 'u43'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                name="whatsapp_phone_number_id" 
                                id="whatsapp_phone_number_id" 
                                class="regular-text" 
                                value="<?php echo esc_attr($whatsapp_phone_number_id); ?>" 
                                placeholder="e.g., 867714716428402"
                            >
                            <p class="description">
                                <?php esc_html_e('Your WhatsApp Business Phone Number ID from Meta Business. This is required to send messages via WhatsApp Cloud API.', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Webhook + Business ID Method -->
            <div id="webhook_business_method" class="auth-method-section">
                <h3><?php esc_html_e('Webhook + Business ID', 'u43'); ?></h3>
                <p><?php esc_html_e('Use this method for WhatsApp Cloud API integration.', 'u43'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_business_id"><?php esc_html_e('Business ID', 'u43'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                name="whatsapp_business_id" 
                                id="whatsapp_business_id" 
                                class="regular-text" 
                                value="<?php echo esc_attr($whatsapp_business_id); ?>" 
                                placeholder="Your Business Account ID"
                            >
                            <p class="description">
                                <?php esc_html_e('Your WhatsApp Business Account ID from Meta Business.', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_webhook_url"><?php esc_html_e('Webhook URL', 'u43'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="url" 
                                name="whatsapp_webhook_url" 
                                id="whatsapp_webhook_url" 
                                class="regular-text" 
                                value="<?php echo esc_attr($whatsapp_webhook_url); ?>" 
                                readonly
                            >
                            <p class="description">
                                <?php esc_html_e('This is your webhook endpoint URL. Use this URL when configuring your WhatsApp webhook in Meta Business.', 'u43'); ?>
                                <?php
                                // Check if HTTPS is available
                                $webhook_url_scheme = parse_url($whatsapp_webhook_url, PHP_URL_SCHEME);
                                if ($webhook_url_scheme !== 'https') {
                                    echo '<br><strong style="color: #d63638;">⚠ ' . esc_html__('Warning: Meta requires HTTPS for webhook verification. Please ensure your site uses SSL/HTTPS.', 'u43') . '</strong>';
                                } else {
                                    echo '<br><span style="color: #00a32a;">✓ ' . esc_html__('HTTPS is enabled. This URL is ready for Meta webhook verification.', 'u43') . '</span>';
                                }
                                ?>
                            </p>
                            <p>
                                <button type="button" id="test_webhook" class="button">
                                    <?php esc_html_e('Test Webhook Verification', 'u43'); ?>
                                </button>
                                <span id="webhook_test_result" style="margin-left: 10px;"></span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_webhook_verify_token"><?php esc_html_e('Webhook Verify Token (verifyToken)', 'u43'); ?></label>
                        </th>
                        <td>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input 
                                    type="text" 
                                    name="whatsapp_webhook_verify_token" 
                                    id="whatsapp_webhook_verify_token" 
                                    class="regular-text" 
                                    value="<?php echo esc_attr($whatsapp_webhook_verify_token); ?>" 
                                    placeholder="Enter a secure verify token"
                                    style="flex: 1;"
                                >
                                <button type="button" id="generate_verify_token" class="button button-secondary" title="<?php esc_attr_e('Generate a random secure token', 'u43'); ?>">
                                    <?php esc_html_e('Generate', 'u43'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php esc_html_e('This is your verifyToken field. Create a secure random string and use the exact same value when configuring your webhook in Meta Business Center. When Meta verifies your webhook, they send: GET /webhook?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=CHALLENGE. Your server must match the token and return the challenge.', 'u43'); ?>
                            </p>
                            <?php if (empty($whatsapp_webhook_verify_token)): ?>
                                <p class="description" style="color: #d63638; font-weight: bold;">
                                    <?php esc_html_e('⚠ Warning: Verify token is required for webhook verification. Please set a token before configuring your webhook in Meta Business.', 'u43'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <h3><?php esc_html_e('Connection Status', 'u43'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'u43'); ?></th>
                    <td>
                        <span id="connection_status" class="status-indicator status-<?php echo esc_attr($whatsapp_connection_status); ?>">
                            <?php 
                            if ($whatsapp_connection_status === 'connected') {
                                esc_html_e('Connected', 'u43');
                            } else {
                                esc_html_e('Disconnected', 'u43');
                            }
                            ?>
                        </span>
                        <button type="button" id="test_connection" class="button" style="margin-left: 10px;">
                            <?php esc_html_e('Test Connection', 'u43'); ?>
                        </button>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="u43_save_whatsapp_settings" class="button button-primary" value="<?php esc_attr_e('Save WhatsApp Settings', 'u43'); ?>">
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide auth method sections based on saved method
    function updateAuthMethodDisplay() {
        var selectedMethod = $('#whatsapp_auth_method').val();
        
        if (!selectedMethod) {
            return;
        }
        
        // Hide all sections first
        $('.auth-method-section').each(function() {
            $(this).removeClass('active highlighted');
            $(this).hide();
            $(this).css({
                'background': '#f9f9f9',
                'border': '1px solid #ddd',
                'box-shadow': 'none'
            });
        });
        
        // Show and highlight selected section
        var $selectedSection = $('#' + selectedMethod + '_method');
        if ($selectedSection.length > 0) {
            $selectedSection.addClass('active highlighted');
            $selectedSection.show();
            $selectedSection.css({
                'background': '#e8f4f8',
                'border': '2px solid #21759b',
                'box-shadow': '0 2px 8px rgba(33, 117, 155, 0.2)'
            });
        }
    }
    
    // Initialize display on page load
    updateAuthMethodDisplay();
    
    // Also initialize after a short delay to ensure DOM is ready
    setTimeout(function() {
        updateAuthMethodDisplay();
    }, 200);
    
    // Update display when method changes (immediately, not on save)
    $(document).on('change', '#whatsapp_auth_method', function() {
        updateAuthMethodDisplay();
    });
    
    // Test connection
    $(document).on('click', '#test_connection', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('<?php echo esc_js(__('Testing...', 'u43')); ?>');
        
        var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'u43_test_whatsapp_connection',
                nonce: '<?php echo wp_create_nonce('u43_test_whatsapp'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#connection_status').removeClass('status-disconnected').addClass('status-connected').text('<?php echo esc_js(__('Connected', 'u43')); ?>');
                    alert('<?php echo esc_js(__('Connection successful!', 'u43')); ?>');
                } else {
                    var errorMsg = '<?php echo esc_js(__('Connection failed: ', 'u43')); ?>';
                    if (response.data && response.data.message) {
                        errorMsg += response.data.message;
                    } else if (response.message) {
                        errorMsg += response.message;
                    } else {
                        errorMsg += '<?php echo esc_js(__('Unknown error', 'u43')); ?>';
                    }
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = '<?php echo esc_js(__('Error testing connection', 'u43')); ?>';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg += ': ' + xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data && errorResponse.data.message) {
                            errorMsg += ': ' + errorResponse.data.message;
                        }
                    } catch(e) {
                        errorMsg += ': ' + error;
                    }
                } else {
                    errorMsg += ': ' + error;
                }
                alert(errorMsg);
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__('Test Connection', 'u43')); ?>');
            }
        });
    });
    
    // Test webhook verification
    $(document).on('click', '#test_webhook', function() {
        var $button = $(this);
        var $result = $('#webhook_test_result');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Testing...', 'u43')); ?>');
        $result.html('');
        
        var verifyToken = $('#whatsapp_webhook_verify_token').val();
        if (!verifyToken) {
            $result.html('<span style="color: red;"><?php echo esc_js(__('Please set a verify token first', 'u43')); ?></span>');
            $button.prop('disabled', false).text('<?php echo esc_js(__('Test Webhook Verification', 'u43')); ?>');
            return;
        }
        
        var webhookUrl = $('#whatsapp_webhook_url').val();
        if (!webhookUrl) {
            $result.html('<span style="color: red;"><?php echo esc_js(__('Webhook URL not found', 'u43')); ?></span>');
            $button.prop('disabled', false).text('<?php echo esc_js(__('Test Webhook Verification', 'u43')); ?>');
            return;
        }
        
        // Simulate WhatsApp verification request
        var testUrl = webhookUrl + '?hub.mode=subscribe&hub.verify_token=' + encodeURIComponent(verifyToken) + '&hub.challenge=test_challenge_12345';
        
        $.ajax({
            url: testUrl,
            method: 'GET',
            success: function(response) {
                if (response === 'test_challenge_12345') {
                    $result.html('<span style="color: green;">✓ <?php echo esc_js(__('Webhook verification successful! The endpoint is working correctly.', 'u43')); ?></span>');
                } else {
                    $result.html('<span style="color: orange;">⚠ <?php echo esc_js(__('Webhook responded but challenge mismatch. Response: ', 'u43')); ?>' + response + '</span>');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = '<?php echo esc_js(__('Webhook test failed', 'u43')); ?>';
                if (xhr.status === 403) {
                    errorMsg = '<?php echo esc_js(__('Verification failed. Check that your verify token matches.', 'u43')); ?>';
                } else if (xhr.status === 404) {
                    errorMsg = '<?php echo esc_js(__('Webhook endpoint not found. Please check your WordPress permalink settings.', 'u43')); ?>';
                } else {
                    errorMsg += ': ' + (xhr.responseJSON?.message || error || '<?php echo esc_js(__('Unknown error', 'u43')); ?>');
                }
                $result.html('<span style="color: red;">✗ ' + errorMsg + '</span>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__('Test Webhook Verification', 'u43')); ?>');
            }
        });
    });
    
    // Generate verify token
    $(document).on('click', '#generate_verify_token', function() {
        // Generate a random secure token (32 characters) - alphanumeric
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var token = '';
        for (var i = 0; i < 32; i++) {
            token += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#whatsapp_webhook_verify_token').val(token);
    });
});
</script>

<style>
.status-indicator {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: bold;
}
.status-connected {
    background-color: #46b450;
    color: white;
}
.status-disconnected {
    background-color: #dc3232;
    color: white;
}
.auth-method-section {
    margin: 20px 0 !important;
    padding: 20px !important;
    background: #f9f9f9 !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    display: none !important;
    transition: all 0.3s ease !important;
}
.auth-method-section.active {
    display: block !important;
}
.auth-method-section.highlighted,
.auth-method-section.active.highlighted {
    background: #e8f4f8 !important;
    border: 2px solid #21759b !important;
    box-shadow: 0 2px 8px rgba(33, 117, 155, 0.2) !important;
}
.auth-method-section h3 {
    margin-top: 0 !important;
}
</style>

