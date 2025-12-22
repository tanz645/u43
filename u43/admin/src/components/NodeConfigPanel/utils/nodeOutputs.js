/**
 * Node Output Utilities
 * 
 * Functions for calculating and working with node outputs
 */

/**
 * Normalize an ID by trying both underscore and hyphen versions
 * @param {string} id - The ID to normalize
 * @returns {Object} Object with normalized versions
 */
export const normalizeId = (id) => {
  if (!id) return { original: id, withUnderscores: id, withHyphens: id };
  return {
    original: id,
    withUnderscores: id.replace(/-/g, '_'),
    withHyphens: id.replace(/_/g, '-')
  };
};

/**
 * Get outputs for a specific node based on its type and configuration
 * 
 * This function is designed to be extensible - it will automatically work for any node type
 * that defines outputs in their configuration files or in the node's data/config.
 * 
 * **For Dynamic Outputs:**
 * If a future node has dynamic outputs (outputs that change based on configuration),
 * simply set `node.data.config.outputs` when the config changes, and this function will
 * automatically pick them up. The edge labels will update automatically via `updateNode`.
 * 
 * Example for a future node with dynamic outputs:
 * ```javascript
 * // When node config changes, update outputs dynamically
 * updateNode(nodeId, {
 *   config: {
 *     ...existingConfig,
 *     outputs: computeDynamicOutputs(newConfig) // Your dynamic logic
 *   }
 * });
 * ```
 * 
 * @param {Object} node - The node object
 * @param {Object} configs - Object containing toolConfigs, triggerConfigs, agentConfigs, and potentially other config types
 * @returns {Object|null} Outputs object or null
 */
export const getNodeOutputs = (node, configs = {}) => {
  const { toolConfigs = {}, triggerConfigs = {}, agentConfigs = {} } = configs;
  const nodeType = node.data?.nodeType;
  
  // First, check if outputs are defined directly in the node's config/data
  // This allows nodes to define outputs dynamically without needing a config file
  // This is checked FIRST so dynamic outputs take precedence over static config files
  if (node.data?.config?.outputs && typeof node.data.config.outputs === 'object') {
    return node.data.config.outputs;
  }
  if (node.data?.outputs && typeof node.data.outputs === 'object') {
    return node.data.outputs;
  }
  
  // Handle known node types with their specific config systems
  if (nodeType === 'trigger') {
    const triggerId = node.data.config?.trigger_type || node.data.triggerType;
    if (triggerId && triggerConfigs[triggerId]?.outputs) {
      return triggerConfigs[triggerId].outputs;
    }
    return null;
  }
  
  if (nodeType === 'agent') {
    const agentId = node.data.config?.agent_id || node.data.agentId;
    if (agentId) {
      const { withUnderscores, withHyphens } = normalizeId(agentId);
      const agentConfig = agentConfigs[agentId] || agentConfigs[withUnderscores] || agentConfigs[withHyphens];
      if (agentConfig?.outputs) {
        // For unified LLM agent, filter outputs based on mode
        if (agentId === 'llm_agent' || withUnderscores === 'llm_agent' || withHyphens === 'llm-agent') {
          const mode = node.data.config?.settings?.mode || 'chat';
          const allOutputs = agentConfig.outputs;
          
          // Always exclude model_used and tokens_used
          const filteredOutputs = { ...allOutputs };
          delete filteredOutputs.model_used;
          delete filteredOutputs.tokens_used;
          
          // In chat mode, also exclude 'reason' output
          if (mode === 'chat') {
            delete filteredOutputs.reason;
          }
          
          return filteredOutputs;
        }
        
        return agentConfig.outputs;
      }
    }
    return null;
  }
  
  if (nodeType === 'action') {
    const toolId = node.data.config?.tool_id || node.data.toolId;
    if (toolId) {
      const { withUnderscores, withHyphens } = normalizeId(toolId);
      const toolConfig = toolConfigs[toolId] || toolConfigs[withUnderscores] || toolConfigs[withHyphens];
      
      // Special handling for button message nodes - generate outputs from buttons
      if (toolId === 'whatsapp_send_button_message' || 
          withUnderscores === 'whatsapp_send_button_message' || 
          withHyphens === 'whatsapp-send-button-message') {
        const buttons = node.data.config?.inputs?.buttons || [];
        // Only generate outputs if buttons exist and have valid IDs
        if (Array.isArray(buttons) && buttons.length > 0) {
          const buttonOutputs = {};
          buttons.forEach((button) => {
            // Only add output if button has an ID
            if (button && button.id) {
              const buttonId = button.id;
              const buttonTitle = button.title || buttonId;
              buttonOutputs[buttonId] = {
                type: 'string',
                label: buttonTitle
              };
            }
          });
          // Only return outputs if we have valid button outputs
          if (Object.keys(buttonOutputs).length > 0) {
            return buttonOutputs;
          }
        }
        // For button message nodes, return empty object if no buttons configured
        return {};
      }
      
      if (toolConfig?.outputs) {
        return toolConfig.outputs;
      }
    }
    return null;
  }
  
  // For future node types: check if there's a generic config system
  // This allows extensibility - if a new node type has a config with outputs, it will work
  // Example: if configs has a 'customNodeConfigs' or similar, we could check that here
  
  return null;
};

/**
 * Create output signature for comparing outputs
 * @param {Object} outputs - Outputs object
 * @returns {string|null} Signature string or null
 */
export const getOutputSignature = (outputs) => {
  if (!outputs) return null;
  return Object.keys(outputs).sort().join('|');
};

/**
 * Check if a node is a WhatsApp button message node
 * @param {Object} node - The node object
 * @returns {boolean} True if it's a button message node
 */
export const isButtonMessageNode = (node) => {
  const toolId = node.data?.config?.tool_id || node.data?.toolId;
  if (!toolId) return false;
  
  const { withUnderscores, withHyphens } = normalizeId(toolId);
  return toolId === 'whatsapp_send_button_message' || 
         withUnderscores === 'whatsapp_send_button_message' ||
         withHyphens === 'whatsapp-send-button-message';
};

