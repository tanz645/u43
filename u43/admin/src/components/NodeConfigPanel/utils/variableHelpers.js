/**
 * Variable Helpers
 * 
 * Utilities for working with variables in node configurations
 */

/**
 * Insert a variable at the cursor position in a text input/textarea
 * @param {HTMLInputElement|HTMLTextAreaElement} element - The input element
 * @param {string} variable - The variable string to insert (e.g., "{{trigger_data.field}}")
 */
export const insertVariableAtCursor = (element, variable) => {
  if (!element) return;
  
  const start = element.selectionStart || 0;
  const end = element.selectionEnd || 0;
  const currentValue = element.value || '';
  const newValue = currentValue.slice(0, start) + variable + currentValue.slice(end);
  
  // Update the value
  element.value = newValue;
  
  // Set cursor position after inserted variable
  const newPos = start + variable.length;
  setTimeout(() => {
    element.focus();
    element.setSelectionRange(newPos, newPos);
  }, 0);
  
  return newValue;
};

/**
 * Generate variable suggestions for a node based on its type and outputs
 * @param {Object} node - The source node
 * @param {Object} outputs - The outputs from the node
 * @returns {Array} Array of suggestion objects
 */
export const generateVariableSuggestions = (node, outputs) => {
  if (!node || !outputs) return [];
  
  const nodeId = node.id;
  const nodeType = node.data?.nodeType;
  const suggestions = [];
  
  if (nodeType === 'trigger') {
    // Trigger nodes use trigger_data prefix
    Object.entries(outputs).forEach(([key, output]) => {
      suggestions.push({
        label: output.label || key,
        description: `Type: ${output.type || 'string'}`,
        value: `{{trigger_data.${key}}}`
      });
    });
  } else if (nodeType === 'agent' || nodeType === 'action') {
    // Agent and action nodes use node ID
    Object.entries(outputs).forEach(([key, output]) => {
      suggestions.push({
        label: output.label || key,
        description: `Type: ${output.type || 'string'}`,
        value: `{{${nodeId}.${key}}}`,
        displayValue: `{{node.${key}}}`
      });
    });
  } else {
    // Fallback for unknown node types
    suggestions.push({
      label: 'Output',
      description: 'The output from this node',
      value: `{{${nodeId}}}`,
      displayValue: `{{node}}`
    });
  }
  
  return suggestions;
};

/**
 * Generate combined variable for grouped nodes
 * @param {string} nodeType - The type of nodes in the group
 * @param {string} outputKey - The output key
 * @returns {string} Combined variable string
 */
export const generateCombinedVariable = (nodeType, outputKey) => {
  return `{{parents.${nodeType}.${outputKey}}}`;
};

/**
 * Parse a variable string to extract its components
 * @param {string} variable - Variable string (e.g., "{{trigger_data.field}}")
 * @returns {Object|null} Parsed components or null
 */
export const parseVariable = (variable) => {
  if (!variable || typeof variable !== 'string') return null;
  
  const match = variable.match(/\{\{([^}]+)\}\}/);
  if (!match) return null;
  
  const content = match[1];
  const parts = content.split('.');
  
  return {
    full: variable,
    content: content,
    parts: parts,
    root: parts[0],
    path: parts.slice(1).join('.')
  };
};

/**
 * Validate a variable string format
 * @param {string} variable - Variable string to validate
 * @returns {boolean} True if valid format
 */
export const isValidVariableFormat = (variable) => {
  if (!variable || typeof variable !== 'string') return false;
  return /^\{\{[^}]+\}\}$/.test(variable);
};

