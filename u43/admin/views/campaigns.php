<?php
/**
 * Campaigns View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$campaign_manager = new \U43\Campaigns\Campaign_Manager();
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

$campaigns_data = $campaign_manager->get_campaigns([
    'status' => $status,
    'page' => $page,
    'per_page' => 20
]);

$campaigns = $campaigns_data['items'];
$total_pages = $campaigns_data['pages'];
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Campaigns', 'u43'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&action=create')); ?>" class="page-title-action"><?php esc_html_e('Create Campaign', 'u43'); ?></a>
    
    <hr class="wp-header-end">
    
    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns')); ?>" class="<?php echo $status === '' ? 'current' : ''; ?>"><?php esc_html_e('All', 'u43'); ?> <span class="count">(<?php echo esc_html($campaigns_data['total']); ?>)</span></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&status=draft')); ?>" class="<?php echo $status === 'draft' ? 'current' : ''; ?>"><?php esc_html_e('Draft', 'u43'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&status=running')); ?>" class="<?php echo $status === 'running' ? 'current' : ''; ?>"><?php esc_html_e('Running', 'u43'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&status=completed')); ?>" class="<?php echo $status === 'completed' ? 'current' : ''; ?>"><?php esc_html_e('Completed', 'u43'); ?></a></li>
    </ul>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'create'): ?>
        <?php include U43_PLUGIN_DIR . 'admin/views/campaign-create.php'; ?>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])): ?>
        <?php
        $campaign_id = intval($_GET['id']);
        $campaign = $campaign_manager->get_campaign($campaign_id);
        
        if (!$campaign) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Campaign not found.', 'u43') . '</p></div>';
        } else {
            // Decode JSON fields
            $target_value = !empty($campaign->target_value) ? json_decode($campaign->target_value, true) : [];
            $template_params = !empty($campaign->template_params) ? json_decode($campaign->template_params, true) : [];
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html($campaign->name); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns')); ?>" class="page-title-action"><?php esc_html_e('Back to Campaigns', 'u43'); ?></a>
            </h1>
            
            <div class="campaign-details" style="margin-top: 20px;">
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Name', 'u43'); ?></th>
                        <td><strong><?php echo esc_html($campaign->name); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Description', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->description ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Status', 'u43'); ?></th>
                        <td><span class="status-<?php echo esc_attr($campaign->status); ?>"><?php echo esc_html(ucfirst($campaign->status)); ?></span></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Message Type', 'u43'); ?></th>
                        <td><?php echo !empty($campaign->template_name) ? esc_html__('Template', 'u43') : esc_html__('Plain Text', 'u43'); ?></td>
                    </tr>
                    <?php if (!empty($campaign->template_name)): ?>
                        <tr>
                            <th><?php esc_html_e('Template Name', 'u43'); ?></th>
                            <td><?php echo esc_html($campaign->template_name); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th><?php esc_html_e('Message Text', 'u43'); ?></th>
                            <td><?php echo nl2br(esc_html($campaign->message_text)); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e('Target Segment', 'u43'); ?></th>
                        <td>
                            <?php
                            if ($campaign->target_type === 'all') {
                                echo esc_html__('All Contacts', 'u43');
                            } elseif ($campaign->target_type === 'folder') {
                                $contact_manager = new \U43\Campaigns\Contact_Manager();
                                $folders = $contact_manager->get_folders();
                                $folder_name = '';
                                foreach ($folders as $folder) {
                                    if ($folder->id == $target_value) {
                                        $folder_name = $folder->name;
                                        break;
                                    }
                                }
                                echo esc_html(sprintf(__('Folder: %s', 'u43'), $folder_name ?: '-'));
                            } elseif ($campaign->target_type === 'tags') {
                                $contact_manager = new \U43\Campaigns\Contact_Manager();
                                $tags = $contact_manager->get_tags();
                                $tag_names = [];
                                if (is_array($target_value)) {
                                    foreach ($tags as $tag) {
                                        if (in_array($tag->id, $target_value)) {
                                            $tag_names[] = $tag->name;
                                        }
                                    }
                                }
                                echo esc_html(sprintf(__('Tags: %s', 'u43'), !empty($tag_names) ? implode(', ', $tag_names) : '-'));
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Schedule', 'u43'); ?></th>
                        <td>
                            <?php
                            if ($campaign->schedule_type === 'immediate') {
                                echo esc_html__('Send Immediately', 'u43');
                            } else {
                                echo esc_html(sprintf(__('Scheduled for: %s', 'u43'), $campaign->scheduled_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($campaign->scheduled_at)) : '-'));
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Batch Size', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->batch_size); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Total Contacts', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->total_contacts); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Sent', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->sent_count); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Failed', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->failed_count); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Pending', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->total_contacts - $campaign->sent_count - $campaign->failed_count); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Created', 'u43'); ?></th>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($campaign->created_at))); ?></td>
                    </tr>
                    <?php if ($campaign->started_at): ?>
                        <tr>
                            <th><?php esc_html_e('Started', 'u43'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($campaign->started_at))); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($campaign->completed_at): ?>
                        <tr>
                            <th><?php esc_html_e('Completed', 'u43'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($campaign->completed_at))); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
                
                <p class="submit">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&action=logs&id=' . $campaign_id)); ?>" class="button"><?php esc_html_e('View Logs', 'u43'); ?></a>
                    <?php if ($campaign->status === 'draft'): ?>
                        <a href="#" class="button start-campaign" data-id="<?php echo esc_attr($campaign_id); ?>"><?php esc_html_e('Start Campaign', 'u43'); ?></a>
                    <?php elseif ($campaign->status === 'running'): ?>
                        <a href="#" class="button pause-campaign" data-id="<?php echo esc_attr($campaign_id); ?>"><?php esc_html_e('Pause Campaign', 'u43'); ?></a>
                    <?php elseif ($campaign->status === 'paused'): ?>
                        <a href="#" class="button resume-campaign" data-id="<?php echo esc_attr($campaign_id); ?>"><?php esc_html_e('Resume Campaign', 'u43'); ?></a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php } ?>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'logs' && isset($_GET['id'])): ?>
        <?php
        $campaign_id = intval($_GET['id']);
        $campaign = $campaign_manager->get_campaign($campaign_id);
        $log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        
        if (!$campaign) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Campaign not found.', 'u43') . '</p></div>';
        } else {
            $logs_data = $campaign_manager->get_campaign_delivery_logs($campaign_id, [
                'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
                'page' => $page,
                'per_page' => 50
            ]);
            $logs = $logs_data['items'];
            $total_pages = $logs_data['pages'];
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(sprintf(__('Campaign Logs: %s', 'u43'), $campaign->name)); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns')); ?>" class="page-title-action"><?php esc_html_e('Back to Campaigns', 'u43'); ?></a>
            </h1>
            
            <div class="campaign-summary" style="background: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
                <h3><?php esc_html_e('Campaign Summary', 'u43'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Status', 'u43'); ?></th>
                        <td><span class="status-<?php echo esc_attr($campaign->status); ?>"><?php echo esc_html(ucfirst($campaign->status)); ?></span></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Total Contacts', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->total_contacts); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Sent', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->sent_count); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Failed', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->failed_count); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Pending', 'u43'); ?></th>
                        <td><?php echo esc_html($campaign->total_contacts - $campaign->sent_count - $campaign->failed_count); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="u43-campaigns">
                        <input type="hidden" name="action" value="logs">
                        <input type="hidden" name="id" value="<?php echo esc_attr($campaign_id); ?>">
                        <select name="status">
                            <option value=""><?php esc_html_e('All Statuses', 'u43'); ?></option>
                            <option value="pending" <?php selected(isset($_GET['status']) && $_GET['status'] === 'pending'); ?>><?php esc_html_e('Pending', 'u43'); ?></option>
                            <option value="sent" <?php selected(isset($_GET['status']) && $_GET['status'] === 'sent'); ?>><?php esc_html_e('Sent', 'u43'); ?></option>
                            <option value="failed" <?php selected(isset($_GET['status']) && $_GET['status'] === 'failed'); ?>><?php esc_html_e('Failed', 'u43'); ?></option>
                            <option value="delivered" <?php selected(isset($_GET['status']) && $_GET['status'] === 'delivered'); ?>><?php esc_html_e('Delivered', 'u43'); ?></option>
                            <option value="read" <?php selected(isset($_GET['status']) && $_GET['status'] === 'read'); ?>><?php esc_html_e('Read', 'u43'); ?></option>
                        </select>
                        <button type="submit" class="button"><?php esc_html_e('Filter', 'u43'); ?></button>
                    </form>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Contact', 'u43'); ?></th>
                        <th><?php esc_html_e('Phone', 'u43'); ?></th>
                        <th><?php esc_html_e('Status', 'u43'); ?></th>
                        <th><?php esc_html_e('Sent At', 'u43'); ?></th>
                        <th><?php esc_html_e('Retry Count', 'u43'); ?></th>
                        <th><?php esc_html_e('Error Message', 'u43'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No logs found.', 'u43'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><strong><?php echo esc_html($log->contact_name ?: '-'); ?></strong></td>
                                <td><?php echo esc_html($log->contact_phone ?: '-'); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($log->status); ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo $log->sent_at ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->sent_at))) : '-'; ?></td>
                                <td><?php echo esc_html($log->retry_count); ?></td>
                                <td>
                                    <?php if ($log->error_message): ?>
                                        <span style="color: #dc3232;" title="<?php echo esc_attr($log->error_message); ?>">
                                            <?php echo esc_html(wp_trim_words($log->error_message, 10)); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
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
                        $base_url = add_query_arg(['page' => 'u43-campaigns', 'action' => 'logs', 'id' => $campaign_id], admin_url('admin.php'));
                        if (isset($_GET['status'])) {
                            $base_url = add_query_arg('status', $_GET['status'], $base_url);
                        }
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%', $base_url),
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
        <?php } ?>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'u43'); ?></th>
                    <th><?php esc_html_e('Status', 'u43'); ?></th>
                    <th><?php esc_html_e('Total Contacts', 'u43'); ?></th>
                    <th><?php esc_html_e('Sent', 'u43'); ?></th>
                    <th><?php esc_html_e('Failed', 'u43'); ?></th>
                    <th><?php esc_html_e('Created', 'u43'); ?></th>
                    <th><?php esc_html_e('Actions', 'u43'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No campaigns found.', 'u43'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td><strong><?php echo esc_html($campaign->name); ?></strong></td>
                            <td>
                                <span class="status-<?php echo esc_attr($campaign->status); ?>">
                                    <?php echo esc_html(ucfirst($campaign->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($campaign->total_contacts); ?></td>
                            <td><?php echo esc_html($campaign->sent_count); ?></td>
                            <td><?php echo esc_html($campaign->failed_count); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($campaign->created_at))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&action=view&id=' . $campaign->id)); ?>"><?php esc_html_e('View', 'u43'); ?></a> |
                                <?php if ($campaign->status === 'draft'): ?>
                                    <a href="#" class="start-campaign" data-id="<?php echo esc_attr($campaign->id); ?>"><?php esc_html_e('Start', 'u43'); ?></a> |
                                <?php elseif ($campaign->status === 'running'): ?>
                                    <a href="#" class="pause-campaign" data-id="<?php echo esc_attr($campaign->id); ?>"><?php esc_html_e('Pause', 'u43'); ?></a> |
                                <?php elseif ($campaign->status === 'paused'): ?>
                                    <a href="#" class="resume-campaign" data-id="<?php echo esc_attr($campaign->id); ?>"><?php esc_html_e('Resume', 'u43'); ?></a> |
                                <?php endif; ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=u43-campaigns&action=logs&id=' . $campaign->id)); ?>"><?php esc_html_e('Logs', 'u43'); ?></a>
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
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.start-campaign').on('click', function(e) {
        e.preventDefault();
        var campaignId = $(this).data('id');
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/campaigns/')); ?>' + campaignId + '/start',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                location.reload();
            }
        });
    });
    
    $('.pause-campaign, .resume-campaign').on('click', function(e) {
        e.preventDefault();
        var campaignId = $(this).data('id');
        var action = $(this).hasClass('pause-campaign') ? 'paused' : 'running';
        
        $.ajax({
            url: '<?php echo esc_url(rest_url('u43/v1/campaigns/')); ?>' + campaignId,
            method: 'PUT',
            data: JSON.stringify({ status: action }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                location.reload();
            }
        });
    });
});
</script>

<style>
.status-draft { color: #666; }
.status-running { color: #0073aa; font-weight: bold; }
.status-completed { color: #46b450; }
.status-paused { color: #dc3232; }
</style>

