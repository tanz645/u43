/**
 * Key-Value Pair Helpers
 * 
 * Utilities for working with key-value pair inputs (used in HTTP tools)
 */

/**
 * Convert object to array of key-value pairs with stable IDs
 * @param {Object} objectValue - The object to convert
 * @returns {Array} Array of {id, key, value, originalKey} objects
 */
export const objectToKeyValuePairs = (objectValue) => {
  const obj = typeof objectValue === 'object' && objectValue !== null ? objectValue : {};
  const pairsMeta = obj.__pairs_meta__ || {};
  const keyValuePairs = [];
  
  Object.entries(obj).forEach(([key, value]) => {
    // Skip the meta object
    if (key === '__pairs_meta__') return;
    
    // Get or create stable ID for this pair
    let pairId = pairsMeta[key];
    if (!pairId) {
      // Generate a new stable ID for this key
      pairId = `pair_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    
    keyValuePairs.push({
      id: pairId,
      key: key.startsWith('__empty_') ? '' : key,
      value: typeof value === 'string' ? value : JSON.stringify(value),
      originalKey: key // Store original key to track it
    });
  });
  
  return keyValuePairs;
};

/**
 * Convert array of key-value pairs back to object with stable IDs
 * @param {Array} keyValuePairs - Array of {id, key, value} objects
 * @returns {Object} Object with __pairs_meta__ for stable IDs
 */
export const keyValuePairsToObject = (keyValuePairs) => {
  const newObject = {};
  const pairsMeta = {};
  
  keyValuePairs.forEach((p) => {
    if (p.key && p.key.trim() !== '') {
      // Real key-value pair - use key as the object key
      newObject[p.key] = p.value || '';
      // Store the stable ID mapping
      pairsMeta[p.key] = p.id;
    } else {
      // Empty pair - use temporary key but preserve ID
      const tempKey = `__empty_${p.id}`;
      newObject[tempKey] = p.value || '';
      pairsMeta[tempKey] = p.id;
    }
  });
  
  // Store the meta information
  newObject.__pairs_meta__ = pairsMeta;
  
  return newObject;
};

/**
 * Update a key-value pair at a specific index
 * @param {Array} keyValuePairs - Current pairs array
 * @param {number} index - Index to update
 * @param {Object} updates - Updates to apply {key?, value?}
 * @returns {Array} New pairs array
 */
export const updateKeyValuePair = (keyValuePairs, index, updates) => {
  const newPairs = [...keyValuePairs];
  const oldPair = newPairs[index];
  newPairs[index] = { ...oldPair, ...updates };
  return newPairs;
};

/**
 * Remove a key-value pair at a specific index
 * @param {Array} keyValuePairs - Current pairs array
 * @param {number} index - Index to remove
 * @returns {Array} New pairs array
 */
export const removeKeyValuePair = (keyValuePairs, index) => {
  return keyValuePairs.filter((_, i) => i !== index);
};

/**
 * Add a new empty key-value pair
 * @param {Array} keyValuePairs - Current pairs array
 * @returns {Array} New pairs array with added empty pair
 */
export const addKeyValuePair = (keyValuePairs) => {
  const newId = `pair_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  return [...keyValuePairs, { id: newId, key: '', value: '', originalKey: '' }];
};

/**
 * Get label for "Add" button based on input key
 * @param {string} inputKey - The input key
 * @returns {string} Label text
 */
export const getAddButtonLabel = (inputKey) => {
  const labels = {
    'url_params': 'URL Parameter',
    'query_params': 'Query Parameter',
    'headers': 'Header',
    'body': 'Field'
  };
  return labels[inputKey] || 'Field';
};

