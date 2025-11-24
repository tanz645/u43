<?php
/**
 * Settings View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$openai_api_key = get_option('u43_openai_api_key', '');
?>

<div class="wrap">
    <h1><?php esc_html_e('U43 Settings', 'u43'); ?></h1>
    
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
            <input type="submit" name="u43_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'u43'); ?>">
        </p>
    </form>
</div>

