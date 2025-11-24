import { Handle, Position } from 'reactflow';
import { useState, useEffect } from 'react';
import { useWorkflowStore } from '../store/workflowStore';

/**
 * Custom Workflow Node Component
 */
export default function WorkflowNode({ id, data, selected }) {
  // Safely get nodeType with fallback
  const nodeType = data?.nodeType || 'action';
  const { updateNode, deleteNode, selectNode } = useWorkflowStore();
  const [isEditingTitle, setIsEditingTitle] = useState(false);
  const [title, setTitle] = useState(data?.label || data?.config?.title || 'Untitled Node');
  
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
          <div className={`w-3 h-3 rounded-full flex-shrink-0 ${nodeTypeColors[nodeType] || 'bg-gray-500'}`} />
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
              className="font-semibold text-sm text-gray-900 cursor-text hover:text-blue-600 transition-colors"
              onClick={handleTitleClick}
              title="Click to edit title"
            >
              {title}
            </div>
          )}
        </div>
      </div>
      
      {/* Node Body */}
      <div className="px-4 py-2">
        {data.description && (
          <div className="text-xs text-gray-600">{data.description}</div>
        )}
        {data.config?.description && !data.description && (
          <div className="text-xs text-gray-600">{data.config.description}</div>
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

