/**
 * Input Helpers
 * 
 * Utilities for working with input fields and validation
 */

/**
 * Check if an input value is empty based on its type
 * @param {*} value - The input value
 * @param {string} type - The input type (array, string, etc.)
 * @returns {boolean} True if empty
 */
export const isEmptyInput = (value, type) => {
  if (type === 'array') {
    return !Array.isArray(value) || value.length === 0;
  }
  
  if (typeof value === 'string') {
    return value.trim() === '';
  }
  
  return !value || value === null || value === undefined;
};

/**
 * Get default value for an input based on its configuration
 * @param {Object} inputConfig - Input configuration
 * @param {*} currentValue - Current value
 * @returns {*} Default value
 */
export const getInputDefaultValue = (inputConfig, currentValue) => {
  if (currentValue !== undefined && currentValue !== null) {
    return currentValue;
  }
  
  if (inputConfig?.default !== undefined) {
    return inputConfig.default;
  }
  
  // Type-based defaults
  switch (inputConfig?.type) {
    case 'array':
      return [];
    case 'object':
      return {};
    case 'string':
      return '';
    case 'number':
      return 0;
    case 'boolean':
      return false;
    default:
      return '';
  }
};

/**
 * Validate an input value against its configuration
 * @param {*} value - The input value
 * @param {Object} inputConfig - Input configuration
 * @returns {Object} Validation result { valid: boolean, error: string }
 */
export const validateInput = (value, inputConfig) => {
  // Check required
  if (inputConfig.required && isEmptyInput(value, inputConfig.type)) {
    return {
      valid: false,
      error: `${inputConfig.label || 'This field'} is required`
    };
  }
  
  // Type-specific validation
  if (inputConfig.type === 'array' && !Array.isArray(value)) {
    return {
      valid: false,
      error: `${inputConfig.label || 'This field'} must be an array`
    };
  }
  
  if (inputConfig.type === 'object' && typeof value !== 'object') {
    return {
      valid: false,
      error: `${inputConfig.label || 'This field'} must be an object`
    };
  }
  
  return { valid: true, error: null };
};

/**
 * Check if an input field should be hidden
 * @param {string} inputKey - The input key
 * @returns {boolean} True if should be hidden
 */
export const isHiddenInput = (inputKey) => {
  const hiddenFields = ['message_type', 'template_name', 'template_language'];
  return hiddenFields.includes(inputKey);
};

