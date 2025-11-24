<?php
/**
 * Tools Registry
 *
 * @package U43
 */

namespace U43\Registry;

use U43\Tools\Tool_Base;

class Tools_Registry extends Registry_Base {
    
    /**
     * Execute a tool
     *
     * @param string $tool_id Tool ID
     * @param array $inputs Input parameters
     * @return mixed
     * @throws \Exception
     */
    public function execute($tool_id, $inputs = []) {
        $tool = $this->get($tool_id);
        
        if (!$tool) {
            throw new \Exception("Tool '{$tool_id}' not found");
        }
        
        if (!($tool instanceof Tool_Base)) {
            throw new \Exception("Tool '{$tool_id}' is not a valid tool instance");
        }
        
        return $tool->execute($inputs);
    }
    
    /**
     * Register a tool
     *
     * @param string $id Tool ID
     * @param Tool_Base $tool Tool instance
     */
    public function register($id, $tool) {
        if (!($tool instanceof Tool_Base)) {
            throw new \InvalidArgumentException('Tool must be an instance of Tool_Base');
        }
        parent::register($id, $tool);
    }
}

