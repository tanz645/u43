import { useRef } from 'react';
import { useWorkflowStore } from '../store/workflowStore';
import { useNodeOutputs } from './NodeConfigPanel/hooks/useNodeOutputs';
import { useVariableSuggestions } from './NodeConfigPanel/hooks/useVariableSuggestions';
import { useConnectedNodes } from './NodeConfigPanel/hooks/useConnectedNodes';
import { useOpenAIModels } from './NodeConfigPanel/hooks/useOpenAIModels';
import { useToolConfig } from './NodeConfigPanel/hooks/useToolConfig';
import { useNodeConfig } from './NodeConfigPanel/hooks/useNodeConfig';
import { useNodeValidation } from './NodeConfigPanel/hooks/useNodeValidation';
import TriggerNodeConfig from './NodeConfigPanel/node-types/TriggerNodeConfig';
import AgentNodeConfig from './NodeConfigPanel/node-types/AgentNodeConfig';
import ConditionNodeConfig from './NodeConfigPanel/node-types/ConditionNodeConfig';
import ActionNodeConfig from './NodeConfigPanel/node-types/ActionNodeConfig';

export default function NodeConfigPanel() {
  const { selectedNode, showConfigPanel, updateNode, toggleConfigPanel, toolConfigs, triggerConfigs, agentConfigs } = useWorkflowStore();
  
  // Refs for variable insertion (used in ActionNodeConfig)
  const lastFocusedInputRef = useRef(null);
  const lastFocusedInputInfoRef = useRef({ index: null, isKey: false, inputKey: null });
  const lastFocusedTextareaRef = useRef(null);
  const lastFocusedTextareaInputKeyRef = useRef(null);
  
  // Use hooks for connected nodes
  const { connectedSourceNodes, triggerNodes } = useConnectedNodes(selectedNode);
  
  // Use hooks for OpenAI models
  const { openaiModels, loadingModels, fetchOpenAIModels } = useOpenAIModels();
  
  // Use hooks for tool config
  const { toolConfig, loadingToolConfig, fetchToolConfig, setToolConfig } = useToolConfig();
  
  // Use hook for node config
  const { config, setConfig } = useNodeConfig(selectedNode, fetchToolConfig, setToolConfig, fetchOpenAIModels);
  
  // Use hook for validation
  const { validateNode } = useNodeValidation(selectedNode, config, toolConfig);
  
  // Use hook for node outputs calculation
  const { grouped: groupedParentNodes, individual: individualParentNodes } = useNodeOutputs(
    connectedSourceNodes,
    { toolConfigs, triggerConfigs, agentConfigs }
  );
  
  // Use hook for variable suggestions
  const {
    groupedSuggestions,
    individualSuggestions,
    triggerSuggestions,
    hasSuggestions
  } = useVariableSuggestions({
    groupedParentNodes,
    individualParentNodes,
    triggerNodes,
    connectedSourceNodes,
    configs: { toolConfigs, triggerConfigs, agentConfigs }
  });
  
  if (!showConfigPanel || !selectedNode || !selectedNode.data) {
    return null;
  }
  
  const handleSave = () => {
    try {
      // Validate node configuration
      const validation = validateNode();
      if (!validation.isValid) {
        validation.errors.forEach(error => alert(error));
        return;
      }
      
      if (selectedNode && selectedNode.id) {
        updateNode(selectedNode.id, { config });
        toggleConfigPanel();
      }
    } catch (error) {
      console.error('Error saving node config:', error);
      alert('Error saving configuration: ' + error.message);
    }
  };
  
  const handleCancel = () => {
    try {
      // Reset config to original node config
      if (selectedNode && selectedNode.data) {
        const nodeConfig = selectedNode.data.config || {};
        if (selectedNode.data.nodeType === 'condition') {
          setConfig({
            field: nodeConfig.field || '',
            operator: nodeConfig.operator || 'equals',
            value: nodeConfig.value || '',
            title: nodeConfig.title || selectedNode.data.label || 'Condition',
            description: nodeConfig.description || '',
          });
        } else {
          setConfig(nodeConfig);
        }
      }
      toggleConfigPanel();
    } catch (error) {
      console.error('Error canceling node config:', error);
      toggleConfigPanel();
    }
  };
  
  return (
    <div className="w-80 bg-white border-l border-gray-200 h-full overflow-y-auto flex-shrink-0">
      <div className="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 className="font-semibold text-gray-900">Configure Node</h3>
        <button
          onClick={toggleConfigPanel}
          className="text-gray-500 hover:text-gray-700"
        >
          ✕
        </button>
      </div>
      
      <div className="p-4 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Node Title
          </label>
          <input
            type="text"
            value={config.title || selectedNode?.data?.label || ''}
            onChange={(e) => setConfig({ ...config, title: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            value={config.description || ''}
            onChange={(e) => setConfig({ ...config, description: e.target.value })}
            rows={3}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        {/* Node-specific configuration */}
        {selectedNode?.data?.nodeType === 'trigger' && (
          <TriggerNodeConfig
            config={config}
            setConfig={setConfig}
            triggerConfigs={triggerConfigs}
          />
        )}
        
        {selectedNode?.data?.nodeType === 'agent' && (
          <AgentNodeConfig
            config={config}
            setConfig={setConfig}
            openaiModels={openaiModels}
            loadingModels={loadingModels}
            groupedSuggestions={groupedSuggestions}
            individualSuggestions={individualSuggestions}
            triggerSuggestions={triggerSuggestions}
          />
        )}
        
        {selectedNode?.data?.nodeType === 'condition' && (
          <ConditionNodeConfig
            config={config}
            setConfig={setConfig}
            groupedSuggestions={groupedSuggestions}
            individualSuggestions={individualSuggestions}
            triggerSuggestions={triggerSuggestions}
            connectedSourceNodes={connectedSourceNodes}
          />
        )}
        
        {selectedNode?.data?.nodeType === 'action' && (
          <ActionNodeConfig
            config={config}
            setConfig={setConfig}
            toolConfig={toolConfig}
            loadingToolConfig={loadingToolConfig}
            variableProps={{
              groupedSuggestions,
              individualSuggestions,
              triggerSuggestions,
              hasSuggestions,
              triggerNodes,
              triggerConfigs,
              connectedSourceNodes
            }}
            lastFocusedTextareaRef={lastFocusedTextareaRef}
            lastFocusedTextareaInputKeyRef={lastFocusedTextareaInputKeyRef}
            lastFocusedInputRef={lastFocusedInputRef}
            lastFocusedInputInfoRef={lastFocusedInputInfoRef}
            connectedSourceNodes={connectedSourceNodes}
          />
        )}
        
        <div className="flex gap-2 pt-4 border-t border-gray-200">
          <button
            onClick={handleSave}
            className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
          >
            Save
          </button>
          <button
            onClick={handleCancel}
            className="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
}
