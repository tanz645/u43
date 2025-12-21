import React, { useState, useRef } from 'react';
import VariableSuggestions from '../common/VariableSuggestions';

/**
 * Agent Node Configuration Component
 * 
 * Handles configuration for agent nodes including:
 * - AI Model selection
 * - Prompt configuration with variable support
 * - Custom decision options
 */
export default function AgentNodeConfig({ 
  config, 
  setConfig, 
  openaiModels, 
  loadingModels,
  groupedSuggestions,
  individualSuggestions,
  triggerSuggestions,
  selectedNode,
  updateNode
}) {
  const [showVariableInfo, setShowVariableInfo] = useState(false);
  const promptTextareaRef = useRef(null);
  
  // Get mode from config (default: 'chat')
  const mode = config.settings?.mode || 'chat';
  const isDecisionMode = mode === 'decision';
  
  // Toggle between chat and decision mode
  const handleModeToggle = (e) => {
    const newMode = e.target.checked ? 'decision' : 'chat';
    const newConfig = {
      ...config,
      settings: {
        ...config.settings,
        mode: newMode
      }
    };
    
    // Update local config state
    setConfig(newConfig);
    
    // Immediately update the node so outputs change in real-time
    if (selectedNode && selectedNode.id && updateNode) {
      // Ensure we preserve all existing config properties
      const currentConfig = selectedNode.data?.config || {};
      const mergedConfig = {
        ...currentConfig,
        ...newConfig,
        settings: {
          ...currentConfig.settings,
          ...newConfig.settings,
        }
      };
      updateNode(selectedNode.id, { config: mergedConfig });
    }
  };

  return (
    <div className="space-y-4">
      {/* Mode Toggle */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Agent Mode
        </label>
        <div className="flex items-center gap-3">
          <span className={`text-sm ${!isDecisionMode ? 'font-semibold text-blue-600' : 'text-gray-500'}`}>
            Chat Agent
          </span>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              checked={isDecisionMode}
              onChange={handleModeToggle}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
          <span className={`text-sm ${isDecisionMode ? 'font-semibold text-blue-600' : 'text-gray-500'}`}>
            Decision Agent
          </span>
        </div>
        <p className="text-xs text-gray-500 mt-1">
          {isDecisionMode 
            ? 'Decision mode analyzes information and makes decisions with reasoning.'
            : 'Chat mode provides conversational responses.'}
        </p>
      </div>
      
      {/* Model Selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          AI Model
        </label>
        {loadingModels ? (
          <div className="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
            <span className="text-sm text-gray-500">Loading models...</span>
          </div>
        ) : openaiModels.length > 0 ? (
          <select
            value={config.settings?.model || (openaiModels[0]?.id || 'gpt-5.2')}
            onChange={(e) => {
              setConfig({
                ...config,
                settings: {
                  ...config.settings,
                  model: e.target.value,
                  provider: config.settings?.provider || 'openai'
                }
              });
            }}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            {openaiModels.map((model) => (
              <option key={model.id} value={model.id}>
                {model.name} {model.description ? `- ${model.description}` : ''}
              </option>
            ))}
          </select>
        ) : (
          <div className="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
            <span className="text-sm text-gray-500">
              {config.settings?.model || 'gpt-5.2'} (API key not configured or GPT-5 models unavailable)
            </span>
          </div>
        )}
        {config.settings?.provider && (
          <p className="text-xs text-gray-500 mt-1">
            Provider: {config.settings.provider}
          </p>
        )}
      </div>
      
      <div>
        <div className="flex items-center gap-2 mb-1">
          <label className="block text-sm font-medium text-gray-700">
            Prompt <span className="text-red-500">*</span>
          </label>
          <button
            type="button"
            onClick={() => setShowVariableInfo(!showVariableInfo)}
            className="text-gray-400 hover:text-blue-600 transition-colors"
            title="Show variable instructions"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>
        </div>
        
        {/* Variable Instructions */}
        {showVariableInfo && (
          <div className="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <p className="text-xs font-semibold text-blue-900 mb-2">How to Use Variables:</p>
            <div className="text-xs text-blue-800 space-y-1">
              <div><strong>Basic variable:</strong> <code className="bg-blue-100 px-1 rounded">{'{{variable_name}}'}</code></div>
              <div><strong>Nested variable:</strong> <code className="bg-blue-100 px-1 rounded">{'{{node_id.field}}'}</code></div>
              <div><strong>Array item:</strong> <code className="bg-blue-100 px-1 rounded">{'{{node_id.array[0].field}}'}</code></div>
              <div className="mt-2 pt-2 border-t border-blue-200">
                <strong>Examples:</strong>
                <ul className="ml-4 mt-1 space-y-0.5 list-disc">
                  <li><code>{'{{trigger_data.content}}'}</code> - Comment content</li>
                  <li><code>{'{{node_123.response}}'}</code> - Response from another agent node</li>
                  <li><code>{'{{node_123.results[0].status}}'}</code> - First result status</li>
                </ul>
              </div>
            </div>
          </div>
        )}
        
        {/* Show connected nodes for variable suggestions */}
        <VariableSuggestions
          groupedSuggestions={groupedSuggestions}
          individualSuggestions={individualSuggestions}
          triggerSuggestions={triggerSuggestions}
          insertionConfig={{
            mode: 'textarea',
            targetRef: promptTextareaRef,
            onChange: (newValue) => setConfig({ ...config, prompt: newValue }),
            setConfig,
            config
          }}
          title="Connected Nodes - Click to insert variable:"
        />
        
        <textarea
          ref={promptTextareaRef}
          value={config.prompt || ''}
          onChange={(e) => setConfig({ ...config, prompt: e.target.value })}
          rows={6}
          placeholder="Enter the prompt for the AI agent... You can use variables like {{trigger_data.content}} or {{node_id.field}}"
          className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 font-mono text-sm ${
            !config.prompt || config.prompt.trim() === '' 
              ? 'border-red-300 focus:ring-red-500' 
              : 'border-gray-300 focus:ring-blue-500'
          }`}
          required
        />
        {(!config.prompt || config.prompt.trim() === '') && (
          <p className="text-xs text-red-500 mt-1">
            Prompt is required. The node cannot be saved without a prompt.
          </p>
        )}
      </div>
      
      {/* Custom Decision Options - Only show in decision mode */}
      {isDecisionMode && (
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Custom Decision Options (Optional)
          </label>
          <input
            type="text"
            value={config.custom_decisions || ''}
            onChange={(e) => setConfig({ ...config, custom_decisions: e.target.value })}
            placeholder="e.g., accept, reject, pending (comma-separated)"
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p className="text-xs text-gray-500 mt-1">
            Default options: yes, no, maybe. Add custom options separated by commas.
          </p>
        </div>
      )}
    </div>
  );
}

