import React from 'react';
import { 
  objectToKeyValuePairs, 
  keyValuePairsToObject, 
  updateKeyValuePair, 
  removeKeyValuePair, 
  addKeyValuePair,
  getAddButtonLabel 
} from '../../utils/keyValueHelpers';

/**
 * Key-Value Pairs Input Component (for HTTP tools)
 * 
 * Handles key-value pair inputs for HTTP tools (url_params, query_params, headers, body)
 * Supports variable insertion at cursor position
 */
export default function KeyValuePairsInput({ 
  inputKey, 
  inputConfig, 
  inputValue, 
  updateInputValue, 
  config, 
  setConfig, 
  variableProps, 
  lastFocusedInputRef, 
  lastFocusedInputInfoRef, 
  renderLabel, 
  renderDescription, 
  triggerNodes, 
  triggerConfigs, 
  connectedSourceNodes 
}) {
  const objectValue = typeof inputValue === 'object' && inputValue !== null ? inputValue : {};
  const keyValuePairs = objectToKeyValuePairs(objectValue);
  
  // Helper to update pairs and convert back to object
  const updatePairs = (newPairs) => {
    const newObject = keyValuePairsToObject(newPairs);
    updateInputValue(newObject);
  };
  
  // Handle variable insertion into key-value pair inputs
  const handleVariableInsert = (variable) => {
    const targetInput = lastFocusedInputRef?.current;
    const inputInfo = lastFocusedInputInfoRef?.current;
    
    if (targetInput && inputInfo?.index !== null && inputInfo?.inputKey === inputKey) {
      const start = targetInput.selectionStart || 0;
      const end = targetInput.selectionEnd || 0;
      const currentValue = targetInput.value || '';
      const newValue = currentValue.slice(0, start) + variable + currentValue.slice(end);
      
      const newPairs = updateKeyValuePair(keyValuePairs, inputInfo.index, {
        [inputInfo.isKey ? 'key' : 'value']: newValue
      });
      
      updatePairs(newPairs);
      
      setTimeout(() => {
        if (targetInput) {
          targetInput.focus();
          const newPos = start + variable.length;
          targetInput.setSelectionRange(newPos, newPos);
        }
      }, 0);
    }
  };
  
  // Get available trigger nodes for variable suggestions
  const allTriggerNodes = (triggerNodes || []).filter(node => 
    !(connectedSourceNodes || []).some(connected => connected.id === node.id)
  );
  
  // Check if we have any variable suggestions
  const hasVariableSuggestions = (variableProps?.hasSuggestions) || 
                                 (variableProps?.groupedSuggestions?.length > 0) ||
                                 (variableProps?.individualSuggestions?.length > 0) ||
                                 (allTriggerNodes.length > 0);
  
  return (
    <div>
      {renderLabel()}
      
      {/* Variable suggestions for key-value pairs */}
      {hasVariableSuggestions && (
        <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
          <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
            <span>✓</span> Available Variables - Click to insert at cursor:
          </p>
          <div className="space-y-2 max-h-32 overflow-y-auto overflow-x-hidden pr-1">
            {/* Grouped suggestions */}
            {variableProps?.groupedSuggestions?.map((group) => (
              <div key={group.id} className="bg-white rounded p-1.5 border border-blue-200 text-xs">
                <div className="font-semibold text-blue-900">{group.nodeTypeLabel} ({group.nodeCount})</div>
                <div className="text-gray-600 mt-0.5 space-y-0.5">
                  {group.outputs.map((output) => (
                    <button
                      key={output.key}
                      type="button"
                      onMouseDown={(e) => e.preventDefault()}
                      onClick={() => handleVariableInsert(output.value)}
                      className="block w-full text-left p-1 rounded hover:bg-blue-50 transition-colors"
                    >
                      <span className="font-mono text-blue-700">{output.value}</span>
                      <span className="text-gray-500 ml-1">{output.label}</span>
                    </button>
                  ))}
                </div>
              </div>
            ))}
            {/* Individual suggestions */}
            {variableProps?.individualSuggestions?.map((item) => (
              <div key={item.id} className="bg-white rounded p-1.5 border border-green-100 text-xs">
                <div className="font-semibold text-green-900">{item.nodeLabel}</div>
                <div className="text-gray-600 mt-0.5 space-y-0.5">
                  {item.suggestions.map((s, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onMouseDown={(e) => e.preventDefault()}
                      onClick={() => handleVariableInsert(s.value)}
                      className="block w-full text-left p-1 rounded hover:bg-blue-50 transition-colors"
                    >
                      <span className="font-mono text-blue-700">{s.value}</span>
                      <span className="text-gray-500 ml-1">{s.label}</span>
                    </button>
                  ))}
                </div>
              </div>
            ))}
            {/* Trigger node suggestions */}
            {allTriggerNodes.map((sourceNode) => {
              const triggerId = sourceNode.data?.config?.trigger_type || sourceNode.data?.triggerType;
              if (!triggerId || !triggerConfigs?.[triggerId]?.outputs) return null;
              
              const suggestions = Object.entries(triggerConfigs[triggerId].outputs).map(([key, output]) => ({
                label: output.label || key,
                value: `{{trigger_data.${key}}}`
              }));
              
              return (
                <div key={sourceNode.id} className="bg-white rounded p-1.5 border border-green-100 text-xs">
                  <div className="font-semibold text-green-900 truncate">
                    {sourceNode.data?.label || sourceNode.data?.config?.title || 'Untitled Node'}
                  </div>
                  <div className="text-gray-600 mt-0.5 space-y-0.5">
                    {suggestions.map((s, idx) => (
                      <button
                        key={idx}
                        type="button"
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() => handleVariableInsert(s.value)}
                        className="block w-full text-left p-1 rounded hover:bg-blue-50 transition-colors"
                      >
                        <span className="font-mono text-blue-700">{s.value}</span>
                        <span className="text-gray-500 ml-1">{s.label}</span>
                      </button>
                    ))}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}
      
      {/* Key-Value pairs list */}
      <div className="space-y-2 mb-2">
        {keyValuePairs.map((pair, index) => (
          <div key={pair.id || index} className="flex items-start gap-2 p-2 border border-gray-300 rounded-md bg-gray-50">
            <div className="flex-1 grid grid-cols-2 gap-2">
              <input
                type="text"
                data-input-key={`${inputKey}_key_${index}`}
                onFocus={(e) => {
                  if (lastFocusedInputRef) {
                    lastFocusedInputRef.current = e.target;
                  }
                  if (lastFocusedInputInfoRef) {
                    lastFocusedInputInfoRef.current = { index: index, isKey: true, inputKey: inputKey };
                  }
                }}
                value={pair.key || ''}
                onChange={(e) => {
                  const newPairs = updateKeyValuePair(keyValuePairs, index, { key: e.target.value });
                  updatePairs(newPairs);
                }}
                placeholder="Key"
                className="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
              <input
                type="text"
                data-input-key={`${inputKey}_value_${index}`}
                onFocus={(e) => {
                  if (lastFocusedInputRef) {
                    lastFocusedInputRef.current = e.target;
                  }
                  if (lastFocusedInputInfoRef) {
                    lastFocusedInputInfoRef.current = { index: index, isKey: false, inputKey: inputKey };
                  }
                }}
                value={pair.value || ''}
                onChange={(e) => {
                  const newPairs = updateKeyValuePair(keyValuePairs, index, { value: e.target.value });
                  updatePairs(newPairs);
                }}
                placeholder="Value (use {{variables}})"
                className="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
              />
            </div>
            <button
              type="button"
              onClick={() => {
                const newPairs = removeKeyValuePair(keyValuePairs, index);
                updatePairs(newPairs);
              }}
              className="text-red-500 hover:text-red-700 text-sm flex-shrink-0 mt-1"
              title="Remove item"
            >
              ✕
            </button>
          </div>
        ))}
      </div>
      
      {/* Add button */}
      <button
        type="button"
        onClick={() => {
          const newPairs = addKeyValuePair(keyValuePairs);
          updatePairs(newPairs);
        }}
        className="text-sm text-blue-600 hover:text-blue-800 mb-2 flex items-center gap-1"
      >
        <span>+</span> Add {getAddButtonLabel(inputKey)}
      </button>
      
      {renderDescription()}
      {inputKey === 'body' && (
        <p className="text-xs text-blue-600 mt-1">
          💡 Tip: Use variables in values like {'{{trigger_data.name}}'}
        </p>
      )}
    </div>
  );
}

