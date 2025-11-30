import { Handle, Position } from 'reactflow';
import { useState, useEffect, useMemo } from 'react';
import { useWorkflowStore } from '../store/workflowStore';
import { renderIcon, iconMap } from './Icons';

/**
 * Custom Workflow Node Component
 */
export default function WorkflowNode({ id, data, selected }) {
  // Safely get nodeType with fallback
  const nodeType = data?.nodeType || 'action';
  const { updateNode, deleteNode, selectNode, toolConfigs, triggerConfigs, agentConfigs } = useWorkflowStore();
  const [isEditingTitle, setIsEditingTitle] = useState(false);
  const [title, setTitle] = useState(data?.label || data?.config?.title || 'Untitled Node');
  const [showOutputs, setShowOutputs] = useState(false);
  
  // Ensure data exists
  if (!data) {
    console.error('WorkflowNode: Missing data prop', { id, data, selected });
    return null;
  }
  
  const nodeTypeColors = {
    trigger: 'bg-green-500',
    action: 'bg-blue-500',
    agent: 'bg-purple-500',
    condition: 'bg-orange-500',
  };
  
  const nodeTypeLabels = {
    trigger: 'Trigger',
    action: 'Action',
    agent: 'Agent',
    condition: 'Condition',
  };
  
  // Helper function to truncate text to max length with ellipsis
  const truncateText = (text, maxLength = 50) => {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  };
  
  const handleTitleClick = (e) => {
    e.stopPropagation();
    setIsEditingTitle(true);
    selectNode(id);
  };
  
  const handleTitleBlur = () => {
    try {
    setIsEditingTitle(false);
      if (id && data) {
    updateNode(id, {
      label: title,
      config: {
            ...(data.config || {}),
        title: title,
      },
    });
      }
    } catch (error) {
      console.error('Error updating node title:', error);
    }
  };
  
  const handleTitleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleTitleBlur();
    }
    if (e.key === 'Escape') {
      setTitle(data.label || data.config?.title || 'Untitled Node');
      setIsEditingTitle(false);
    }
  };
  
  const handleDelete = (e) => {
    e.stopPropagation();
    if (confirm('Are you sure you want to delete this node?')) {
      deleteNode(id);
    }
  };
  
  const handleNodeClick = () => {
    try {
      if (id && data) {
    selectNode(id);
      }
    } catch (error) {
      console.error('Error selecting node:', error, { id, data });
    }
  };
  
  // Sync title when data changes externally
  useEffect(() => {
    const newTitle = data.label || data.config?.title || 'Untitled Node';
    if (newTitle !== title && !isEditingTitle) {
      setTitle(newTitle);
    }
  }, [data.label, data.config?.title]);
  
  // Get outputs for this node based on node type
  const nodeOutputs = useMemo(() => {
    if (nodeType === 'condition') {
      return {
        'true': { type: 'boolean', label: 'True' },
        'false': { type: 'boolean', label: 'False' },
      };
    }
    
    if (nodeType === 'trigger') {
      const triggerId = data.config?.trigger_type || data.triggerType;
      if (triggerId) {
        // Try both underscore and hyphen versions
        const normalizedTriggerId = triggerId.replace(/-/g, '_');
        const normalizedTriggerIdHyphen = triggerId.replace(/_/g, '-');
        const triggerConfig = triggerConfigs[triggerId] || triggerConfigs[normalizedTriggerId] || triggerConfigs[normalizedTriggerIdHyphen];
        if (triggerConfig && triggerConfig.outputs) {
          return triggerConfig.outputs;
        }
      }
    }
    
    if (nodeType === 'agent') {
      const agentId = data.config?.agent_id || data.agentId;
      if (agentId) {
        // Try both underscore and hyphen versions
        const normalizedAgentId = agentId.replace(/-/g, '_');
        const normalizedAgentIdHyphen = agentId.replace(/_/g, '-');
        const agentConfig = agentConfigs[agentId] || agentConfigs[normalizedAgentId] || agentConfigs[normalizedAgentIdHyphen];
        if (agentConfig && agentConfig.outputs) {
          return agentConfig.outputs;
        }
      }
    }
    
    if (nodeType === 'action') {
      const toolId = data.config?.tool_id || data.toolId;
      if (toolId) {
        // Try both underscore and hyphen versions
        const normalizedToolId = toolId.replace(/-/g, '_');
        const normalizedToolIdHyphen = toolId.replace(/_/g, '-');
        const toolConfig = toolConfigs[toolId] || toolConfigs[normalizedToolId] || toolConfigs[normalizedToolIdHyphen];
        if (toolConfig && toolConfig.outputs) {
          return toolConfig.outputs;
        }
      }
    }
    
    return {};
  }, [nodeType, data.config, data.triggerType, data.agentId, data.toolId, triggerConfigs, agentConfigs, toolConfigs]);
  
  const hasOutputs = Object.keys(nodeOutputs).length > 0;
  
  // Debug logging - log immediately when component renders
  useEffect(() => {
    console.log(`[WorkflowNode] Node ${id} - Type: ${nodeType}`, {
      hasOutputs,
      outputCount: Object.keys(nodeOutputs).length,
      nodeOutputs,
      triggerId: data.config?.trigger_type || data.triggerType,
      agentId: data.config?.agent_id || data.agentId,
      toolId: data.config?.tool_id || data.toolId,
      triggerConfigsCount: Object.keys(triggerConfigs || {}).length,
      agentConfigsCount: Object.keys(agentConfigs || {}).length,
      toolConfigsCount: Object.keys(toolConfigs || {}).length,
    });
  }, [id, nodeType, hasOutputs, nodeOutputs]);
  
  return (
    <div 
      className={`workflow-node ${nodeType} ${selected ? 'selected' : ''}`}
      onClick={handleNodeClick}
    >
      {/* Input Handle */}
      {nodeType !== 'trigger' && (
        <Handle
          type="target"
          position={Position.Left}
          className="!bg-white !border-gray-400"
        />
      )}
      
      {/* Node Header */}
      <div className="px-4 py-2 border-b border-gray-200">
        <div className="flex items-center gap-2 mb-1">
          {/* Category Icon */}
          {data.category && (
            <span className="flex-shrink-0">
              {renderIcon(data.icon || iconMap[nodeType] || '⚙️', data.category)}
            </span>
          )}
          {!data.category && (
          <div className={`w-3 h-3 rounded-full flex-shrink-0 ${nodeTypeColors[nodeType] || 'bg-gray-500'}`} />
          )}
          <div className="text-xs text-gray-500 flex-shrink-0">{nodeTypeLabels[nodeType]}</div>
          <button
            onClick={handleDelete}
            onMouseDown={(e) => e.stopPropagation()}
            className="ml-auto text-gray-400 hover:text-red-600 transition-colors flex-shrink-0 p-1 rounded hover:bg-red-50"
            title="Delete node"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
        
        {/* Editable Title */}
        <div className="flex-1">
          {isEditingTitle ? (
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              onBlur={handleTitleBlur}
              onKeyDown={handleTitleKeyDown}
              onClick={(e) => e.stopPropagation()}
              className="w-full font-semibold text-sm text-gray-900 bg-transparent border-b-2 border-blue-500 focus:outline-none"
              autoFocus
            />
          ) : (
            <div 
              className="font-semibold text-sm text-gray-900 cursor-text hover:text-blue-600 transition-colors truncate"
              onClick={handleTitleClick}
              title={title}
            >
              {truncateText(title, 50)}
            </div>
          )}
        </div>
      </div>
      
      {/* Node Body */}
      <div className="px-4 py-2">
        {data.description && (
          <div className="text-xs text-gray-600 truncate" title={data.description}>
            {truncateText(data.description, 50)}
          </div>
        )}
        {data.config?.description && !data.description && (
          <div className="text-xs text-gray-600 truncate" title={data.config.description}>
            {truncateText(data.config.description, 50)}
          </div>
        )}
        {/* Show model info for agent nodes */}
        {nodeType === 'agent' && data.config?.settings?.model && (
          <div className="mt-2 pt-2 border-t border-gray-200">
            <div className="text-xs text-gray-500">
              <span className="font-medium">Model:</span> {data.config.settings.model}
            </div>
          </div>
        )}
      </div>
      
      {/* Output Preview Section - Always show for all nodes */}
      <div className="border-t border-gray-200">
        <button
          onClick={(e) => {
            e.stopPropagation();
            e.preventDefault();
            setShowOutputs(!showOutputs);
            console.log(`[WorkflowNode] Toggling outputs for node ${id}, showOutputs: !showOutputs}`);
          }}
          onMouseDown={(e) => e.stopPropagation()}
          className="w-full px-4 py-2 flex items-center justify-between text-xs text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer"
          title={showOutputs ? 'Hide outputs' : 'Show outputs'}
        >
          <span className="font-medium flex items-center gap-1">
            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Outputs {hasOutputs ? `(${Object.keys(nodeOutputs).length})` : '(0)'}
          </span>
          <svg
            className={`w-4 h-4 transition-transform flex-shrink-0 ${showOutputs ? 'transform rotate-180' : ''}`}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        
        {showOutputs && (
          <div className="px-4 pb-3 bg-gray-50 border-t border-gray-200">
            <div className="space-y-2 pt-2">
              {Object.entries(nodeOutputs).length > 0 ? (
                Object.entries(nodeOutputs).map(([key, output]) => (
                  <div key={key} className="text-xs">
                    <div className="flex items-start gap-2">
                      <span className="font-mono text-blue-600 font-semibold flex-shrink-0">
                        {key}
                      </span>
                      <span className="text-gray-500">:</span>
                      <div className="flex-1 min-w-0">
                        <div className="text-gray-700 font-medium truncate" title={output.label || key}>
                          {output.label || key}
                        </div>
                        {output.type && (
                          <div className="text-gray-400 text-[10px] mt-0.5">
                            {output.type}
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                ))
              ) : (
                <div className="text-xs text-gray-400 italic">
                  No outputs available. Configs may not be loaded yet.
                  <br />
                  <span className="text-[10px]">Node Type: {nodeType}</span>
                </div>
              )}
            </div>
          </div>
        )}
      </div>
      
      {/* Output Handles - Condition nodes have multiple outputs */}
      {nodeType === 'condition' ? (
        <>
          <Handle
            type="source"
            id="true"
            position={Position.Right}
            className="!bg-green-500 !border-white"
            style={{ top: '30%' }}
            label="True"
          />
          <Handle
            type="source"
            id="false"
            position={Position.Right}
            className="!bg-red-500 !border-white"
            style={{ top: '70%' }}
            label="False"
          />
        </>
      ) : (
      <Handle
        type="source"
        position={Position.Right}
        className="!bg-white !border-gray-400"
      />
      )}
      
      {/* Status Indicator */}
      {data.executionStatus && (
        <div className={`absolute top-2 right-2 w-2 h-2 rounded-full ${
          data.executionStatus === 'success' ? 'bg-green-500' :
          data.executionStatus === 'failed' ? 'bg-red-500' :
          data.executionStatus === 'running' ? 'bg-yellow-500 animate-pulse' :
          'bg-gray-400'
        }`} title={`Status: ${data.executionStatus}`} />
      )}
    </div>
  );
}

