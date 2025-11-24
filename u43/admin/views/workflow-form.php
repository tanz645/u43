<?php
/**
 * Workflow Form View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Create New Workflow', 'u43'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('u43_workflow_action'); ?>
        <input type="hidden" name="u43_action" value="create_workflow">
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="title"><?php esc_html_e('Title', 'u43'); ?></label>
                </th>
                <td>
                    <input type="text" name="title" id="title" class="regular-text" value="Comment Moderation" required>
                    <p class="description"><?php esc_html_e('Name for this workflow', 'u43'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="description"><?php esc_html_e('Description', 'u43'); ?></label>
                </th>
                <td>
                    <textarea name="description" id="description" class="large-text" rows="3">Automatically moderate comments using AI</textarea>
                    <p class="description"><?php esc_html_e('Optional description of what this workflow does', 'u43'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="status"><?php esc_html_e('Status', 'u43'); ?></label>
                </th>
                <td>
                    <select name="status" id="status">
                        <option value="draft"><?php esc_html_e('Draft', 'u43'); ?></option>
                        <option value="published"><?php esc_html_e('Published', 'u43'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Published workflows will execute automatically', 'u43'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="workflow-preview">
            <h2><?php esc_html_e('Workflow Preview', 'u43'); ?></h2>
            <p><?php esc_html_e('This workflow will:', 'u43'); ?></p>
            <ol>
                <li><?php esc_html_e('Trigger when a comment is posted', 'u43'); ?></li>
                <li><?php esc_html_e('Use AI to analyze the comment', 'u43'); ?></li>
                <li><?php esc_html_e('Automatically approve, spam, or delete based on AI decision', 'u43'); ?></li>
            </ol>
            <p class="description">
                <?php esc_html_e('Note: A visual workflow builder will be available in Phase 2. For now, this creates a default comment moderation workflow.', 'u43'); ?>
            </p>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Create Workflow', 'u43'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=u43')); ?>" class="button">
                <?php esc_html_e('Cancel', 'u43'); ?>
            </a>
        </p>
    </form>
</div>

