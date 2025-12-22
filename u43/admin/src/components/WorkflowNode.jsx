import { Handle, Position, useUpdateNodeInternals } from 'reactflow';
import { useState, useEffect, useMemo } from 'react';
import { useWorkflowStore } from '../store/workflowStore';
import { renderIcon, iconMap } from './Icons';

/**
 * Custom Workflow Node Component
 */
export default function WorkflowNode({ id, data, selected }) {
  const updateNodeInternals = useUpdateNodeInternals();
  // Safely get nodeType with fallback
  const nodeType = data?.nodeType || 'action';
  const { updateNode, deleteNode, duplicateNode, selectNode, toolConfigs, triggerConfigs, agentConfigs } = useWorkflowStore();
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
  
  const handleDuplicate = (e) => {
    e.stopPropagation();
    duplicateNode(id);
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
        'true': { type: 'boolean', label: 'Yes' },
        'false': { type: 'boolean', label: 'No' },
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
          // For unified LLM agent, filter outputs based on mode
          if (agentId === 'llm_agent' || normalizedAgentId === 'llm_agent' || normalizedAgentIdHyphen === 'llm-agent') {
            const mode = data.config?.settings?.mode || 'chat';
            const allOutputs = agentConfig.outputs;
            
            // Always exclude model_used and tokens_used
            const filteredOutputs = { ...allOutputs };
            delete filteredOutputs.model_used;
            delete filteredOutputs.tokens_used;
            
            // In chat mode, also exclude 'reason' output
            if (mode === 'chat') {
              delete filteredOutputs.reason;
            }
            
            return filteredOutputs;
          }
          
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
        
        // Special handling for button message nodes - generate outputs from buttons
        if (toolId === 'whatsapp_send_button_message' || normalizedToolId === 'whatsapp_send_button_message' || normalizedToolIdHyphen === 'whatsapp_send_button_message') {
          const buttons = data.config?.inputs?.buttons || [];
          // Only generate outputs if buttons exist and have valid IDs
          if (Array.isArray(buttons) && buttons.length > 0) {
            const buttonOutputs = {};
            buttons.forEach((button, index) => {
              // Only add output if button has an ID
              if (button && button.id) {
                const buttonId = button.id;
                const buttonTitle = button.title || buttonId;
                buttonOutputs[buttonId] = {
                  type: 'string',
                  label: buttonTitle
                };
              }
            });
            // Only return outputs if we have valid button outputs
            if (Object.keys(buttonOutputs).length > 0) {
              return buttonOutputs;
            }
          }
          // For button message nodes, return empty object if no buttons configured
          return {};
        }
        
        if (toolConfig && toolConfig.outputs) {
          return toolConfig.outputs;
        }
      }
    }
    
    return {};
  }, [nodeType, data.config, data.config?.settings?.mode, data.triggerType, data.agentId, data.toolId, data.config?.inputs?.buttons, triggerConfigs, agentConfigs, toolConfigs]);
  
  const hasOutputs = Object.keys(nodeOutputs).length > 0;
  
  // Force React Flow to recalculate handle positions when outputs change
  useEffect(() => {
    // Use setTimeout to ensure the DOM has updated with new handles
    const timer = setTimeout(() => {
      if (updateNodeInternals && id) {
        updateNodeInternals(id);
      }
    }, 50);
    
    return () => clearTimeout(timer);
  }, [id, nodeOutputs, data.config?.settings?.mode, updateNodeInternals]);
  
  // Color mapping for different output types - returns both Tailwind class and hex color
  const getOutputColor = (outputKey) => {
    // For unified LLM agent in decision mode, use different colors for response and reason
    if (nodeType === 'agent') {
      const agentId = data.config?.agent_id || data.agentId;
      const mode = data.config?.settings?.mode || 'chat';
      
      if ((agentId === 'llm_agent' || agentId?.replace(/-/g, '_') === 'llm_agent' || agentId?.replace(/_/g, '-') === 'llm-agent') && mode === 'decision') {
        if (outputKey === 'response') {
          return { class: 'bg-blue-500', hex: '#3b82f6' };
        } else if (outputKey === 'reason') {
          return { class: 'bg-amber-500', hex: '#f59e0b' };
        }
      }
    }
    
    // Default color for all other outputs
    return { class: 'bg-blue-500', hex: '#3b82f6' };
  };
  
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
      style={{ position: 'relative', overflow: 'visible', minHeight: '60px' }}
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
          {!data.category && data.icon && (
            <span className="flex-shrink-0">
              {renderIcon(data.icon, null)}
            </span>
          )}
          {!data.category && !data.icon && (
            <div className={`w-3 h-3 rounded-full flex-shrink-0 ${nodeTypeColors[nodeType] || 'bg-gray-500'}`} />
          )}
          <div className="text-xs text-gray-500 flex-shrink-0">{nodeTypeLabels[nodeType]}</div>
          <div className="ml-auto flex items-center gap-1">
            <button
              onClick={handleDuplicate}
              onMouseDown={(e) => e.stopPropagation()}
              className="text-gray-400 hover:text-blue-600 transition-colors flex-shrink-0 p-1 rounded hover:bg-blue-50"
              title="Duplicate node"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
            <button
              onClick={handleDelete}
              onMouseDown={(e) => e.stopPropagation()}
              className="text-gray-400 hover:text-red-600 transition-colors flex-shrink-0 p-1 rounded hover:bg-red-50"
              title="Delete node"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
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
        
        {/* Show buttons for button message nodes */}
        {nodeType === 'action' && data.config?.tool_id === 'whatsapp_send_button_message' && data.config?.inputs?.buttons && Array.isArray(data.config.inputs.buttons) && data.config.inputs.buttons.length > 0 && (
          <div className="mt-2 pt-2 border-t border-gray-200">
            <div className="text-xs font-medium text-gray-700 mb-1.5">Buttons:</div>
            <div className="space-y-1.5">
              {data.config.inputs.buttons.map((button, index) => {
                const buttonColors = ['#3B82F6', '#10B981', '#F59E0B']; // Blue, Green, Orange
                const buttonColor = button.color || buttonColors[index] || buttonColors[0];
                const buttonId = button.id || `btn${index + 1}`;
                const buttonTitle = button.title || buttonId;
                
                return (
                  <div key={index} className="flex items-center gap-2">
                    <div 
                      className="w-4 h-4 rounded border-2 border-white flex-shrink-0 shadow-sm" 
                      style={{ backgroundColor: buttonColor }}
                      title={`Button: ${buttonTitle}`}
                    />
                    <span className="text-xs text-gray-600 truncate flex-1">
                      {buttonTitle}
                    </span>
                  </div>
                );
              })}
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
                Object.entries(nodeOutputs).map(([key, output]) => {
                  const colorInfo = nodeType === 'agent' ? getOutputColor(key) : { hex: '#9ca3af' };
                  return (
                    <div key={key} className="text-xs">
                      <div className="flex items-start gap-2">
                        {nodeType === 'agent' && (
                          <div 
                            className="w-2.5 h-2.5 rounded-full border border-white flex-shrink-0 mt-0.5" 
                            style={{ backgroundColor: colorInfo.hex }}
                          />
                        )}
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
                  );
                })
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
      
      {/* Output Handles - Condition nodes and Agent nodes have multiple outputs */}
      {nodeType === 'condition' ? (
        <>
          <Handle
            type="source"
            id="true"
            position={Position.Right}
            className="!bg-green-500 !border-white"
            style={{ top: '30%', right: '-6px', left: 'auto', transform: 'none' }}
            label="Yes"
          />
          <Handle
            type="source"
            id="false"
            position={Position.Right}
            className="!bg-red-500 !border-white"
            style={{ top: '70%', right: '-6px', left: 'auto', transform: 'none' }}
            label="No"
          />
        </>
      ) : nodeType === 'agent' && hasOutputs ? (
        <>
          {Object.entries(nodeOutputs).map(([outputKey, output], index, arr) => {
            // Calculate position: distribute handles evenly along the right side
            const totalOutputs = arr.length;
            // Better positioning: center for single output, evenly spaced for multiple
            let topPercentage;
            if (totalOutputs === 1) {
              topPercentage = '50%';
            } else if (totalOutputs === 2) {
              // For 2 outputs (response and reason in decision mode), position at 35% and 65%
              topPercentage = index === 0 ? '35%' : '65%';
            } else {
              // For 3+ outputs, distribute evenly between 25% and 75%
              topPercentage = `${25 + (index * (50 / (totalOutputs - 1)))}%`;
            }
            const colorInfo = getOutputColor(outputKey);
            
            return (
              <Handle
                key={outputKey}
                type="source"
                id={outputKey}
                position={Position.Right}
                className="!border-white !cursor-crosshair"
                style={{ 
                  top: topPercentage,
                  backgroundColor: colorInfo.hex,
                  width: '12px',
                  height: '12px',
                  zIndex: 1000,
                  border: '2px solid white',
                  pointerEvents: 'all',
                  left: 'auto',
                  right: '-6px',
                  transform: 'none'
                }}
                label={output.label || outputKey}
              />
            );
          })}
        </>
      ) : nodeType === 'action' && (data.config?.tool_id === 'whatsapp_send_button_message' || data.toolId === 'whatsapp_send_button_message') ? (
        // Button message nodes: only show handles when buttons exist and have valid IDs
        (() => {
          const buttons = data.config?.inputs?.buttons || [];
          const hasValidButtons = Array.isArray(buttons) && buttons.length > 0 && buttons.some(btn => btn && btn.id);
          return hasValidButtons && hasOutputs && Object.keys(nodeOutputs).length > 0;
        })() ? (
          <>
            {Object.entries(nodeOutputs).map(([outputKey, output], index, arr) => {
              const buttons = data.config?.inputs?.buttons || [];
              const buttonIndex = buttons.findIndex(btn => (btn.id || '').toString() === outputKey.toString());
              
              // Use button colors - match the button display
              const buttonColors = ['#3B82F6', '#10B981', '#F59E0B']; // Blue, Green, Orange
              let handleColor = buttonColors[index] || buttonColors[0];
              if (buttonIndex >= 0 && buttons[buttonIndex].color) {
                handleColor = buttons[buttonIndex].color;
              }
              
              // Calculate position: distribute handles evenly along the right side
              const totalOutputs = arr.length;
              const topPercentage = totalOutputs === 1 ? '50%' : `${30 + (index * (40 / (totalOutputs - 1)))}%`;
              
              return (
                <Handle
                  key={outputKey}
                  type="source"
                  id={outputKey}
                  position={Position.Right}
                  className="!border-white"
                  style={{ 
                    top: topPercentage,
                    backgroundColor: handleColor 
                  }}
                  label={output.label || outputKey}
                />
              );
            })}
          </>
        ) : null
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

