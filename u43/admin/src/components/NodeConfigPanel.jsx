import { useState, useEffect } from 'react';
import { useWorkflowStore } from '../store/workflowStore';

export default function NodeConfigPanel() {
  const { selectedNode, showConfigPanel, updateNode, toggleConfigPanel, nodes, edges } = useWorkflowStore();
  const [config, setConfig] = useState({});
  
  // Find connected source nodes for condition nodes
  const getConnectedSourceNodes = () => {
    if (!selectedNode || selectedNode.data.nodeType !== 'condition') {
      return [];
    }
    
    const incomingEdges = edges.filter(edge => edge.target === selectedNode.id);
    return incomingEdges.map(edge => {
      const sourceNode = nodes.find(n => n.id === edge.source);
      return sourceNode;
    }).filter(Boolean);
  };
  
  const connectedSourceNodes = getConnectedSourceNodes();
  
  useEffect(() => {
    if (selectedNode && selectedNode.data) {
      // Safely get config with defaults for condition nodes
      const nodeConfig = selectedNode.data.config || {};
      if (selectedNode.data.nodeType === 'condition') {
        setConfig({
          field: nodeConfig.field || '',
          operator: nodeConfig.operator || 'equals',
          value: nodeConfig.value || '',
          title: nodeConfig.title || selectedNode.data.label || 'Condition',
          description: nodeConfig.description || '',
        });
      } else if (selectedNode.data.nodeType === 'action') {
        // For action nodes, ensure actionType is set from tool_id if needed
        const configToSet = { ...nodeConfig };
        if (!configToSet.actionType && configToSet.tool_id) {
          // Map tool_id back to actionType for dropdown
          const toolIdToActionTypeMap = {
            'wordpress_approve_comment': 'approve_comment',
            'wordpress_spam_comment': 'spam_comment',
            'wordpress_delete_comment': 'delete_comment',
            'wordpress_send_email': 'send_email',
          };
          configToSet.actionType = toolIdToActionTypeMap[configToSet.tool_id] || configToSet.tool_id;
        }
        setConfig(configToSet);
      } else {
        setConfig(nodeConfig);
      }
    }
  }, [selectedNode, edges]); // Watch edges too to detect new connections
  
  if (!showConfigPanel || !selectedNode || !selectedNode.data) {
    return null;
  }
  
  const handleSave = () => {
    try {
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
        toggleConfigPanel();
      }
    } catch (error) {
      console.error('Error canceling node config:', error);
    toggleConfigPanel();
    }
  };
  
  return (
    <div className="w-80 bg-white border-l border-gray-200 h-full overflow-y-auto flex-shrink-0" style={{ height: '100%' }}>
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
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Trigger Type
            </label>
            <input
              type="text"
              value={config.trigger_type || 'wordpress_comment_post'}
              onChange={(e) => setConfig({ ...config, trigger_type: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="wordpress_comment_post"
            />
            <p className="text-xs text-gray-500 mt-1">
              The trigger ID that this node listens to
            </p>
          </div>
        )}
        
        {selectedNode?.data?.nodeType === 'agent' && (
          <div className="space-y-4">
            {/* Model Information */}
            <div className="p-3 bg-gray-50 border border-gray-200 rounded-md">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-semibold text-gray-700 mb-1">AI Model</p>
                  <p className="text-sm text-gray-900">
                    {config.settings?.model || 'gpt-3.5-turbo'}
                  </p>
                </div>
                {config.settings?.provider && (
                  <div className="text-xs text-gray-500">
                    Provider: {config.settings.provider}
                  </div>
                )}
              </div>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
              Prompt
            </label>
            <textarea
              value={config.prompt || ''}
              onChange={(e) => setConfig({ ...config, prompt: e.target.value })}
              rows={4}
              placeholder="Enter the prompt for the AI agent..."
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            </div>
            
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
          </div>
        )}
        
        {selectedNode?.data?.nodeType === 'condition' && (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Field to Check
              </label>
              
              {/* Show connected nodes */}
              {connectedSourceNodes.length > 0 && (
                <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md">
                  <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                    <span>✓</span> Connected Nodes - Click to use:
                  </p>
                  <div className="space-y-2">
                    {connectedSourceNodes.map((sourceNode) => {
                      const nodeId = sourceNode.id;
                      const nodeType = sourceNode.data.nodeType;
                      const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
                      let suggestions = [];
                      
                      if (nodeType === 'agent') {
                        suggestions = [
                          { 
                            label: 'Decision', 
                            description: 'The decision made (yes, no, maybe, etc.)',
                            value: `{{${nodeId}.decision}}` 
                          },
                          { 
                            label: 'Reasoning', 
                            description: 'The explanation for the decision',
                            value: `{{${nodeId}.reasoning}}` 
                          },
                        ];
                      } else if (nodeType === 'trigger') {
                        suggestions = [
                          { 
                            label: 'Comment ID', 
                            description: 'The ID of the comment',
                            value: `{{trigger_data.comment_id}}` 
                          },
                          { 
                            label: 'Author', 
                            description: 'The comment author name',
                            value: `{{trigger_data.author}}` 
                          },
                          { 
                            label: 'Content', 
                            description: 'The comment text content',
                            value: `{{trigger_data.content}}` 
                          },
                          { 
                            label: 'Email', 
                            description: 'The comment author email',
                            value: `{{trigger_data.email}}` 
                          },
                        ];
                      } else {
                        // For other node types, show generic output
                        suggestions = [
                          { 
                            label: 'Output', 
                            description: 'The output from this node',
                            value: `{{${nodeId}}}` 
                          },
                        ];
                      }
                      
                      return (
                        <div key={nodeId} className="bg-white rounded p-2 border border-green-100">
                          <div className="flex items-center gap-2 mb-1">
                            <span className="text-xs font-semibold text-green-900">{nodeLabel}</span>
                            <span className="text-xs text-gray-500">({nodeType})</span>
                          </div>
                          <div className="space-y-1">
                            {suggestions.map((suggestion, idx) => (
                              <button
                                key={idx}
                                type="button"
                                onClick={() => setConfig({ ...config, field: suggestion.value })}
                                className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group"
                                title={suggestion.description}
                              >
                                <div>
                                  <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900">
                                    {suggestion.label}
                                  </span>
                                  <span className="text-xs text-gray-500 ml-2">
                                    {suggestion.description}
                                  </span>
                                </div>
                              </button>
                            ))}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}
              
              <input
                type="text"
                value={config.field || ''}
                onChange={(e) => setConfig({ ...config, field: e.target.value })}
                placeholder="e.g., {'{{'}agent_node_id.decision{'}}'} or {'{{'}trigger_data.comment_id{'}}'}"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <div className="mt-2 p-3 bg-blue-50 rounded-md">
                <p className="text-xs font-semibold text-blue-900 mb-2">How to Reference Data:</p>
                <div className="text-xs text-blue-800 space-y-2">
                  <div>
                    <strong>From Agent Nodes:</strong>
                    <ul className="ml-4 mt-1 space-y-0.5 list-disc">
                      <li>Use {'{{'}node_name.decision{'}}'} to check the decision</li>
                      <li>Use {'{{'}node_name.reasoning{'}}'} to check the reasoning</li>
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
        )}
        
        {selectedNode?.data?.nodeType === 'action' && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Action Type
            </label>
            <select
              value={config.actionType || config.tool_id || ''}
              onChange={(e) => {
                const actionType = e.target.value;
                // Map actionType to tool_id
                const toolIdMap = {
                  'approve_comment': 'wordpress_approve_comment',
                  'spam_comment': 'wordpress_spam_comment',
                  'delete_comment': 'wordpress_delete_comment',
                  'send_email': 'wordpress_send_email',
                };
                const tool_id = toolIdMap[actionType] || actionType;
                setConfig({ 
                  ...config, 
                  actionType: actionType,
                  tool_id: tool_id, // Set tool_id for executor
                });
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select action...</option>
              <option value="approve_comment">Approve Comment</option>
              <option value="spam_comment">Spam Comment</option>
              <option value="delete_comment">Delete Comment</option>
              <option value="send_email">Send Email</option>
            </select>
            {config.tool_id && (
              <p className="text-xs text-gray-500 mt-1">
                Tool ID: {config.tool_id}
              </p>
            )}
          </div>
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

