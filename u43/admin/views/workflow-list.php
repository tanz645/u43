<?php
/**
 * Workflow List View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$flow_manager = U43()->get_flow_manager();
$workflows = $flow_manager->get_workflows(['limit' => 100]);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Workflows', 'u43'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=u43-builder')); ?>" class="page-title-action">
        <?php esc_html_e('Workflow Builder', 'u43'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['message'])): ?>
        <?php
        $message = sanitize_text_field($_GET['message']);
        $message_class = 'notice-success';
        $message_text = '';
        
        switch ($message) {
            case 'created':
                $message_text = __('Workflow created successfully!', 'u43');
                break;
            case 'published':
                $message_text = __('Workflow published successfully!', 'u43');
                break;
            case 'unpublished':
                $message_text = __('Workflow unpublished successfully!', 'u43');
                break;
            case 'deleted':
                $message_text = __('Workflow deleted successfully!', 'u43');
                break;
            case 'duplicated':
                $message_text = __('Workflow duplicated successfully!', 'u43');
                break;
            case 'publish_failed':
            case 'unpublish_failed':
            case 'duplicate_failed':
                $message_class = 'notice-error';
                $message_text = __('Operation failed. Please try again.', 'u43');
                break;
        }
        ?>
        <?php if ($message_text): ?>
            <div class="notice <?php echo esc_attr($message_class); ?> is-dismissible">
                <p><?php echo esc_html($message_text); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Title', 'u43'); ?></th>
                <th scope="col"><?php esc_html_e('Status', 'u43'); ?></th>
                <th scope="col"><?php esc_html_e('Created', 'u43'); ?></th>
                <th scope="col"><?php esc_html_e('Actions', 'u43'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($workflows)): ?>
                <tr>
                    <td colspan="4"><?php esc_html_e('No workflows found.', 'u43'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($workflows as $workflow): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($workflow->title); ?></strong>
                            <?php if ($workflow->description): ?>
                                <br><span class="description"><?php echo esc_html($workflow->description); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($workflow->status); ?>">
                                <?php echo esc_html(ucfirst($workflow->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($workflow->created_at))); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=u43-builder&workflow_id=' . $workflow->id)); ?>" class="button button-small">
                                <?php esc_html_e('Edit', 'u43'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=u43-executions&workflow_id=' . $workflow->id)); ?>" class="button button-small">
                                <?php esc_html_e('Executions', 'u43'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=u43&u43_action=export_workflow&workflow_id=' . $workflow->id)); ?>" class="button button-small">
                                <?php esc_html_e('Export', 'u43'); ?>
                            </a>
                            <?php if ($workflow->status === 'published'): ?>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('u43_workflow_action'); ?>
                                    <input type="hidden" name="u43_action" value="unpublish_workflow">
                                    <input type="hidden" name="workflow_id" value="<?php echo esc_attr($workflow->id); ?>">
                                    <button type="submit" class="button button-small">
                                        <?php esc_html_e('Unpublish', 'u43'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('u43_workflow_action'); ?>
                                    <input type="hidden" name="u43_action" value="publish_workflow">
                                    <input type="hidden" name="workflow_id" value="<?php echo esc_attr($workflow->id); ?>">
                                    <button type="submit" class="button button-small button-primary">
                                        <?php esc_html_e('Publish', 'u43'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('u43_workflow_action'); ?>
                                <input type="hidden" name="u43_action" value="duplicate_workflow">
                                <input type="hidden" name="workflow_id" value="<?php echo esc_attr($workflow->id); ?>">
                                <button type="submit" class="button button-small">
                                    <?php esc_html_e('Duplicate', 'u43'); ?>
                                </button>
                            </form>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('u43_workflow_action'); ?>
                                <input type="hidden" name="u43_action" value="delete_workflow">
                                <input type="hidden" name="workflow_id" value="<?php echo esc_attr($workflow->id); ?>">
                                <button type="submit" class="button button-small delete" onclick="return confirm('Are you sure?');">
                                    <?php esc_html_e('Delete', 'u43'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

