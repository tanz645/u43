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
 * @param {Object} node - The node object
 * @param {Object} configs - Object containing toolConfigs, triggerConfigs, agentConfigs
 * @returns {Object|null} Outputs object or null
 */
export const getNodeOutputs = (node, configs = {}) => {
  const { toolConfigs = {}, triggerConfigs = {}, agentConfigs = {} } = configs;
  const nodeType = node.data?.nodeType;
  
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

