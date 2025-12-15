<?php
/**
 * Triggers Registry
 *
 * @package U43
 */

namespace U43\Registry;

class Triggers_Registry extends Registry_Base {
    
    /**
     * Trigger an event
     *
     * @param string $trigger_id Trigger ID
     * @param array $data Trigger data
     */
    public function trigger($trigger_id, $data = []) {
        // Get flow manager instance (lazy loading to avoid circular dependency)
        $flow_manager = U43()->get_flow_manager();
        
        // Get all published workflows that listen to this trigger (with filtering)
        $workflows = $flow_manager->get_workflows_by_trigger($trigger_id, $data);
        
        error_log("U43: Trigger '{$trigger_id}' fired. Found " . count($workflows) . " matching workflow(s).");
        
        if (empty($workflows)) {
            error_log("U43: No published workflows found for trigger '{$trigger_id}'. Make sure workflows are published and have the correct trigger type.");
        }
        
        foreach ($workflows as $workflow) {
            try {
                error_log("U43: Executing workflow ID {$workflow->id} ({$workflow->title})");
                $execution_id = $flow_manager->execute_workflow($workflow->id, $data);
                if ($execution_id) {
                    error_log("U43: Workflow execution started successfully. Execution ID: {$execution_id}");
                } else {
                    error_log("U43: Workflow execution failed to start for workflow ID {$workflow->id}");
                }
            } catch (\Exception $e) {
                error_log('U43: Workflow execution failed - ' . $e->getMessage());
                error_log('U43: Stack trace: ' . $e->getTraceAsString());
            }
        }
    }
}

