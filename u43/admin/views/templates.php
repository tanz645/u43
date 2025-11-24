<?php
/**
 * Workflow Templates View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$flow_manager = U43()->get_flow_manager();

// Handle template import
if (isset($_POST['u43_import_template']) && check_admin_referer('u43_template_action')) {
    $template_data = null;
    
    // Check if importing from file
    if (!empty($_FILES['template_file']['tmp_name']) && file_exists($_FILES['template_file']['tmp_name'])) {
        $template_data = json_decode(file_get_contents($_FILES['template_file']['tmp_name']), true);
    }
    // Check if importing from hidden field (built-in template)
    elseif (!empty($_POST['template_data'])) {
        $template_data = json_decode(stripslashes($_POST['template_data']), true);
    }
    
    if ($template_data) {
        $workflow_id = $flow_manager->create_workflow([
            'title' => sanitize_text_field($template_data['name'] ?? 'Imported Workflow'),
            'description' => sanitize_textarea_field($template_data['description'] ?? ''),
            'status' => 'draft',
            'nodes' => $template_data['nodes'] ?? [],
            'edges' => $template_data['edges'] ?? [],
        ]);
        
        if ($workflow_id) {
            wp_redirect(admin_url('admin.php?page=u43-builder&workflow_id=' . $workflow_id . '&message=imported'));
            exit;
        }
    }
}

// Load built-in templates
$templates_dir = U43_PLUGIN_DIR . 'configs/workflow-templates/';
$templates = [];
if (is_dir($templates_dir)) {
    $template_files = glob($templates_dir . '*.json');
    foreach ($template_files as $file) {
        $template_data = json_decode(file_get_contents($file), true);
        if ($template_data) {
            $templates[] = $template_data;
        }
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Workflow Templates', 'u43'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=u43-builder')); ?>" class="page-title-action">
        <?php esc_html_e('Create New Workflow', 'u43'); ?>
    </a>
    <hr class="wp-header-end">
    
    <div class="u43-templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        <?php foreach ($templates as $template): ?>
            <div class="u43-template-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: white;">
                <h3><?php echo esc_html($template['name']); ?></h3>
                <p style="color: #666; margin: 10px 0;"><?php echo esc_html($template['description'] ?? ''); ?></p>
                <p style="font-size: 12px; color: #999;">Category: <?php echo esc_html($template['category'] ?? 'General'); ?></p>
                <form method="post" style="margin-top: 15px;">
                    <?php wp_nonce_field('u43_template_action'); ?>
                    <input type="hidden" name="u43_import_template" value="1">
                    <input type="hidden" name="template_data" value="<?php echo esc_attr(json_encode($template)); ?>">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Use Template', 'u43'); ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($templates)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                <p><?php esc_html_e('No templates available.', 'u43'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 40px; border-top: 1px solid #ddd; padding-top: 20px;">
        <h2><?php esc_html_e('Import Workflow from JSON', 'u43'); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('u43_template_action'); ?>
            <input type="hidden" name="u43_import_template" value="1">
            <p>
                <input type="file" name="template_file" accept=".json" required>
            </p>
            <p>
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Import Workflow', 'u43'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

