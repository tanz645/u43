<?php
/**
 * Executions and Logs View
 *
 * @package U43
 */

if (!defined('ABSPATH')) {
    exit;
}

$flow_manager = U43()->get_flow_manager();

// Get workflow ID from query string
$workflow_id = isset($_GET['workflow_id']) ? intval($_GET['workflow_id']) : null;
$execution_id = isset($_GET['execution_id']) ? intval($_GET['execution_id']) : null;

// Get executions
$executions = $flow_manager->get_executions($workflow_id, ['limit' => 50]);

// Get execution details if viewing a specific execution
$execution = null;
$node_logs = [];
if ($execution_id) {
    $execution = $flow_manager->get_execution($execution_id);
    if ($execution) {
        $node_logs = $flow_manager->get_node_logs($execution_id);
    }
}

// Get workflows for filter
$workflows = $flow_manager->get_workflows(['limit' => 100]);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Workflow Executions & Logs', 'u43'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=u43')); ?>" class="page-title-action">
        <?php esc_html_e('Back to Workflows', 'u43'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php if ($execution_id && $execution): ?>
        <!-- Execution Detail View -->
        <div class="u43-execution-detail">
            <h2><?php echo esc_html(sprintf(__('Execution #%d', 'u43'), $execution_id)); ?></h2>
            
            <div class="u43-execution-info">
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Workflow', 'u43'); ?></th>
                        <td>
                            <strong><?php echo esc_html($execution->workflow_title); ?></strong>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=u43-executions&workflow_id=' . $execution->workflow_id)); ?>" class="button button-small">
                                <?php esc_html_e('View All Executions', 'u43'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Status', 'u43'); ?></th>
                        <td>
                            <span class="u43-status u43-status-<?php echo esc_attr($execution->status); ?>">
                                <?php echo esc_html(ucfirst($execution->status)); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Started At', 'u43'); ?></th>
                        <td><?php echo esc_html($execution->started_at ? date_i18n('Y-m-d H:i:s', strtotime($execution->started_at)) : '-'); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Completed At', 'u43'); ?></th>
                        <td><?php echo esc_html($execution->completed_at ? date_i18n('Y-m-d H:i:s', strtotime($execution->completed_at)) : '-'); ?></td>
                    </tr>
                    <?php if ($execution->duration_ms): ?>
                    <tr>
                        <th><?php esc_html_e('Duration', 'u43'); ?></th>
                        <td><?php echo esc_html(number_format($execution->duration_ms / 1000, 2)); ?>s</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($execution->error_message): ?>
                    <tr>
                        <th><?php esc_html_e('Error', 'u43'); ?></th>
                        <td>
                            <div class="u43-error-message">
                                <strong><?php echo esc_html($execution->error_message); ?></strong>
                                <?php if ($execution->error_stack): ?>
                                    <details style="margin-top: 10px;">
                                        <summary><?php esc_html_e('Stack Trace', 'u43'); ?></summary>
                                        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;"><?php echo esc_html($execution->error_stack); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <?php if (!empty($execution->trigger_data)): ?>
            <div class="u43-trigger-data" style="margin-top: 20px;">
                <h3><?php esc_html_e('Trigger Data', 'u43'); ?></h3>
                
                <?php 
                $trigger_data = $execution->trigger_data;
                $webhook_body = $trigger_data['_webhook_body'] ?? null;
                // Remove webhook body from trigger data display (show separately)
                $display_data = $trigger_data;
                unset($display_data['_webhook_body']);
                ?>
                
                <?php if (!empty($webhook_body)): ?>
                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px; color: #0073aa;"><?php esc_html_e('Webhook Request Body', 'u43'); ?></h4>
                    <pre style="background: #e8f4f8; padding: 15px; overflow-x: auto; max-height: 400px; border-left: 4px solid #0073aa;"><?php echo esc_html(json_encode($webhook_body, JSON_PRETTY_PRINT)); ?></pre>
                    <p style="margin-top: 5px; font-size: 12px; color: #666;"><?php esc_html_e('Full webhook payload received from WhatsApp', 'u43'); ?></p>
                </div>
                <?php endif; ?>
                
                <h4 style="margin-bottom: 10px;"><?php esc_html_e('Processed Trigger Data', 'u43'); ?></h4>
                <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; max-height: 300px;"><?php echo esc_html(json_encode($display_data, JSON_PRETTY_PRINT)); ?></pre>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($node_logs)): ?>
            <div class="u43-node-logs" style="margin-top: 20px;">
                <h3><?php esc_html_e('Node Execution Logs', 'u43'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Node ID', 'u43'); ?></th>
                            <th><?php esc_html_e('Type', 'u43'); ?></th>
                            <th><?php esc_html_e('Status', 'u43'); ?></th>
                            <th><?php esc_html_e('Started', 'u43'); ?></th>
                            <th><?php esc_html_e('Completed', 'u43'); ?></th>
                            <th><?php esc_html_e('Duration', 'u43'); ?></th>
                            <th><?php esc_html_e('Actions', 'u43'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($node_logs as $log): ?>
                        <tr>
                            <td><code><?php echo esc_html($log->node_id); ?></code></td>
                            <td><?php echo esc_html(ucfirst($log->node_type)); ?></td>
                            <td>
                                <span class="u43-status u43-status-<?php echo esc_attr($log->status); ?>">
                                    <?php echo esc_html(ucfirst($log->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->started_at ? date_i18n('H:i:s', strtotime($log->started_at)) : '-'); ?></td>
                            <td><?php echo esc_html($log->completed_at ? date_i18n('H:i:s', strtotime($log->completed_at)) : '-'); ?></td>
                            <td><?php echo esc_html($log->duration_ms ? number_format($log->duration_ms / 1000, 2) . 's' : '-'); ?></td>
                            <td>
                                <button type="button" class="button button-small u43-view-node-log" data-log-id="<?php echo esc_attr($log->id); ?>">
                                    <?php esc_html_e('View Details', 'u43'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Node Log Details Modal -->
                <?php foreach ($node_logs as $log): ?>
                <div id="u43-node-log-<?php echo esc_attr($log->id); ?>" class="u43-node-log-details" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                    <h4><?php echo esc_html(sprintf(__('Node: %s (%s)', 'u43'), $log->node_id, $log->node_type)); ?></h4>
                    
                    <?php if ($log->error_message): ?>
                    <div style="margin: 10px 0; padding: 10px; background: #ffe6e6; border-left: 4px solid #dc3232;">
                        <strong><?php esc_html_e('Error:', 'u43'); ?></strong> <?php echo esc_html($log->error_message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($log->input_data): ?>
                    <div style="margin: 10px 0;">
                        <strong><?php esc_html_e('Input Data:', 'u43'); ?></strong>
                        <pre style="background: white; padding: 10px; overflow-x: auto; max-height: 200px;"><?php echo esc_html(json_encode($log->input_data, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($log->output_data): ?>
                    <div style="margin: 10px 0;">
                        <?php 
                        // Handle both string (JSON) and array formats
                        $output_data = $log->output_data;
                        if (is_string($output_data)) {
                            $output_data = json_decode($output_data, true);
                        }
                        
                        // Ensure output_data is an array
                        if (!is_array($output_data)) {
                            $output_data = [];
                        }
                        
                        // Show HTTP request details prominently for HTTP action nodes
                        if ($log->node_type === 'action' && !empty($output_data['request'])): 
                            $request = $output_data['request'];
                            if (!is_array($request)) {
                                $request = [];
                            }
                        ?>
                            <div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                                <strong style="font-size: 14px;"><?php esc_html_e('HTTP Request:', 'u43'); ?></strong>
                                <div style="margin-top: 10px;">
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php esc_html_e('Method:', 'u43'); ?></strong> 
                                        <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($request['method'] ?? 'GET'); ?></code>
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php esc_html_e('URL:', 'u43'); ?></strong>
                                        <div style="margin-top: 4px; padding: 8px; background: white; border: 1px solid #ddd; font-family: monospace; font-size: 12px; word-break: break-all;">
                                            <?php echo esc_html($request['url'] ?? ''); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($request['headers'])): ?>
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php esc_html_e('Headers:', 'u43'); ?></strong>
                                        <pre style="margin-top: 4px; padding: 8px; background: white; border: 1px solid #ddd; font-size: 11px; max-height: 200px; overflow-y: auto;"><?php echo esc_html(json_encode($request['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['query_params'])): ?>
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php esc_html_e('Query Parameters:', 'u43'); ?></strong>
                                        <pre style="margin-top: 4px; padding: 8px; background: white; border: 1px solid #ddd; font-size: 11px; max-height: 150px; overflow-y: auto;"><?php echo esc_html(json_encode($request['query_params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['url_params'])): ?>
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php esc_html_e('URL Parameters:', 'u43'); ?></strong>
                                        <pre style="margin-top: 4px; padding: 8px; background: white; border: 1px solid #ddd; font-size: 11px; max-height: 150px; overflow-y: auto;"><?php echo esc_html(json_encode($request['url_params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (isset($request['body']) && $request['body'] !== null && $request['body'] !== ''): ?>
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php esc_html_e('Request Body:', 'u43'); ?></strong>
                                        <pre style="margin-top: 4px; padding: 8px; background: white; border: 1px solid #ddd; font-size: 11px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;"><?php 
                                            $body = $request['body'];
                                            // Try to pretty-print JSON if it's JSON
                                            if (is_string($body)) {
                                                $decoded = json_decode($body, true);
                                                if (json_last_error() === JSON_ERROR_NONE) {
                                                    echo esc_html(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                                } else {
                                                    echo esc_html($body);
                                                }
                                            } else {
                                                echo esc_html(json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                            }
                                        ?></pre>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // Show inputs sent to agent nodes prominently
                        if ($log->node_type === 'agent' && !empty($output_data['_inputs_sent'])): 
                            $inputs_sent = $output_data['_inputs_sent'];
                            if (!is_array($inputs_sent)) {
                                $inputs_sent = [];
                            }
                        ?>
                            <div style="margin-bottom: 20px; padding: 15px; background: #e8f4f8; border-left: 4px solid #0073aa;">
                                <strong style="font-size: 14px;"><?php esc_html_e('Prompt Sent to AI Agent:', 'u43'); ?></strong>
                                <div style="margin-top: 10px; padding: 10px; background: white; border: 1px solid #ddd; white-space: pre-wrap; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                                    <?php 
                                    // Show the actual prompt
                                    $prompt_to_display = '';
                                    if (!empty($inputs_sent['prompt'])) {
                                        $prompt_to_display = $inputs_sent['prompt'];
                                    } elseif (!empty($inputs_sent['full_user_message'])) {
                                        // If prompt is empty but full_user_message exists, extract just the prompt part
                                        // (before the "\n\nContext:" part)
                                        $full_msg = $inputs_sent['full_user_message'];
                                        $context_pos = strpos($full_msg, "\n\nContext:");
                                        if ($context_pos !== false) {
                                            $prompt_to_display = substr($full_msg, 0, $context_pos);
                                        } else {
                                            $prompt_to_display = $full_msg;
                                        }
                                    }
                                    
                                    if (empty($prompt_to_display)) {
                                        $prompt_to_display = 'No prompt provided (prompt field was empty)';
                                    }
                                    echo esc_html($prompt_to_display); 
                                    ?>
                                </div>
                                
                                <?php if (!empty($inputs_sent['decision_options'])): ?>
                                <div style="margin-top: 15px;">
                                    <strong style="font-size: 14px;"><?php esc_html_e('Decision Options:', 'u43'); ?></strong>
                                    <div style="margin-top: 10px; padding: 10px; background: white; border: 1px solid #ddd; font-size: 12px;">
                                        <?php echo esc_html(json_encode($inputs_sent['decision_options'], JSON_PRETTY_PRINT)); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($inputs_sent['context']) && is_array($inputs_sent['context'])): ?>
                                <div style="margin-top: 15px;">
                                    <strong style="font-size: 14px;"><?php esc_html_e('Context Data:', 'u43'); ?></strong>
                                    <pre style="margin-top: 10px; padding: 10px; background: white; border: 1px solid #ddd; overflow-x: auto; max-height: 200px; font-size: 12px;"><?php echo esc_html(json_encode($inputs_sent['context'], JSON_PRETTY_PRINT)); ?></pre>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($inputs_sent['full_user_message'])): ?>
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                    <strong style="font-size: 12px; color: #666;"><?php esc_html_e('Complete Message Sent to LLM:', 'u43'); ?></strong>
                                    <pre style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; overflow-x: auto; max-height: 200px; font-size: 11px; white-space: pre-wrap;"><?php echo esc_html($inputs_sent['full_user_message']); ?></pre>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <strong><?php esc_html_e('Agent Response:', 'u43'); ?></strong>
                                <?php 
                                // Remove inputs from display (already shown above)
                                $response_data = $output_data;
                                unset($response_data['_inputs_sent']);
                                ?>
                                <pre style="background: white; padding: 10px; overflow-x: auto; max-height: 200px; margin-top: 10px; font-size: 12px;"><?php echo esc_html(json_encode($response_data, JSON_PRETTY_PRINT)); ?></pre>
                            </div>
                        <?php elseif ($log->node_type === 'agent'): ?>
                            <!-- Agent node but no _inputs_sent found - show full output for debugging -->
                            <div style="margin-bottom: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                                <strong style="font-size: 12px;"><?php esc_html_e('Note: Input prompt data not found in output. Showing full output:', 'u43'); ?></strong>
                            </div>
                            <strong><?php esc_html_e('Output Data:', 'u43'); ?></strong>
                            <pre style="background: white; padding: 10px; overflow-x: auto; max-height: 200px; margin-top: 10px; font-size: 12px;"><?php echo esc_html(json_encode($output_data, JSON_PRETTY_PRINT)); ?></pre>
                        <?php else: ?>
                            <strong><?php esc_html_e('Output Data:', 'u43'); ?></strong>
                            <pre style="background: white; padding: 10px; overflow-x: auto; max-height: 200px; margin-top: 10px; font-size: 12px;"><?php echo esc_html(json_encode($output_data, JSON_PRETTY_PRINT)); ?></pre>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=u43-executions' . ($workflow_id ? '&workflow_id=' . $workflow_id : ''))); ?>" class="button">
                    <?php esc_html_e('← Back to Executions', 'u43'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>
        <!-- Executions List View -->
        <div class="u43-executions-list">
            <!-- Log Retention Settings -->
            <?php
            $retention_settings = \U43\Log_Cleanup::get_settings();
            $retention_enabled = $retention_settings['enabled'];
            $retention_duration = $retention_settings['duration'];
            $retention_unit = $retention_settings['unit'];
            ?>
            <div class="u43-log-retention-settings" style="margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top: 0;"><?php esc_html_e('Log Retention Settings', 'u43'); ?></h2>
                <p class="description"><?php esc_html_e('Automatically remove old execution logs based on the duration you specify.', 'u43'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=u43-executions')); ?>">
                    <?php wp_nonce_field('u43_log_retention'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="log_retention_enabled"><?php esc_html_e('Enable Automatic Log Removal', 'u43'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="log_retention_enabled" id="log_retention_enabled" value="1" <?php checked($retention_enabled, true); ?>>
                                    <?php esc_html_e('Enable automatic removal of old execution logs', 'u43'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr id="retention-duration-row" style="<?php echo $retention_enabled ? '' : 'display: none;'; ?>">
                            <th scope="row">
                                <label for="log_retention_duration"><?php esc_html_e('Retention Duration', 'u43'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="log_retention_duration" id="log_retention_duration" value="<?php echo esc_attr($retention_duration); ?>" min="1" style="width: 80px; margin-right: 10px;">
                                <select name="log_retention_unit" id="log_retention_unit" style="margin-right: 10px;">
                                    <option value="minute" <?php selected($retention_unit, 'minute'); ?>><?php esc_html_e('Minute(s)', 'u43'); ?></option>
                                    <option value="hour" <?php selected($retention_unit, 'hour'); ?>><?php esc_html_e('Hour(s)', 'u43'); ?></option>
                                    <option value="day" <?php selected($retention_unit, 'day'); ?>><?php esc_html_e('Day(s)', 'u43'); ?></option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Logs older than this duration will be automatically removed. Cleanup runs at the same interval as the selected unit.', 'u43'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="u43_save_log_retention" class="button button-primary">
                            <?php esc_html_e('Save Settings', 'u43'); ?>
                        </button>
                    </p>
                </form>
                
                <?php if ($retention_enabled): ?>
                    <?php
                    $next_run = wp_next_scheduled('u43_cleanup_old_logs');
                    $logs_to_delete = \U43\Log_Cleanup::get_logs_to_delete_count();
                    $is_overdue = $next_run && $next_run < time();
                    $debug_info = \U43\Log_Cleanup::get_debug_info();
                    ?>
                    <div style="margin-top: 15px; padding: 10px; background: <?php echo $is_overdue ? '#fff3cd' : '#e8f4f8'; ?>; border-left: 4px solid <?php echo $is_overdue ? '#ffc107' : '#0073aa'; ?>;">
                        <strong><?php esc_html_e('Status:', 'u43'); ?></strong>
                        <?php if ($next_run): ?>
                            <?php
                            $next_run_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run);
                            if ($is_overdue): ?>
                                <p style="margin: 5px 0;">
                                    <strong style="color: #856404;"><?php esc_html_e('⚠ Cleanup is overdue!', 'u43'); ?></strong><br>
                                    <?php echo esc_html(sprintf(
                                        __('Next cleanup was scheduled for: %s', 'u43'),
                                        $next_run_formatted
                                    )); ?>
                                </p>
                            <?php else: ?>
                                <p style="margin: 5px 0;">
                                    <?php echo esc_html(sprintf(
                                        __('Next cleanup scheduled for: %s', 'u43'),
                                        $next_run_formatted
                                    )); ?>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="margin: 5px 0;">
                                <?php esc_html_e('Automatic cleanup is enabled but not scheduled. It will be scheduled shortly.', 'u43'); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($logs_to_delete > 0): ?>
                            <p style="margin: 5px 0;">
                                <strong><?php echo esc_html(sprintf(
                                    __('Logs ready for deletion: %d', 'u43'),
                                    $logs_to_delete
                                )); ?></strong>
                                <?php if ($logs_to_delete > 1000): ?>
                                    <br><small style="color: #856404;">
                                        <?php esc_html_e('Note: Large volumes are processed in batches of 1000 per scheduled run to prevent timeouts. Manual cleanup can process up to 5000 at once.', 'u43'); ?>
                                    </small>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        
                        <details style="margin-top: 10px;">
                            <summary style="cursor: pointer; font-weight: bold;"><?php esc_html_e('Debug Information', 'u43'); ?></summary>
                            <div style="margin-top: 10px; padding: 10px; background: #f9f9f9; font-family: monospace; font-size: 11px;">
                                <p><strong>Current Time:</strong> <?php echo esc_html($debug_info['current_time']); ?> (<?php echo esc_html($debug_info['current_timestamp']); ?>)</p>
                                <?php if (isset($debug_info['cutoff_date'])): ?>
                                    <p><strong>Cutoff Date:</strong> <?php echo esc_html($debug_info['cutoff_date']); ?> (<?php echo esc_html($debug_info['cutoff_timestamp']); ?>)</p>
                                    <p><strong>Retention:</strong> <?php echo esc_html($debug_info['duration'] . ' ' . $debug_info['unit']); ?></p>
                                    <p><strong>Total Executions:</strong> <?php echo esc_html($debug_info['total_executions']); ?></p>
                                    <p><strong>Logs to Delete:</strong> <?php echo esc_html($debug_info['logs_to_delete']); ?></p>
                                    <?php if (isset($debug_info['oldest_execution'])): ?>
                                        <p><strong>Oldest Execution:</strong> ID <?php echo esc_html($debug_info['oldest_execution']['id']); ?>, 
                                        Started: <?php echo esc_html($debug_info['oldest_execution']['started_at']); ?>, 
                                        Age: <?php echo esc_html(human_time_diff($debug_info['oldest_execution']['age_seconds'])); ?> ago</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </details>
                        
                        <p style="margin: 10px 0 0 0;">
                            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=u43-executions')); ?>" style="display: inline;">
                                <?php wp_nonce_field('u43_manual_cleanup'); ?>
                                <button type="submit" name="u43_manual_cleanup" value="1" class="button button-secondary">
                                    <?php esc_html_e('Run Cleanup Now', 'u43'); ?>
                                </button>
                            </form>
                            <span class="description" style="margin-left: 10px;">
                                <?php esc_html_e('Manually trigger cleanup to delete old logs immediately. Check debug.log for details.', 'u43'); ?>
                            </span>
                        </p>
                        
                        <?php
                        // Show last cleanup result if available
                        $last_cleanup = get_transient('u43_last_cleanup_result');
                        if ($last_cleanup):
                        ?>
                            <div style="margin-top: 10px; padding: 8px; background: <?php echo $last_cleanup['success'] ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $last_cleanup['success'] ? '#28a745' : '#dc3545'; ?>;">
                                <strong><?php echo $last_cleanup['success'] ? '✓' : '✗'; ?></strong>
                                <?php echo esc_html($last_cleanup['message']); ?>
                                <?php if (isset($last_cleanup['deleted']) && $last_cleanup['deleted'] > 0): ?>
                                    <br><small><?php echo sprintf(__('Deleted: %d log(s)', 'u43'), $last_cleanup['deleted']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Filters -->
            <div class="u43-filters" style="margin: 20px 0;">
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                    <input type="hidden" name="page" value="u43-executions">
                    <select name="workflow_id" style="margin-right: 10px;">
                        <option value=""><?php esc_html_e('All Workflows', 'u43'); ?></option>
                        <?php foreach ($workflows as $wf): ?>
                            <option value="<?php echo esc_attr($wf->id); ?>" <?php selected($workflow_id, $wf->id); ?>>
                                <?php echo esc_html($wf->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e('Filter', 'u43'); ?></button>
                    <?php if ($workflow_id): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=u43-executions')); ?>" class="button">
                            <?php esc_html_e('Clear Filter', 'u43'); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Executions Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('ID', 'u43'); ?></th>
                        <th scope="col"><?php esc_html_e('Workflow', 'u43'); ?></th>
                        <th scope="col"><?php esc_html_e('Status', 'u43'); ?></th>
                        <th scope="col"><?php esc_html_e('Started', 'u43'); ?></th>
                        <th scope="col"><?php esc_html_e('Completed', 'u43'); ?></th>
                        <th scope="col"><?php esc_html_e('Duration', 'u43'); ?></th>
                        <th scope="col"><?php esc_html_e('Actions', 'u43'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($executions)): ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('No executions found.', 'u43'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($executions as $exec): ?>
                            <tr>
                                <td><?php echo esc_html($exec->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($exec->workflow_title); ?></strong>
                                </td>
                                <td>
                                    <span class="u43-status u43-status-<?php echo esc_attr($exec->status); ?>">
                                        <?php echo esc_html(ucfirst($exec->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($exec->started_at ? date_i18n('Y-m-d H:i:s', strtotime($exec->started_at)) : '-'); ?></td>
                                <td><?php echo esc_html($exec->completed_at ? date_i18n('Y-m-d H:i:s', strtotime($exec->completed_at)) : '-'); ?></td>
                                <td><?php echo esc_html($exec->duration_ms ? number_format($exec->duration_ms / 1000, 2) . 's' : '-'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=u43-executions&execution_id=' . $exec->id)); ?>" class="button button-small">
                                        <?php esc_html_e('View Details', 'u43'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.u43-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.u43-status-success {
    background: #d4edda;
    color: #155724;
}
.u43-status-failed {
    background: #f8d7da;
    color: #721c24;
}
.u43-status-running {
    background: #d1ecf1;
    color: #0c5460;
}
.u43-status-cancelled {
    background: #e2e3e5;
    color: #383d41;
}
.u43-error-message {
    color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.u43-view-node-log').on('click', function() {
        var logId = $(this).data('log-id');
        $('#u43-node-log-' + logId).slideToggle();
    });
    
    // Toggle retention duration row based on enabled checkbox
    $('#log_retention_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#retention-duration-row').show();
        } else {
            $('#retention-duration-row').hide();
        }
    });
});
</script>

