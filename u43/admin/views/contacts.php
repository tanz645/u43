<?php
/**
 * Contacts View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$contact_manager = new \U43\Campaigns\Contact_Manager();
$folder_id = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : '';
$tag_id = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

$contacts_data = $contact_manager->get_contacts([
    'folder_id' => $folder_id,
    'tag_id' => $tag_id,
    'search' => $search,
    'page' => $page,
    'per_page' => 50
]);

$contacts = $contacts_data['items'];
$total_pages = $contacts_data['pages'];

$folders = $contact_manager->get_folders();
$tags = $contact_manager->get_tags();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Contacts', 'u43'); ?></h1>
    <a href="#" class="page-title-action" id="import-contacts-btn"><?php esc_html_e('Import Contacts', 'u43'); ?></a>
    <a href="#" class="page-title-action" id="add-contact-btn"><?php esc_html_e('Add Contact', 'u43'); ?></a>
    
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="u43-contacts">
                <select name="folder_id">
                    <option value=""><?php esc_html_e('All Folders', 'u43'); ?></option>
                    <?php foreach ($folders as $folder): ?>
                        <option value="<?php echo esc_attr($folder->id); ?>" <?php selected($folder_id, $folder->id); ?>>
                            <?php echo esc_html($folder->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="tag_id">
                    <option value=""><?php esc_html_e('All Tags', 'u43'); ?></option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?php echo esc_attr($tag->id); ?>" <?php selected($tag_id, $tag->id); ?>>
                            <?php echo esc_html($tag->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search contacts...', 'u43'); ?>">
                <button type="submit" class="button"><?php esc_html_e('Filter', 'u43'); ?></button>
            </form>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'u43'); ?></th>
                <th><?php esc_html_e('Phone', 'u43'); ?></th>
                <th><?php esc_html_e('Tags', 'u43'); ?></th>
                <th><?php esc_html_e('Folder', 'u43'); ?></th>
                <th><?php esc_html_e('Created', 'u43'); ?></th>
                <th><?php esc_html_e('Actions', 'u43'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No contacts found.', 'u43'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td><strong><?php echo esc_html($contact->name); ?></strong></td>
                        <td><?php echo esc_html($contact->phone); ?></td>
                        <td>
                            <?php if (!empty($contact->tags)): ?>
                                <?php foreach ($contact->tags as $tag): ?>
                                    <span class="tag" style="background: <?php echo esc_attr($tag->color); ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                                        <?php echo esc_html($tag->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="description"><?php esc_html_e('No tags', 'u43'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $folder_name = '';
                            foreach ($folders as $folder) {
                                if ($folder->id == $contact->folder_id) {
                                    $folder_name = $folder->name;
                                    break;
                                }
                            }
                            echo esc_html($folder_name ?: '-');
                            ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($contact->created_at))); ?></td>
                        <td>
                            <a href="#" class="edit-contact" data-id="<?php echo esc_attr($contact->id); ?>"><?php esc_html_e('Edit', 'u43'); ?></a> |
                            <a href="#" class="delete-contact" data-id="<?php echo esc_attr($contact->id); ?>"><?php esc_html_e('Delete', 'u43'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $page
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Contact Modal -->
<div id="add-contact-modal" class="u43-modal" style="display:none;">
    <div class="u43-modal-content">
        <h2><?php esc_html_e('Add New Contact', 'u43'); ?></h2>
        <form id="add-contact-form">
            <input type="hidden" id="contact_id" name="contact_id" value="">
            <table class="form-table">
                <tr>
                    <th><label for="contact_phone"><?php esc_html_e('Phone Number', 'u43'); ?> <span class="required">*</span></label></th>
                    <td>
                        <input type="text" id="contact_phone" name="phone" class="regular-text" required>
                        <p class="description"><?php esc_html_e('Phone number with country code (e.g., +1234567890)', 'u43'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact_name"><?php esc_html_e('Name', 'u43'); ?></label></th>
                    <td>
                        <input type="text" id="contact_name" name="name" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="contact_folder"><?php esc_html_e('Folder', 'u43'); ?></label></th>
                    <td>
                        <select id="contact_folder" name="folder_id" class="regular-text">
                            <option value=""><?php esc_html_e('-- Select Folder --', 'u43'); ?></option>
                            <?php foreach ($folders as $folder): ?>
                                <option value="<?php echo esc_attr($folder->id); ?>"><?php echo esc_html($folder->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e('Tags', 'u43'); ?></label></th>
                    <td id="contact_tags_container">
                        <?php foreach ($tags as $tag): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag->id); ?>" class="contact-tag-checkbox">
                                <span style="background: <?php echo esc_attr($tag->color); ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">
                                    <?php echo esc_html($tag->name); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($tags)): ?>
                            <p class="description"><?php esc_html_e('No tags available. Create tags in the Segments page.', 'u43'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact_notes"><?php esc_html_e('Notes', 'u43'); ?></label></th>
                    <td>
                        <textarea id="contact_notes" name="notes" class="large-text" rows="3"></textarea>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary" id="contact-submit-btn"><?php esc_html_e('Add Contact', 'u43'); ?></button>
                <button type="button" class="button cancel-modal"><?php esc_html_e('Cancel', 'u43'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Import Contacts Modal -->
<div id="import-contacts-modal" class="u43-modal" style="display:none;">
    <div class="u43-modal-content">
        <h2><?php esc_html_e('Import Contacts from CSV', 'u43'); ?></h2>
        <form id="import-contacts-form" enctype="multipart/form-data">
            <p>
                <label><?php esc_html_e('CSV File', 'u43'); ?></label><br>
                <input type="file" name="csv_file" accept=".csv" required>
            </p>
            <p>
                <label><input type="checkbox" name="skip_first_row" checked> <?php esc_html_e('Skip first row (headers)', 'u43'); ?></label>
            </p>
            <p>
                <label><?php esc_html_e('Name Column', 'u43'); ?></label>
                <input type="number" name="name_column" value="0" min="0" class="small-text">
            </p>
            <p>
                <label><?php esc_html_e('Phone Column', 'u43'); ?></label>
                <input type="number" name="phone_column" value="1" min="0" class="small-text">
            </p>
            <p>
                <label><?php esc_html_e('Tags Column', 'u43'); ?></label>
                <input type="number" name="tags_column" value="2" min="0" class="small-text">
            </p>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Import', 'u43'); ?></button>
                <button type="button" class="button cancel-modal"><?php esc_html_e('Cancel', 'u43'); ?></button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add Contact Modal
    $('#add-contact-btn').on('click', function(e) {
        e.preventDefault();
        resetContactForm();
        $('#add-contact-modal').show();
        $('#contact_phone').focus();
    });
    
    // Import Contacts Modal
    $('#import-contacts-btn').on('click', function(e) {
        e.preventDefault();
        $('#import-contacts-modal').show();
    });
    
    // Close modals
    $('.cancel-modal').on('click', function() {
        $('.u43-modal').hide();
        resetContactForm();
    });
    
    // Click outside modal to close
    $('.u43-modal').on('click', function(e) {
        if ($(e.target).hasClass('u43-modal')) {
            $(this).hide();
            resetContactForm();
        }
    });
    
    // Reset contact form
    function resetContactForm() {
        $('#add-contact-form')[0].reset();
        $('#contact_id').val('');
        $('.contact-tag-checkbox').prop('checked', false);
        $('#add-contact-modal h2').text('<?php echo esc_js(__('Add New Contact', 'u43')); ?>');
        $('#contact-submit-btn').text('<?php echo esc_js(__('Add Contact', 'u43')); ?>');
    }
    
    // Edit Contact
    $('.edit-contact').on('click', function(e) {
        e.preventDefault();
        var contactId = $(this).data('id');
        
        // Load contact data
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/contacts/')); ?>' + contactId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(contact) {
                // Populate form
                $('#contact_id').val(contact.id);
                $('#contact_phone').val(contact.phone);
                $('#contact_name').val(contact.name || '');
                $('#contact_folder').val(contact.folder_id || '');
                $('#contact_notes').val(contact.notes || '');
                
                // Clear all tag checkboxes
                $('.contact-tag-checkbox').prop('checked', false);
                
                // Check tags for this contact
                if (contact.tags && contact.tags.length > 0) {
                    contact.tags.forEach(function(tag) {
                        $('.contact-tag-checkbox[value="' + tag.id + '"]').prop('checked', true);
                    });
                }
                
                // Update modal title and button
                $('#add-contact-modal h2').text('<?php echo esc_js(__('Edit Contact', 'u43')); ?>');
                $('#contact-submit-btn').text('<?php echo esc_js(__('Update Contact', 'u43')); ?>');
                
                // Show modal
                $('#add-contact-modal').show();
                $('#contact_phone').focus();
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.message || '<?php echo esc_js(__('Unknown error', 'u43')); ?>';
                alert('<?php echo esc_js(__('Error loading contact: ', 'u43')); ?>' + errorMsg);
            }
        });
    });
    
    // Add/Edit Contact Form Submit
    $('#add-contact-form').on('submit', function(e) {
        e.preventDefault();
        
        var contactId = $('#contact_id').val();
        var formData = {
            phone: $('#contact_phone').val(),
            name: $('#contact_name').val() || '',
            folder_id: $('#contact_folder').val() || null,
            notes: $('#contact_notes').val() || ''
        };
        
        // Get selected tags
        var tags = [];
        $('input[name="tags[]"]:checked').each(function() {
            tags.push($(this).val());
        });
        if (tags.length > 0) {
            formData.tags = tags;
        }
        
        var url = '<?php echo esc_url(rest_url('u43/v1/contacts')); ?>';
        var method = 'POST';
        
        // If editing, use PUT method
        if (contactId) {
            url += '/' + contactId;
            method = 'PUT';
        }
        
        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                var message = contactId ? '<?php echo esc_js(__('Contact updated successfully!', 'u43')); ?>' : '<?php echo esc_js(__('Contact added successfully!', 'u43')); ?>';
                alert(message);
                location.reload();
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.message || '<?php echo esc_js(__('Unknown error', 'u43')); ?>';
                var action = contactId ? '<?php echo esc_js(__('updating', 'u43')); ?>' : '<?php echo esc_js(__('adding', 'u43')); ?>';
                alert('<?php echo esc_js(__('Error ', 'u43')); ?>' + action + ' contact: ' + errorMsg);
            }
        });
    });
    
    // Import Contacts Form Submit
    $('#import-contacts-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/contacts/import')); ?>',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                alert('Imported: ' + response.imported + ', Skipped: ' + response.skipped);
                location.reload();
            },
            error: function(xhr) {
                alert('Error importing contacts: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
    
    // Delete Contact
    $('.delete-contact').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this contact?', 'u43')); ?>')) {
            return;
        }
        
        var contactId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/contacts/')); ?>' + contactId,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                $row.fadeOut(300, function() {
                    $(this).remove();
                    // Reload if no contacts left
                    if ($('tbody tr').length === 0) {
                        location.reload();
                    }
                });
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.message || '<?php echo esc_js(__('Unknown error', 'u43')); ?>';
                alert('<?php echo esc_js(__('Error deleting contact: ', 'u43')); ?>' + errorMsg);
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


