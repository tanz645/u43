import React from 'react';
import { insertVariableAtCursor } from '../utils/variableHelpers';

/**
 * VariableSuggestions Component
 * 
 * Reusable component for displaying and inserting variable suggestions
 * 
 * @param {Object} props - Component props
 * @param {Array} groupedSuggestions - Grouped variable suggestions
 * @param {Array} individualSuggestions - Individual node suggestions
 * @param {Array} triggerSuggestions - Trigger node suggestions (with triggerConfigs)
 * @param {Function} onInsertVariable - Callback when variable is inserted
 * @param {Object} insertionConfig - Configuration for variable insertion
 * @param {string} insertionConfig.mode - 'textarea', 'input', 'field', 'config'
 * @param {React.Ref} insertionConfig.targetRef - Ref to target element
 * @param {Function} insertionConfig.onChange - Callback for value change
 * @param {string} insertionConfig.fieldKey - Field key for config updates
 * @param {string} insertionConfig.inputKey - Input key for nested config updates
 * @param {boolean} showTriggerNodes - Whether to show trigger nodes
 */
export default function VariableSuggestions({
  groupedSuggestions = [],
  individualSuggestions = [],
  triggerSuggestions = [],
  onInsertVariable,
  insertionConfig = {},
  showTriggerNodes = true,
  title = "Available Variables - Click to insert:",
  emptyMessage = null
}) {
  const {
    mode = 'textarea', // 'textarea', 'input', 'field', 'config'
    targetRef = null,
    onChange = null,
    fieldKey = null,
    inputKey = null,
    setConfig = null,
    config = null
  } = insertionConfig;

  // Handle variable insertion based on mode
  const handleVariableClick = (variable, suggestion = null) => {
    if (onInsertVariable) {
      // Custom handler provided
      onInsertVariable(variable, suggestion);
      return;
    }

    switch (mode) {
      case 'textarea':
        if (targetRef?.current) {
          const newValue = insertVariableAtCursor(targetRef.current, variable);
          if (onChange) {
            onChange(newValue);
          }
        }
        break;

      case 'input':
        if (targetRef?.current) {
          const newValue = insertVariableAtCursor(targetRef.current, variable);
          if (onChange) {
            onChange(newValue);
          }
        } else {
          // Fallback: try to find input by data attribute
          const inputField = document.querySelector(`input[data-input-key="${inputKey}"]`);
          if (inputField) {
            const newValue = insertVariableAtCursor(inputField, variable);
            if (setConfig && inputKey) {
              setConfig({
                ...config,
                inputs: {
                  ...(config.inputs || {}),
                  [inputKey]: newValue,
                },
              });
            }
          }
        }
        break;

      case 'field':
        // Direct field update (e.g., condition field)
        if (setConfig && fieldKey) {
          setConfig({
            ...config,
            [fieldKey]: variable,
          });
        }
        break;

      case 'config':
        // Update nested config (e.g., config.inputs[inputKey])
        if (setConfig && inputKey) {
          setConfig({
            ...config,
            inputs: {
              ...(config.inputs || {}),
              [inputKey]: variable,
            },
          });
        }
        break;

      default:
        console.warn('Unknown insertion mode:', mode);
    }
  };

  const hasAnySuggestions = groupedSuggestions.length > 0 || 
                           individualSuggestions.length > 0 || 
                           (showTriggerNodes && triggerSuggestions.length > 0);

  if (!hasAnySuggestions) {
    if (emptyMessage) {
      return (
        <div className="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-md">
          <p className="text-xs text-gray-600">{emptyMessage}</p>
        </div>
      );
    }
    return null;
  }

  return (
    <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
      <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
        <span>✓</span> {title}
      </p>
      <div className="space-y-2 max-h-64 overflow-y-auto overflow-x-hidden pr-1">
        {/* Grouped variable suggestions */}
        {groupedSuggestions.map((group) => (
          <div key={group.id} className="bg-white rounded p-2 border border-blue-200 max-w-full overflow-hidden">
            <div className="flex items-center gap-2 mb-1 min-w-0">
              <span className="text-xs font-semibold text-blue-900 truncate">
                {group.nodeTypeLabel} Nodes ({group.nodeCount})
              </span>
              <span className="text-xs text-gray-500 flex-shrink-0">Combined</span>
            </div>
            <div className="text-xs text-gray-600 mb-2 truncate" title={group.nodeLabels}>
              {group.nodeLabels}
            </div>
            <div className="space-y-1">
              {group.outputs.map((output) => (
                <button
                  key={output.key}
                  type="button"
                  onMouseDown={(e) => e.preventDefault()}
                  onClick={() => handleVariableClick(output.value, output)}
                  className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                  title={output.description}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                      {output.label}
                    </span>
                    <span className="text-xs text-gray-500 font-mono truncate" title={output.value}>
                      {output.value}
                    </span>
                  </div>
                  <div className="text-xs text-gray-500 mt-0.5 truncate">
                    {output.description}
                  </div>
                </button>
              ))}
            </div>
          </div>
        ))}

        {/* Individual node suggestions */}
        {individualSuggestions.map((item) => (
          <div key={item.id} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
            <div className="flex items-center gap-2 mb-1 min-w-0">
              <span className="text-xs font-semibold text-green-900 truncate">{item.nodeLabel}</span>
              <span className="text-xs text-gray-500 flex-shrink-0">({item.nodeType})</span>
            </div>
            <div className="space-y-1">
              {item.suggestions.map((suggestion, idx) => (
                <button
                  key={idx}
                  type="button"
                  onMouseDown={(e) => e.preventDefault()}
                  onClick={() => handleVariableClick(suggestion.value, suggestion)}
                  className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                  title={suggestion.description}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                      {suggestion.label}
                    </span>
                    <span className="text-xs text-gray-500 font-mono truncate" title={suggestion.value}>
                      {suggestion.displayValue || suggestion.value}
                    </span>
                  </div>
                  <div className="text-xs text-gray-500 mt-0.5 truncate">
                    {suggestion.description}
                  </div>
                </button>
              ))}
            </div>
          </div>
        ))}

        {/* Trigger node suggestions */}
        {showTriggerNodes && triggerSuggestions.map((trigger) => {
          if (!trigger.suggestions || trigger.suggestions.length === 0) return null;
          
          return (
            <div key={trigger.id} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
              <div className="flex items-center gap-2 mb-1 min-w-0">
                <span className="text-xs font-semibold text-green-900 truncate">{trigger.nodeLabel}</span>
                <span className="text-xs text-gray-500 flex-shrink-0">(trigger)</span>
              </div>
              <div className="space-y-1">
                {trigger.suggestions.map((suggestion, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onMouseDown={(e) => e.preventDefault()}
                    onClick={() => handleVariableClick(suggestion.value, suggestion)}
                    className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                    title={suggestion.description}
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                        {suggestion.label}
                      </span>
                      <span className="text-xs text-gray-500 font-mono truncate" title={suggestion.value}>
                        {suggestion.value}
                      </span>
                    </div>
                    <div className="text-xs text-gray-500 mt-0.5 truncate">
                      {suggestion.description}
                    </div>
                  </button>
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

