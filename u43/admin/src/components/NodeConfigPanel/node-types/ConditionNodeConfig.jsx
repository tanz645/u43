import React from 'react';
import VariableSuggestions from '../common/VariableSuggestions';

/**
 * Condition Node Configuration Component
 * 
 * Handles configuration for condition nodes including:
 * - Field to check (with variable support)
 * - Operator selection
 * - Compare value
 */
export default function ConditionNodeConfig({ 
  config, 
  setConfig,
  groupedSuggestions,
  individualSuggestions,
  triggerSuggestions,
  connectedSourceNodes
}) {
  return (
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Field to Check
        </label>
        
        {/* Show connected nodes */}
        <VariableSuggestions
          groupedSuggestions={groupedSuggestions}
          individualSuggestions={individualSuggestions}
          triggerSuggestions={triggerSuggestions}
          insertionConfig={{
            mode: 'field',
            fieldKey: 'field',
            setConfig,
            config
          }}
          title="Connected Nodes - Click to use:"
          showTriggerNodes={false}
        />
        
        <input
          type="text"
          value={config.field || ''}
          onChange={(e) => setConfig({ ...config, field: e.target.value })}
          placeholder="e.g., {{agent_node_id.response}} or {{trigger_data.comment_id}}"
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <div className="mt-2 p-3 bg-blue-50 rounded-md">
          <p className="text-xs font-semibold text-blue-900 mb-2">How to Reference Data:</p>
          <div className="text-xs text-blue-800 space-y-2">
            <div>
              <strong>From Agent Nodes:</strong>
              <ul className="ml-4 mt-1 space-y-0.5 list-disc">
                <li>Use {'{{'}node_name.output_field{'}}'} to access agent outputs (e.g., response, decision, reasoning)</li>
                <li>Available outputs depend on the agent type</li>
              </ul>
            </div>
            <div>
              <strong>From Trigger Nodes:</strong>
              <ul className="ml-4 mt-1 space-y-0.5 list-disc">
                <li>Use {'{{'}trigger_data.comment_id{'}}'} for comment ID</li>
                <li>Use {'{{'}trigger_data.author{'}}'} for author name</li>
                <li>Use {'{{'}trigger_data.content{'}}'} for comment content</li>
              </ul>
            </div>
          </div>
          {connectedSourceNodes.length === 0 && (
            <div className="mt-3 pt-2 border-t border-blue-200">
              <p className="text-xs text-blue-700">
                <strong>💡 Tip:</strong> Connect an agent or trigger node to this condition node to see clickable suggestions above!
              </p>
            </div>
          )}
        </div>
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Operator
        </label>
        <select
          value={config.operator || 'equals'}
          onChange={(e) => setConfig({ ...config, operator: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="equals">Equals (==)</option>
          <option value="not_equals">Not Equals (!=)</option>
          <option value="contains">Contains</option>
          <option value="greater_than">Greater Than (&gt;)</option>
          <option value="less_than">Less Than (&lt;)</option>
          <option value="exists">Exists</option>
          <option value="empty">Is Empty</option>
        </select>
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Compare Value
        </label>
        <input
          type="text"
          value={config.value || ''}
          onChange={(e) => setConfig({ ...config, value: e.target.value })}
          placeholder="Value to compare against"
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      
      <div className="p-3 bg-blue-50 rounded-md">
        <p className="text-xs text-blue-800">
          <strong>Note:</strong> Connect True branch to the right output handle, False branch to the bottom output handle (if available).
        </p>
      </div>
    </div>
  );
}

