import React from 'react';
import InputFieldRenderer from '../common/InputFieldRenderer';

/**
 * Action Node Configuration Component
 * 
 * Handles configuration for action nodes including:
 * - Action type selection/display
 * - Dynamic tool input rendering
 */
export default function ActionNodeConfig({
  config,
  setConfig,
  toolConfig,
  loadingToolConfig,
  variableProps,
  lastFocusedTextareaRef,
  lastFocusedTextareaInputKeyRef,
  lastFocusedInputRef,
  lastFocusedInputInfoRef,
  connectedSourceNodes
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">
        Action Type
      </label>
      {toolConfig ? (
        <div className="px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
          <p className="text-sm text-gray-900 font-medium">
            {toolConfig.name || config.tool_id || 'Unknown Action'}
          </p>
          {toolConfig.description && (
            <p className="text-xs text-gray-500 mt-1">
              {toolConfig.description}
            </p>
          )}
          {config.tool_id && (
            <p className="text-xs text-gray-400 mt-1">
              ID: {config.tool_id}
            </p>
          )}
        </div>
      ) : config.tool_id ? (
        <div className="px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
          <p className="text-sm text-gray-900 font-medium">
            {config.tool_id.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
          </p>
          <p className="text-xs text-gray-400 mt-1">
            ID: {config.tool_id}
          </p>
        </div>
      ) : (
        <div className="px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
          <p className="text-sm text-gray-500 italic">
            No action selected
          </p>
        </div>
      )}
      
      {/* Render tool inputs dynamically */}
      {loadingToolConfig && (
        <p className="text-xs text-gray-500 mt-2">Loading tool configuration...</p>
      )}
      
      {toolConfig && toolConfig.inputs && (
        <div className="mt-4 space-y-4 pt-4 border-t border-gray-200">
          <h4 className="text-sm font-semibold text-gray-700">Tool Inputs</h4>
          {Object.entries(toolConfig.inputs).map(([inputKey, inputConfig]) => {
            const inputValue = config.inputs?.[inputKey] ?? inputConfig.default ?? '';
            
            return (
              <InputFieldRenderer
                key={inputKey}
                inputKey={inputKey}
                inputConfig={inputConfig}
                inputValue={inputValue}
                config={config}
                setConfig={setConfig}
                toolConfig={toolConfig}
                variableProps={variableProps}
                lastFocusedTextareaRef={lastFocusedTextareaRef}
                lastFocusedTextareaInputKeyRef={lastFocusedTextareaInputKeyRef}
                lastFocusedInputRef={lastFocusedInputRef}
                lastFocusedInputInfoRef={lastFocusedInputInfoRef}
                connectedSourceNodes={connectedSourceNodes}
              />
            );
          })}
        </div>
      )}
    </div>
  );
}

