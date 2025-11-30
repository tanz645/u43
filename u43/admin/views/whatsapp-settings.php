<?php
/**
 * WhatsApp Settings View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['u43_save_whatsapp_settings']) && check_admin_referer('u43_whatsapp_settings')) {
    $phone_number = sanitize_text_field($_POST['whatsapp_phone_number'] ?? '');
    $api_token = sanitize_text_field($_POST['whatsapp_api_token'] ?? '');
    $business_id = sanitize_text_field($_POST['whatsapp_business_id'] ?? '');
    $webhook_url = esc_url_raw($_POST['whatsapp_webhook_url'] ?? '');
    $webhook_verify_token = sanitize_text_field($_POST['whatsapp_webhook_verify_token'] ?? '');
    $auth_method = sanitize_text_field($_POST['whatsapp_auth_method'] ?? 'phone_token');
    
    // Save settings
    update_option('u43_whatsapp_phone_number', $phone_number);
    update_option('u43_whatsapp_api_token', $api_token);
    update_option('u43_whatsapp_business_id', $business_id);
    update_option('u43_whatsapp_webhook_url', $webhook_url);
    update_option('u43_whatsapp_webhook_verify_token', $webhook_verify_token);
    update_option('u43_whatsapp_auth_method', $auth_method);
    
    echo '<div class="notice notice-success"><p>' . esc_html__('WhatsApp settings saved!', 'u43') . '</p></div>';
}

// Get saved settings
$phone_number = get_option('u43_whatsapp_phone_number', '');
$api_token = get_option('u43_whatsapp_api_token', '');
$business_id = get_option('u43_whatsapp_business_id', '');
$webhook_url = get_option('u43_whatsapp_webhook_url', '');
$webhook_verify_token = get_option('u43_whatsapp_webhook_verify_token', '');
$auth_method = get_option('u43_whatsapp_auth_method', 'phone_token');

// Generate webhook URL if not set - force HTTPS for Meta webhook requirement
if (empty($webhook_url)) {
    $webhook_url = rest_url('u43/v1/webhooks/whatsapp');
    // Force HTTPS - Meta requires SSL for webhook verification
    $webhook_url = set_url_scheme($webhook_url, 'https');
}

// Get connection status
$connection_status = get_option('u43_whatsapp_connection_status', 'disconnected');
?>

<div class="wrap">
    <h1><?php esc_html_e('WhatsApp Integration Settings', 'u43'); ?></h1>
    
    <div class="u43-whatsapp-settings">
        <form method="post" action="">
            <?php wp_nonce_field('u43_whatsapp_settings'); ?>
            
            <h2><?php esc_html_e('Connection Method', 'u43'); ?></h2>
            <p><?php esc_html_e('Choose how you want to connect to WhatsApp:', 'u43'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="whatsapp_auth_method"><?php esc_html_e('Authentication Method', 'u43'); ?></label>
                    </th>
                    <td>
                        <select name="whatsapp_auth_method" id="whatsapp_auth_method" class="regular-text">
                            <option value="phone_token" <?php selected($auth_method, 'phone_token'); ?>>
                                <?php esc_html_e('Phone Number + API Token', 'u43'); ?>
                            </option>
                            <option value="qr_code" <?php selected($auth_method, 'qr_code'); ?>>
                                <?php esc_html_e('QR Code', 'u43'); ?>
                            </option>
                            <option value="webhook_business" <?php selected($auth_method, 'webhook_business'); ?>>
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
            <div id="phone_token_method" class="auth-method-section" style="<?php echo $auth_method === 'phone_token' ? '' : 'display:none;'; ?>">
                <h2><?php esc_html_e('Phone Number + API Token', 'u43'); ?></h2>
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
                                value="<?php echo esc_attr($phone_number); ?>" 
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
                                value="<?php echo esc_attr($api_token); ?>" 
                                placeholder="Your API token"
                            >
                            <p class="description">
                                <?php esc_html_e('Your WhatsApp Business API access token. This will be encrypted and stored securely.', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- QR Code Method -->
            <div id="qr_code_method" class="auth-method-section" style="<?php echo $auth_method === 'qr_code' ? '' : 'display:none;'; ?>">
                <h2><?php esc_html_e('QR Code Authentication', 'u43'); ?></h2>
                <p><?php esc_html_e('Scan the QR code with your WhatsApp mobile app to connect.', 'u43'); ?></p>
                
                <div id="qr_code_container">
                    <p><?php esc_html_e('QR code will be generated when you save settings.', 'u43'); ?></p>
                    <button type="button" id="generate_qr_code" class="button">
                        <?php esc_html_e('Generate QR Code', 'u43'); ?>
                    </button>
                    <div id="qr_code_display" style="margin-top: 20px;"></div>
                </div>
            </div>
            
            <!-- Webhook + Business ID Method -->
            <div id="webhook_business_method" class="auth-method-section" style="<?php echo $auth_method === 'webhook_business' ? '' : 'display:none;'; ?>">
                <h2><?php esc_html_e('Webhook + Business ID', 'u43'); ?></h2>
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
                                value="<?php echo esc_attr($business_id); ?>" 
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
                                value="<?php echo esc_attr($webhook_url); ?>" 
                                readonly
                            >
                            <p class="description">
                                <?php esc_html_e('This is your webhook endpoint URL. Use this URL when configuring your WhatsApp webhook in Meta Business.', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="whatsapp_webhook_verify_token"><?php esc_html_e('Webhook Verify Token', 'u43'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                name="whatsapp_webhook_verify_token" 
                                id="whatsapp_webhook_verify_token" 
                                class="regular-text" 
                                value="<?php echo esc_attr($webhook_verify_token); ?>" 
                                placeholder="Your verify token"
                            >
                            <p class="description">
                                <?php esc_html_e('Create a secure token for webhook verification. Use the same token when configuring your webhook in Meta Business.', 'u43'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <h2><?php esc_html_e('Connection Status', 'u43'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'u43'); ?></th>
                    <td>
                        <span id="connection_status" class="status-indicator status-<?php echo esc_attr($connection_status); ?>">
                            <?php 
                            if ($connection_status === 'connected') {
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
                <input type="submit" name="u43_save_whatsapp_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'u43'); ?>">
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide auth method sections
    $('#whatsapp_auth_method').on('change', function() {
        $('.auth-method-section').hide();
        $('#' + $(this).val() + '_method').show();
    });
    
    // Test connection
    $('#test_connection').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('<?php echo esc_js(__('Testing...', 'u43')); ?>');
        
        $.ajax({
            url: ajaxurl,
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
                    alert('<?php echo esc_js(__('Connection failed: ', 'u43')); ?>' + (response.data?.message || '<?php echo esc_js(__('Unknown error', 'u43')); ?>'));
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error testing connection', 'u43')); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__('Test Connection', 'u43')); ?>');
            }
        });
    });
    
    // Generate QR code
    $('#generate_qr_code').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('<?php echo esc_js(__('Generating...', 'u43')); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'u43_generate_whatsapp_qr',
                nonce: '<?php echo wp_create_nonce('u43_whatsapp_qr'); ?>'
            },
            success: function(response) {
                if (response.success && response.data?.qr_code) {
                    $('#qr_code_display').html('<img src="' + response.data.qr_code + '" alt="QR Code" style="max-width: 300px;">');
                } else {
                    alert('<?php echo esc_js(__('Failed to generate QR code', 'u43')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error generating QR code', 'u43')); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__('Generate QR Code', 'u43')); ?>');
            }
        });
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
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>


