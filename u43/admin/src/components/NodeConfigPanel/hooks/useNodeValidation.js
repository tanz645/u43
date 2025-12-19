import { useCallback } from 'react';

/**
 * Hook to validate node configuration before saving
 * 
 * @param {Object} selectedNode - The currently selected node
 * @param {Object} config - Current node configuration
 * @param {Object} toolConfig - Tool configuration (for action nodes)
 * @returns {Function} validateNode function that returns { isValid: boolean, errors: string[] }
 */
export function useNodeValidation(selectedNode, config, toolConfig) {
  const validateNode = useCallback(() => {
    const errors = [];

    // Validate agent nodes require a prompt
    if (selectedNode?.data?.nodeType === 'agent') {
      const prompt = config.prompt || '';
      if (!prompt || prompt.trim() === '') {
        errors.push('Please enter a prompt for the AI agent. The node cannot be saved without a prompt.');
      }
    }
    
    // Validate action nodes have required fields
    if (selectedNode?.data?.nodeType === 'action' && toolConfig && toolConfig.inputs) {
      const missingRequiredFields = [];
      const inputs = config.inputs || {};
      
      Object.entries(toolConfig.inputs).forEach(([inputKey, inputConfig]) => {
        // Skip hidden fields (message_type, template fields)
        if (inputKey === 'message_type' || inputKey === 'template_name' || inputKey === 'template_language') {
          return;
        }
        
        if (inputConfig.required) {
          const inputValue = inputs[inputKey] ?? inputConfig.default ?? '';
          
          // Check if field is empty
          let isEmpty = false;
          
          if (inputConfig.type === 'array') {
            isEmpty = !Array.isArray(inputValue) || inputValue.length === 0;
          } else if (typeof inputValue === 'string') {
            isEmpty = inputValue.trim() === '';
          } else {
            isEmpty = !inputValue || inputValue === null || inputValue === undefined;
          }
          
          if (isEmpty) {
            missingRequiredFields.push(inputConfig.label || inputKey);
          }
        }
      });
      
      if (missingRequiredFields.length > 0) {
        errors.push(`Please fill in all required fields:\n\n${missingRequiredFields.join('\n')}\n\nThe node cannot be saved without these fields.`);
      }
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  }, [selectedNode, config, toolConfig]);

  return { validateNode };
}

