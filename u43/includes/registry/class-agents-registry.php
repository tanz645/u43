<?php
/**
 * Agents Registry
 *
 * @package U43
 */

namespace U43\Registry;

use U43\Agents\Agent_Base;

class Agents_Registry extends Registry_Base {
    
    /**
     * Execute an agent
     *
     * @param string $agent_id Agent ID
     * @param array $inputs Input parameters
     * @param array $node_config Optional node-specific config to merge with agent config
     * @return mixed
     * @throws \Exception
     */
    public function execute($agent_id, $inputs = [], $node_config = []) {
        $agent = $this->get($agent_id);
        
        if (!$agent) {
            throw new \Exception("Agent '{$agent_id}' not found");
        }
        
        if (!($agent instanceof Agent_Base)) {
            throw new \Exception("Agent '{$agent_id}' is not a valid agent instance");
        }
        
        // Merge node config with agent's base config if provided
        if (!empty($node_config)) {
            $agent->merge_config($node_config);
        }
        
        return $agent->execute($inputs);
    }
    
    /**
     * Register an agent
     *
     * @param string $id Agent ID
     * @param Agent_Base $agent Agent instance
     */
    public function register($id, $agent) {
        if (!($agent instanceof Agent_Base)) {
            throw new \InvalidArgumentException('Agent must be an instance of Agent_Base');
        }
        parent::register($id, $agent);
    }
}

