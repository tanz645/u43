<?php
/**
 * Segments View (Tags and Folders)
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
    <h1><?php esc_html_e('Segments', 'u43'); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#tags-tab" class="nav-tab nav-tab-active"><?php esc_html_e('Tags', 'u43'); ?></a>
        <a href="#folders-tab" class="nav-tab"><?php esc_html_e('Folders', 'u43'); ?></a>
    </div>
    
    <!-- Tags Tab -->
    <div id="tags-tab" class="tab-content">
        <h2><?php esc_html_e('Tags', 'u43'); ?></h2>
        <button type="button" class="button" id="add-tag-btn"><?php esc_html_e('Add Tag', 'u43'); ?></button>
        
        <!-- Add Tag Modal -->
        <div id="add-tag-modal" class="u43-modal" style="display:none;">
            <div class="u43-modal-content">
                <h2><?php esc_html_e('Add New Tag', 'u43'); ?></h2>
                <form id="add-tag-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="tag_name"><?php esc_html_e('Tag Name', 'u43'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="text" id="tag_name" name="name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="tag_color"><?php esc_html_e('Color', 'u43'); ?></label></th>
                            <td>
                                <input type="color" id="tag_color" name="color" value="#0073aa" class="small-text">
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Add Tag', 'u43'); ?></button>
                        <button type="button" class="button cancel-modal"><?php esc_html_e('Cancel', 'u43'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'u43'); ?></th>
                    <th><?php esc_html_e('Color', 'u43'); ?></th>
                    <th><?php esc_html_e('Created', 'u43'); ?></th>
                    <th><?php esc_html_e('Actions', 'u43'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tags)): ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No tags found.', 'u43'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><strong><?php echo esc_html($tag->name); ?></strong></td>
                            <td><span style="display: inline-block; width: 20px; height: 20px; background: <?php echo esc_attr($tag->color); ?>; border-radius: 3px;"></span></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($tag->created_at))); ?></td>
                            <td>
                                <a href="#" class="delete-tag" data-id="<?php echo esc_attr($tag->id); ?>"><?php esc_html_e('Delete', 'u43'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Folders Tab -->
    <div id="folders-tab" class="tab-content" style="display:none;">
        <h2><?php esc_html_e('Folders', 'u43'); ?></h2>
        <button type="button" class="button" id="add-folder-btn"><?php esc_html_e('Add Folder', 'u43'); ?></button>
        
        <!-- Add Folder Modal -->
        <div id="add-folder-modal" class="u43-modal" style="display:none;">
            <div class="u43-modal-content">
                <h2><?php esc_html_e('Add New Folder', 'u43'); ?></h2>
                <form id="add-folder-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="folder_name"><?php esc_html_e('Folder Name', 'u43'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="text" id="folder_name" name="name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="folder_description"><?php esc_html_e('Description', 'u43'); ?></label></th>
                            <td>
                                <textarea id="folder_description" name="description" class="large-text" rows="3"></textarea>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Add Folder', 'u43'); ?></button>
                        <button type="button" class="button cancel-modal"><?php esc_html_e('Cancel', 'u43'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'u43'); ?></th>
                    <th><?php esc_html_e('Description', 'u43'); ?></th>
                    <th><?php esc_html_e('Created', 'u43'); ?></th>
                    <th><?php esc_html_e('Actions', 'u43'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($folders)): ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No folders found.', 'u43'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($folders as $folder): ?>
                        <tr>
                            <td><strong><?php echo esc_html($folder->name); ?></strong></td>
                            <td><?php echo esc_html($folder->description ?: '-'); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($folder->created_at))); ?></td>
                            <td>
                                <a href="#" class="edit-folder" data-id="<?php echo esc_attr($folder->id); ?>"><?php esc_html_e('Edit', 'u43'); ?></a> |
                                <a href="#" class="delete-folder" data-id="<?php echo esc_attr($folder->id); ?>"><?php esc_html_e('Delete', 'u43'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).attr('href');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $(tab).show();
    });
    
    // Show Add Tag Modal
    $('#add-tag-btn').on('click', function(e) {
        e.preventDefault();
        $('#add-tag-modal').show();
        $('#tag_name').focus();
    });
    
    // Show Add Folder Modal
    $('#add-folder-btn').on('click', function(e) {
        e.preventDefault();
        $('#add-folder-modal').show();
        $('#folder_name').focus();
    });
    
    // Close modals
    $('.cancel-modal').on('click', function() {
        $('.u43-modal').hide();
        $('#add-tag-form')[0].reset();
        $('#add-folder-form')[0].reset();
    });
    
    // Click outside modal to close
    $('.u43-modal').on('click', function(e) {
        if ($(e.target).hasClass('u43-modal')) {
            $(this).hide();
            $('#add-tag-form')[0].reset();
            $('#add-folder-form')[0].reset();
        }
    });
    
    // Add Tag Form Submit
    $('#add-tag-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            name: $('#tag_name').val(),
            color: $('#tag_color').val() || '#0073aa'
        };
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/tags')); ?>',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                alert('<?php echo esc_js(__('Tag added successfully!', 'u43')); ?>');
                location.reload();
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.message || '<?php echo esc_js(__('Unknown error', 'u43')); ?>';
                alert('<?php echo esc_js(__('Error adding tag: ', 'u43')); ?>' + errorMsg);
            }
        });
    });
    
    // Add Folder Form Submit
    $('#add-folder-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            name: $('#folder_name').val(),
            description: $('#folder_description').val() || ''
        };
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/folders')); ?>',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                alert('<?php echo esc_js(__('Folder added successfully!', 'u43')); ?>');
                location.reload();
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.message || '<?php echo esc_js(__('Unknown error', 'u43')); ?>';
                alert('<?php echo esc_js(__('Error adding folder: ', 'u43')); ?>' + errorMsg);
            }
        });
    });
});
</script>

<style>
.u43-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.u43-modal-content {
    background: white;
    padding: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.u43-modal-content h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}
.required {
    color: #dc3232;
}
</style>


