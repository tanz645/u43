import React, { useState, useEffect, useRef } from 'react';
import { useWorkflowStore } from '../store/workflowStore';

export default function NodeConfigPanel() {
  const { selectedNode, showConfigPanel, updateNode, toggleConfigPanel, nodes, edges, toolConfigs, triggerConfigs, agentConfigs } = useWorkflowStore();
  const [config, setConfig] = useState({});
  const [toolConfig, setToolConfig] = useState(null);
  const [loadingToolConfig, setLoadingToolConfig] = useState(false);
  const [showVariableInfo, setShowVariableInfo] = useState(false);
  const promptTextareaRef = useRef(null);
  
  // Find connected source nodes for condition nodes and agent nodes
  const getConnectedSourceNodes = () => {
    if (!selectedNode || (selectedNode.data.nodeType !== 'condition' && selectedNode.data.nodeType !== 'agent')) {
      return [];
    }
    
    const incomingEdges = edges.filter(edge => edge.target === selectedNode.id);
    return incomingEdges.map(edge => {
      const sourceNode = nodes.find(n => n.id === edge.source);
      return sourceNode;
    }).filter(Boolean);
  };
  
  const connectedSourceNodes = getConnectedSourceNodes();
  
  // Fetch tool configuration from cache or API
  const fetchToolConfig = async (toolId) => {
    if (!toolId) return;
    
    // First check cached tool configs from store (try both underscore and hyphen versions)
    let foundConfig = toolConfigs?.[toolId];
    if (!foundConfig) {
      const withUnderscores = toolId.replace(/-/g, '_');
      const withHyphens = toolId.replace(/_/g, '-');
      foundConfig = toolConfigs?.[withUnderscores] || toolConfigs?.[withHyphens];
    }
    
    if (foundConfig) {
      console.log('Using cached tool config for', toolId);
      setToolConfig(foundConfig);
      return;
    }
    
    console.log('Tool config not in cache, checking store:', { toolConfigs, toolId });
    
    // If not in cache, try to fetch from API
    if (!window.u43RestUrl) {
      console.warn('REST URL not available');
      setToolConfig(null);
      return;
    }
    
    try {
      setLoadingToolConfig(true);
      
      // Try the specific tool endpoint
      const toolResponse = await fetch(`${window.u43RestUrl}tools/${toolId}`, {
        headers: {
          'X-WP-Nonce': window.u43RestNonce || '',
        },
      });
      
      if (toolResponse.ok) {
        const toolData = await toolResponse.json();
        setToolConfig(toolData);
      } else {
        console.warn(`Tool config not found for ${toolId}`, toolResponse.status);
        setToolConfig(null);
      }
    } catch (error) {
      console.error('Error fetching tool config:', error);
      setToolConfig(null);
    } finally {
      setLoadingToolConfig(false);
    }
  };
  
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
            'whatsapp_send_message': 'whatsapp_send_message',
          };
          configToSet.actionType = toolIdToActionTypeMap[configToSet.tool_id] || configToSet.tool_id;
        }
        setConfig(configToSet);
        
        // Fetch tool config if tool_id is set
        if (configToSet.tool_id) {
          // Check cache first before making API call
          const currentToolConfigs = useWorkflowStore.getState().toolConfigs;
          // Try exact match first
          let foundConfig = currentToolConfigs?.[configToSet.tool_id];
          // If not found, try with hyphens replaced by underscores or vice versa
          if (!foundConfig) {
            const withUnderscores = configToSet.tool_id.replace(/-/g, '_');
            const withHyphens = configToSet.tool_id.replace(/_/g, '-');
            foundConfig = currentToolConfigs?.[withUnderscores] || currentToolConfigs?.[withHyphens];
          }
          
          if (foundConfig) {
            console.log('Found tool config in store for', configToSet.tool_id);
            setToolConfig(foundConfig);
          } else {
            console.log('Tool config not in store, fetching...', configToSet.tool_id, 'Available configs:', Object.keys(currentToolConfigs || {}));
            fetchToolConfig(configToSet.tool_id);
          }
        } else {
          setToolConfig(null);
        }
      } else {
        setConfig(nodeConfig);
        setToolConfig(null);
      }
    }
  }, [selectedNode, edges, toolConfigs]); // Watch edges and toolConfigs to detect when configs are loaded
  
  if (!showConfigPanel || !selectedNode || !selectedNode.data) {
    return null;
  }
  
  const handleSave = () => {
    try {
      // Validate agent nodes require a prompt
      if (selectedNode?.data?.nodeType === 'agent') {
        const prompt = config.prompt || '';
        if (!prompt || prompt.trim() === '') {
          alert('Please enter a prompt for the AI agent. The node cannot be saved without a prompt.');
          return;
        }
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
                        <li><code>{'{{node_123.decision}}'}</code> - Decision from another agent node</li>
                        <li><code>{'{{node_123.results[0].status}}'}</code> - First result status</li>
                      </ul>
                    </div>
                  </div>
                </div>
              )}
              
              {/* Show connected nodes for variable suggestions */}
              {connectedSourceNodes.length > 0 && (
                <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md">
                  <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                    <span>✓</span> Connected Nodes - Click to insert variable:
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
                        // Get outputs from trigger config
                        const triggerId = sourceNode.data.config?.trigger_type || sourceNode.data.triggerType;
                        if (triggerId && triggerConfigs[triggerId] && triggerConfigs[triggerId].outputs) {
                          suggestions = Object.entries(triggerConfigs[triggerId].outputs).map(([key, output]) => ({
                            label: output.label || key,
                            description: `Type: ${output.type || 'string'}`,
                            value: `{{trigger_data.${key}}}`
                          }));
                        } else {
                          // Fallback for common trigger outputs
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
                        }
                      } else if (nodeType === 'action') {
                        // Get outputs from tool config
                        const toolId = sourceNode.data.config?.tool_id || sourceNode.data.toolId;
                        if (toolId) {
                          const normalizedToolId = toolId.replace(/-/g, '_');
                          const normalizedToolIdHyphen = toolId.replace(/_/g, '-');
                          const toolConfig = toolConfigs[toolId] || toolConfigs[normalizedToolId] || toolConfigs[normalizedToolIdHyphen];
                          if (toolConfig && toolConfig.outputs) {
                            suggestions = Object.entries(toolConfig.outputs).map(([key, output]) => ({
                              label: output.label || key,
                              description: `Type: ${output.type || 'string'}`,
                              value: `{{${nodeId}.${key}}}`
                            }));
                          }
                        }
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
                                onClick={() => {
                                  const textarea = promptTextareaRef.current;
                                  const currentPrompt = config.prompt || '';
                                  const cursorPos = textarea ? textarea.selectionStart : currentPrompt.length;
                                  const newPrompt = currentPrompt.slice(0, cursorPos) + suggestion.value + currentPrompt.slice(cursorPos);
                                  setConfig({ ...config, prompt: newPrompt });
                                  // Focus back on textarea and set cursor position
                                  setTimeout(() => {
                                    if (textarea) {
                                      textarea.focus();
                                      const newPos = cursorPos + suggestion.value.length;
                                      textarea.setSelectionRange(newPos, newPos);
                                    }
                                  }, 0);
                                }}
                                className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group"
                                title={suggestion.description}
                              >
                                <div>
                                  <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900">
                                    {suggestion.label}
                                  </span>
                                  <span className="text-xs text-gray-500 ml-2 font-mono">
                                    {suggestion.value}
                                  </span>
                                </div>
                                <div className="text-xs text-gray-500 mt-0.5">
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
              )}
              
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
                  'whatsapp_send_message': 'whatsapp_send_message',
                };
                const tool_id = toolIdMap[actionType] || actionType;
                const newConfig = { 
                  ...config, 
                  actionType: actionType,
                  tool_id: tool_id, // Set tool_id for executor
                };
                setConfig(newConfig);
                
                // Fetch tool config when tool is selected
                if (tool_id) {
                  fetchToolConfig(tool_id);
                } else {
                  setToolConfig(null);
                }
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select action...</option>
              <option value="approve_comment">Approve Comment</option>
              <option value="spam_comment">Spam Comment</option>
              <option value="delete_comment">Delete Comment</option>
              <option value="send_email">Send Email</option>
              <option value="whatsapp_send_message">Send WhatsApp Message</option>
            </select>
            {config.tool_id && (
              <p className="text-xs text-gray-500 mt-1">
                Tool ID: {config.tool_id}
              </p>
            )}
            
            {/* Render tool inputs dynamically */}
            {loadingToolConfig && (
              <p className="text-xs text-gray-500 mt-2">Loading tool configuration...</p>
            )}
            
            {toolConfig && toolConfig.inputs && (
              <div className="mt-4 space-y-4 pt-4 border-t border-gray-200">
                <h4 className="text-sm font-semibold text-gray-700">Tool Inputs</h4>
                {(() => {
                  // Sort inputs: message_type first, then others
                  const sortedInputs = Object.entries(toolConfig.inputs).sort(([keyA], [keyB]) => {
                    if (keyA === 'message_type') return -1;
                    if (keyB === 'message_type') return 1;
                    return 0;
                  });
                  
                  // Get current message_type value
                  const messageType = config.inputs?.message_type ?? toolConfig.inputs.message_type?.default ?? 'text';
                  
                  return sortedInputs.map(([inputKey, inputConfig]) => {
                    const inputValue = config.inputs?.[inputKey] ?? inputConfig.default ?? '';
                    
                    // Conditionally hide template fields when message_type is 'text'
                    if (inputKey === 'template_name' || inputKey === 'template_language') {
                      if (messageType !== 'template') {
                        return null;
                      }
                    }
                    
                    // Conditionally hide message field when message_type is 'template'
                    if (inputKey === 'message' && messageType === 'template') {
                      return null;
                    }
                  
                  // Handle array type inputs (especially phone_numbers)
                  if (inputConfig.type === 'array' || (Array.isArray(inputConfig.type) && inputConfig.type.includes('array'))) {
                    // Special handling for phone_numbers - show comma-separated input
                    if (inputKey === 'phone_numbers') {
                      const phoneArray = Array.isArray(inputValue) ? inputValue : (inputValue ? [inputValue] : []);
                      const phoneString = phoneArray.join(', ');
                      
                      return (
                        <div key={inputKey}>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            {inputConfig.label || inputKey}
                            {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                          </label>
                          <textarea
                            value={phoneString}
                            onChange={(e) => {
                              const phones = e.target.value
                                .split(',')
                                .map(p => p.trim())
                                .filter(p => p.length > 0);
                              setConfig({
                                ...config,
                                inputs: {
                                  ...(config.inputs || {}),
                                  [inputKey]: phones,
                                },
                              });
                            }}
                            placeholder="Enter phone numbers separated by commas (e.g., 8801345318757, +1234567890)"
                            rows={3}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          />
                          <p className="text-xs text-gray-500 mt-1">
                            {inputConfig.description || `Enter phone numbers separated by commas`}
                          </p>
                        </div>
                      );
                    }
                    
                    // Generic array input (JSON format)
                    return (
                      <div key={inputKey}>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          {inputConfig.label || inputKey}
                          {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                        </label>
                        <textarea
                          value={Array.isArray(inputValue) ? JSON.stringify(inputValue, null, 2) : (inputValue || '[]')}
                          onChange={(e) => {
                            try {
                              const parsed = JSON.parse(e.target.value);
                              setConfig({
                                ...config,
                                inputs: {
                                  ...(config.inputs || {}),
                                  [inputKey]: Array.isArray(parsed) ? parsed : [parsed],
                                },
                              });
                            } catch {
                              setConfig({
                                ...config,
                                inputs: {
                                  ...(config.inputs || {}),
                                  [inputKey]: e.target.value,
                                },
                              });
                            }
                          }}
                          placeholder={inputConfig.description || `Enter ${inputKey} as JSON array`}
                          rows={3}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-xs"
                        />
                        <p className="text-xs text-gray-500 mt-1">
                          {inputConfig.description || `Array of ${inputKey}`}
                        </p>
                      </div>
                    );
                  }
                  
                  // Handle select/dropdown inputs
                  if (inputConfig.options) {
                    return (
                      <div key={inputKey}>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          {inputConfig.label || inputKey}
                          {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                        </label>
                        <select
                          value={inputValue}
                          onChange={(e) => setConfig({
                            ...config,
                            inputs: {
                              ...(config.inputs || {}),
                              [inputKey]: e.target.value,
                            },
                          })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                          {Object.entries(inputConfig.options).map(([optValue, optLabel]) => (
                            <option key={optValue} value={optValue}>{optLabel}</option>
                          ))}
                        </select>
                        {inputConfig.description && (
                          <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>
                        )}
                      </div>
                    );
                  }
                  
                  // Handle text/string inputs
                  return (
                    <div key={inputKey}>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        {inputConfig.label || inputKey}
                        {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                      </label>
                      <input
                        type="text"
                        value={inputValue}
                        onChange={(e) => setConfig({
                          ...config,
                          inputs: {
                            ...(config.inputs || {}),
                            [inputKey]: e.target.value,
                          },
                        })}
                        placeholder={inputConfig.description || `Enter ${inputKey}`}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                      {inputConfig.description && (
                        <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>
                      )}
                    </div>
                  );
                  });
                })()}
              </div>
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

