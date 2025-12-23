<?php
/**
 * Create Campaign View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$contact_manager = new \U43\Campaigns\Contact_Manager();
$tags = $contact_manager->get_tags();
$folders = $contact_manager->get_folders();
?>

<div class="wrap">
    <h1><?php esc_html_e('Create Campaign', 'u43'); ?></h1>
    
    <form id="create-campaign-form" method="post">
        <table class="form-table">
            <tr>
                <th><label for="campaign_name"><?php esc_html_e('Campaign Name', 'u43'); ?></label></th>
                <td><input type="text" id="campaign_name" name="campaign_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="campaign_description"><?php esc_html_e('Description', 'u43'); ?></label></th>
                <td><textarea id="campaign_description" name="campaign_description" class="large-text" rows="3"></textarea></td>
            </tr>
            <tr>
                <th><label for="message_type"><?php esc_html_e('Message Type', 'u43'); ?></label></th>
                <td>
                    <select id="message_type" name="message_type">
                        <option value="text"><?php esc_html_e('Plain Text', 'u43'); ?></option>
                        <option value="template"><?php esc_html_e('Template', 'u43'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="message_text_row">
                <th><label for="message_text"><?php esc_html_e('Message Text', 'u43'); ?></label></th>
                <td>
                    <textarea id="message_text" name="message_text" class="large-text" rows="5"></textarea>
                    <p class="description">
                        <?php esc_html_e('Available variables:', 'u43'); ?>
                        <span id="variable-list" style="margin-left: 10px;"></span>
                    </p>
                    <p class="description" style="margin-top: 5px;">
                        <button type="button" id="insert-variable-btn" class="button button-small" style="display:none;">
                            <?php esc_html_e('Insert Variable', 'u43'); ?>
                        </button>
                        <select id="variable-selector" style="display:none; margin-left: 5px;">
                            <option value=""><?php esc_html_e('Select a variable...', 'u43'); ?></option>
                        </select>
                    </p>
                </td>
            </tr>
            <tr id="template_row" style="display:none;">
                <th><label for="template_name"><?php esc_html_e('Template Name', 'u43'); ?></label></th>
                <td>
                    <input type="text" id="template_name" name="template_name" class="regular-text" placeholder="approved_template_name">
                    <p class="description"><?php esc_html_e('Enter the name of your approved WhatsApp template', 'u43'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="target_type"><?php esc_html_e('Target Segment', 'u43'); ?></label></th>
                <td>
                    <select id="target_type" name="target_type">
                        <option value="all"><?php esc_html_e('All Contacts', 'u43'); ?></option>
                        <option value="tags"><?php esc_html_e('By Tags', 'u43'); ?></option>
                        <option value="folder"><?php esc_html_e('By Folder', 'u43'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="target_tags_row" style="display:none;">
                <th><label><?php esc_html_e('Select Tags', 'u43'); ?></label></th>
                <td>
                    <?php foreach ($tags as $tag): ?>
                        <label><input type="checkbox" name="target_tags[]" value="<?php echo esc_attr($tag->id); ?>"> <?php echo esc_html($tag->name); ?></label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr id="target_folder_row" style="display:none;">
                <th><label for="target_folder"><?php esc_html_e('Select Folder', 'u43'); ?></label></th>
                <td>
                    <select id="target_folder" name="target_folder">
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo esc_attr($folder->id); ?>"><?php echo esc_html($folder->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="schedule_type"><?php esc_html_e('Schedule', 'u43'); ?></label></th>
                <td>
                    <select id="schedule_type" name="schedule_type">
                        <option value="immediate"><?php esc_html_e('Send Immediately', 'u43'); ?></option>
                        <option value="scheduled"><?php esc_html_e('Schedule for Later', 'u43'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="scheduled_at_row" style="display:none;">
                <th><label for="scheduled_at"><?php esc_html_e('Scheduled Date/Time', 'u43'); ?></label></th>
                <td><input type="datetime-local" id="scheduled_at" name="scheduled_at" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="batch_size"><?php esc_html_e('Batch Size', 'u43'); ?></label></th>
                <td>
                    <input type="number" id="batch_size" name="batch_size" value="100" min="50" max="200" class="small-text">
                    <p class="description"><?php esc_html_e('Number of messages to send per batch (50-200)', 'u43'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Create Campaign', 'u43'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns')); ?>" class="button"><?php esc_html_e('Cancel', 'u43'); ?></a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var contactVariables = [];
    
    // Load contact variables
    $.ajax({
        url: '<?php echo esc_url(rest_url('u43/v1/campaigns/contact-variables')); ?>',
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
        },
        success: function(response) {
            contactVariables = response;
            var variableList = response.map(function(v) {
                return '<code style="cursor: pointer; margin-right: 10px;" title="' + v.description + '" data-variable="' + v.example + '">' + v.example + '</code>';
            }).join('');
            $('#variable-list').html(variableList);
            $('#insert-variable-btn, #variable-selector').show();
            
            // Populate selector
            response.forEach(function(v) {
                $('#variable-selector').append('<option value="' + v.example + '">' + v.label + ' (' + v.example + ')</option>');
            });
            
            // Click handler for variable codes
            $('#variable-list code').on('click', function() {
                var variable = $(this).data('variable');
                insertVariable(variable);
            });
        },
        error: function() {
            console.error('Failed to load contact variables');
        }
    });
    
    // Insert variable function
    function insertVariable(variable) {
        var textarea = $('#message_text');
        var start = textarea[0].selectionStart;
        var end = textarea[0].selectionEnd;
        var text = textarea.val();
        var before = text.substring(0, start);
        var after = text.substring(end, text.length);
        
        textarea.val(before + variable + after);
        textarea[0].selectionStart = textarea[0].selectionEnd = start + variable.length;
        textarea.focus();
    }
    
    // Insert variable button handler
    $('#insert-variable-btn').on('click', function() {
        var variable = $('#variable-selector').val();
        if (variable) {
            insertVariable(variable);
            $('#variable-selector').val('');
        }
    });
    
    $('#message_type').on('change', function() {
        if ($(this).val() === 'template') {
            $('#message_text_row').hide();
            $('#template_row').show();
        } else {
            $('#message_text_row').show();
            $('#template_row').hide();
        }
    });
    
    $('#target_type').on('change', function() {
        $('#target_tags_row, #target_folder_row').hide();
        if ($(this).val() === 'tags') {
            $('#target_tags_row').show();
        } else if ($(this).val() === 'folder') {
            $('#target_folder_row').show();
        }
    });
    
    $('#schedule_type').on('change', function() {
        if ($(this).val() === 'scheduled') {
            $('#scheduled_at_row').show();
        } else {
            $('#scheduled_at_row').hide();
        }
    });
    
    $('#create-campaign-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            name: $('#campaign_name').val(),
            description: $('#campaign_description').val(),
            message_text: $('#message_text').val(),
            template_name: $('#template_name').val(),
            target_type: $('#target_type').val(),
            schedule_type: $('#schedule_type').val(),
            scheduled_at: $('#scheduled_at').val(),
            batch_size: $('#batch_size').val()
        };
        
        // Get target value
        if (formData.target_type === 'tags') {
            formData.target_value = $('input[name="target_tags[]"]:checked').map(function() {
                return $(this).val();
            }).get();
        } else if (formData.target_type === 'folder') {
            formData.target_value = $('#target_folder').val();
        }
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/campaigns')); ?>',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                window.location.href = '<?php echo esc_url(admin_url('admin.php?page=u43-campaigns')); ?>';
            },
            error: function(xhr) {
                alert('Error creating campaign: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>

