<?php
/**
 * Workflow Builder View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$workflow_id = isset($_GET['workflow_id']) ? intval($_GET['workflow_id']) : 0;
$workflow = null;

if ($workflow_id) {
    $flow_manager = U43()->get_flow_manager();
    $workflow = $flow_manager->get_workflow($workflow_id);
}

// Prepare workflow data for JavaScript
$workflow_data = [
    'id' => $workflow ? $workflow->id : null,
    'title' => $workflow ? $workflow->title : 'New Workflow',
    'description' => $workflow ? $workflow->description : '',
    'status' => $workflow ? $workflow->status : 'draft',
    'created_at' => $workflow ? $workflow->created_at : null,
    'updated_at' => $workflow ? $workflow->updated_at : null,
    'workflow_data' => $workflow ? $workflow->workflow_data : [
        'nodes' => [],
        'edges' => [],
    ],
];
?>

<style>
/* Hide WordPress admin menu when in workflow builder */
body.workflows_page_u43-builder #adminmenuback,
body.workflows_page_u43-builder #adminmenuwrap,
body.workflows_page_u43-builder #adminmenu {
    display: none !important;
}

body.workflows_page_u43-builder #wpcontent {
    margin-left: 0 !important;
}

#u43-workflow-builder {
    position: fixed;
    top: 32px; /* WordPress admin bar height */
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    background: #f0f0f1;
}

/* Ensure full width */
body.workflows_page_u43-builder #wpbody-content {
    padding: 0;
    margin: 0;
}

body.workflows_page_u43-builder .wrap {
    margin: 0;
    padding: 0;
}
</style>

<div id="u43-workflow-builder"></div>

<script>
window.u43WorkflowData = <?php echo json_encode($workflow_data); ?>;
window.u43AjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
window.u43AdminUrl = '<?php echo admin_url(); ?>';
window.u43RestUrl = '<?php echo rest_url('u43/v1/'); ?>';
window.u43Nonce = '<?php echo wp_create_nonce('u43_workflow_action'); ?>';
window.u43RestNonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
</script>

