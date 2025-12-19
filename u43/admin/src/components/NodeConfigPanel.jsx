import React, { useState, useEffect, useRef } from 'react';
import { useWorkflowStore } from '../store/workflowStore';

export default function NodeConfigPanel() {
  const { selectedNode, showConfigPanel, updateNode, toggleConfigPanel, nodes, edges, toolConfigs, triggerConfigs, agentConfigs } = useWorkflowStore();
  const [config, setConfig] = useState({});
  const [toolConfig, setToolConfig] = useState(null);
  const [loadingToolConfig, setLoadingToolConfig] = useState(false);
  const [showVariableInfo, setShowVariableInfo] = useState(false);
  const [openaiModels, setOpenaiModels] = useState([]);
  const [loadingModels, setLoadingModels] = useState(false);
  const promptTextareaRef = useRef(null);
  // Track last focused input for variable insertion in HTTP tools
  const lastFocusedInputRef = useRef(null);
  const lastFocusedInputInfoRef = useRef({ index: null, isKey: false, inputKey: null });
  // Track last focused textarea for variable insertion (for message fields)
  const lastFocusedTextareaRef = useRef(null);
  const lastFocusedTextareaInputKeyRef = useRef(null);
  
  // Find connected source nodes for condition nodes, agent nodes, and action nodes
  const getConnectedSourceNodes = () => {
    if (!selectedNode) {
      return [];
    }
    
    // For action nodes, only show connected nodes when configuring tool inputs that support variables
    // For condition and agent nodes, always show connected nodes
    const nodeType = selectedNode.data.nodeType;
    if (nodeType !== 'condition' && nodeType !== 'agent' && nodeType !== 'action') {
      return [];
    }
    
    const incomingEdges = edges.filter(edge => edge.target === selectedNode.id);
    return incomingEdges.map(edge => {
      const sourceNode = nodes.find(n => n.id === edge.source);
      return sourceNode;
    }).filter(Boolean);
  };
  
  // Find all trigger nodes in the workflow (trigger_data is always available)
  const getTriggerNodes = () => {
    if (!selectedNode) {
      return [];
    }
    
    const nodeType = selectedNode.data.nodeType;
    // Only show trigger nodes for nodes that can use variables (condition, agent, action)
    if (nodeType !== 'condition' && nodeType !== 'agent' && nodeType !== 'action') {
      return [];
    }
    
    // Find all trigger nodes in the workflow
    return nodes.filter(node => node.data?.nodeType === 'trigger');
  };
  
  // Group parent nodes by type and output structure, creating combined variables
  const getGroupedParentNodes = () => {
    const connectedSourceNodes = getConnectedSourceNodes();
    if (connectedSourceNodes.length === 0) {
      return { grouped: [], individual: [] };
    }
    
    // Helper function to get node outputs
    const getNodeOutputs = (node) => {
      const nodeType = node.data?.nodeType;
      const nodeId = node.id;
      
      if (nodeType === 'trigger') {
        const triggerId = node.data.config?.trigger_type || node.data.triggerType;
        if (triggerId && triggerConfigs[triggerId]?.outputs) {
          return triggerConfigs[triggerId].outputs;
        }
        return null;
      } else if (nodeType === 'agent') {
        const agentId = node.data.config?.agent_id || node.data.agentId;
        if (agentId) {
          const normalizedAgentId = agentId.replace(/-/g, '_');
          const normalizedAgentIdHyphen = agentId.replace(/_/g, '-');
          const agentConfig = agentConfigs[agentId] || agentConfigs[normalizedAgentId] || agentConfigs[normalizedAgentIdHyphen];
          if (agentConfig?.outputs) {
            return agentConfig.outputs;
          }
        }
        return null;
      } else if (nodeType === 'action') {
        const toolId = node.data.config?.tool_id || node.data.toolId;
        if (toolId) {
          const normalizedToolId = toolId.replace(/-/g, '_');
          const normalizedToolIdHyphen = toolId.replace(/_/g, '-');
          const toolConfig = toolConfigs[toolId] || toolConfigs[normalizedToolId] || toolConfigs[normalizedToolIdHyphen];
          
          // Special handling for button message nodes - generate outputs from buttons
          if (toolId === 'whatsapp_send_button_message' || normalizedToolId === 'whatsapp_send_button_message' || normalizedToolIdHyphen === 'whatsapp_send_button_message') {
            const buttons = node.data.config?.inputs?.buttons || [];
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
          
          if (toolConfig?.outputs) {
            return toolConfig.outputs;
          }
        }
        return null;
      }
      return null;
    };
    
    // Helper function to create output signature (for comparing outputs)
    const getOutputSignature = (outputs) => {
      if (!outputs) return null;
      return Object.keys(outputs).sort().join('|');
    };
    
    // Group nodes by type and output signature
    // For agent nodes, also group by agent_id to ensure nodes with same agent type are grouped
    const groups = {};
    const individualNodes = [];
    
    connectedSourceNodes.forEach(node => {
      const nodeType = node.data?.nodeType;
      const outputs = getNodeOutputs(node);
      const signature = getOutputSignature(outputs);
      
      // For agent nodes, include agent_id in the grouping key to ensure same agent types are grouped
      let groupKey;
      if (nodeType === 'agent') {
        const agentId = node.data.config?.agent_id || node.data.agentId || '';
        const normalizedAgentId = agentId.replace(/-/g, '_').replace(/_/g, '-');
        // Group by agent_id first, then by output signature
        groupKey = `${nodeType}_${normalizedAgentId}_${signature || 'no_outputs'}`;
      } else {
        // For other node types, group by type and output signature
        groupKey = `${nodeType}_${signature || 'no_outputs'}`;
      }
      
      if (!groups[groupKey]) {
        groups[groupKey] = {
          type: nodeType,
          nodes: [],
          outputs: outputs,
          signature: signature
        };
      }
      
      groups[groupKey].nodes.push(node);
    });
    
    // Special handling: Group all button message nodes together and combine their buttons
    const buttonMessageNodes = connectedSourceNodes.filter(node => {
      const toolId = node.data.config?.tool_id || node.data.toolId;
      return toolId === 'whatsapp_send_button_message' || 
             toolId?.replace(/-/g, '_') === 'whatsapp_send_button_message' ||
             toolId?.replace(/_/g, '-') === 'whatsapp-send-button-message';
    });
    
    // If we have multiple button message nodes, combine all their buttons
    if (buttonMessageNodes.length > 1) {
      const combinedButtonOutputs = {};
      const allButtonNodes = [];
      
      buttonMessageNodes.forEach(node => {
        const outputs = getNodeOutputs(node);
        if (outputs) {
          // Merge all button outputs (unique button IDs)
          Object.assign(combinedButtonOutputs, outputs);
          allButtonNodes.push(node);
        }
      });
      
      // Remove button message nodes from regular groups
      Object.keys(groups).forEach(groupKey => {
        groups[groupKey].nodes = groups[groupKey].nodes.filter(node => {
          const toolId = node.data.config?.tool_id || node.data.toolId;
          return !(toolId === 'whatsapp_send_button_message' || 
                  toolId?.replace(/-/g, '_') === 'whatsapp_send_button_message' ||
                  toolId?.replace(/_/g, '-') === 'whatsapp-send-button-message');
        });
      });
      
      // Add combined button message group if we have outputs
      if (Object.keys(combinedButtonOutputs).length > 0 && allButtonNodes.length > 1) {
        groups['action_button_message_combined'] = {
          type: 'action',
          nodes: allButtonNodes,
          outputs: combinedButtonOutputs,
          signature: Object.keys(combinedButtonOutputs).sort().join('|'),
          isButtonMessageGroup: true
        };
      } else if (allButtonNodes.length === 1) {
        // Single button message node - add to individual
        individualNodes.push({
          node: allButtonNodes[0],
          outputs: getNodeOutputs(allButtonNodes[0]),
          isCombined: false
        });
      }
    }
    
    // Convert groups to array format and separate grouped vs individual
    const grouped = [];
    Object.values(groups).forEach(group => {
      // Skip empty groups
      if (group.nodes.length === 0) return;
      
      // For agent nodes, try to get outputs from the first node if not already set
      if (group.type === 'agent' && (!group.outputs || Object.keys(group.outputs).length === 0)) {
        const firstNode = group.nodes[0];
        const agentId = firstNode.data.config?.agent_id || firstNode.data.agentId;
        if (agentId) {
          const normalizedAgentId = agentId.replace(/-/g, '_');
          const normalizedAgentIdHyphen = agentId.replace(/_/g, '-');
          const agentConfig = agentConfigs[agentId] || agentConfigs[normalizedAgentId] || agentConfigs[normalizedAgentIdHyphen];
          if (agentConfig?.outputs) {
            group.outputs = agentConfig.outputs;
            group.signature = getOutputSignature(agentConfig.outputs);
          }
        }
      }
      
      if (group.nodes.length > 1 && group.outputs && Object.keys(group.outputs).length > 0) {
        // Multiple nodes with same type and outputs - create combined variable
        grouped.push({
          ...group,
          isCombined: true,
          combinedVariablePrefix: `parents.${group.type}`
        });
      } else {
        // Single node or no outputs - show individually
        group.nodes.forEach(node => {
          individualNodes.push({
            node: node,
            outputs: getNodeOutputs(node),
            isCombined: false
          });
        });
      }
    });
    
    return { grouped, individual: individualNodes };
  };
  
  const connectedSourceNodes = getConnectedSourceNodes();
  const triggerNodes = getTriggerNodes();
  const { grouped: groupedParentNodes, individual: individualParentNodes } = getGroupedParentNodes();
  
  // Fetch OpenAI models
  const fetchOpenAIModels = async (currentConfig = null) => {
    if (!window.u43RestUrl) {
      console.warn('REST URL not available');
      return;
    }
    
    try {
      setLoadingModels(true);
      const response = await fetch(`${window.u43RestUrl}openai/models`, {
        headers: {
          'X-WP-Nonce': window.u43RestNonce || '',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setOpenaiModels(data.models || []);
        
        // Set default model if not set and models are available
        // Use setConfig callback to read current state instead of closure value
        // This prevents stale closure issues when async operation completes
        if (data.models && data.models.length > 0) {
          setConfig(prevConfig => {
            // If currentConfig was provided, use it for the check; otherwise use prevConfig
            const configToCheck = currentConfig || prevConfig;
            
            // Only set default if model is not already set
            if (!configToCheck.settings?.model) {
              const defaultModel = data.default_model || data.models[0].id;
              return {
                ...prevConfig,
                settings: {
                  ...prevConfig.settings,
                  model: defaultModel,
                  provider: prevConfig.settings?.provider || 'openai'
                }
              };
            }
            
            // Return unchanged config if model is already set
            return prevConfig;
          });
        }
      } else {
        console.warn('Failed to fetch OpenAI models:', response.status);
        // Set empty array on error
        setOpenaiModels([]);
      }
    } catch (error) {
      console.error('Error fetching OpenAI models:', error);
      setOpenaiModels([]);
    } finally {
      setLoadingModels(false);
    }
  };
  
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
            'whatsapp_send_text_message': 'whatsapp_send_text_message',
            'whatsapp_send_button_message': 'whatsapp_send_button_message',
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
      } else if (selectedNode.data.nodeType === 'agent') {
        // For agent nodes, fetch OpenAI models
        setConfig(nodeConfig);
        fetchOpenAIModels(nodeConfig);
      } else if (selectedNode.data.nodeType === 'trigger') {
        // For trigger nodes, initialize filter config if not present
        const triggerConfig = {
          ...nodeConfig,
          filter: nodeConfig.filter || {
            enabled: false,
            field: 'message_text',
            match_type: 'exact',
            value: ''
          }
        };
        setConfig(triggerConfig);
        setToolConfig(null);
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
      
      // Validate action nodes have required fields
      if (selectedNode?.data?.nodeType === 'action' && toolConfig && toolConfig.inputs) {
        const missingRequiredFields = [];
        const inputs = config.inputs || {};
        
        Object.entries(toolConfig.inputs).forEach(([inputKey, inputConfig]) => {
          // Skip hidden fields (message_type, template fields)
          if (inputKey === 'message_type' || inputKey === 'template_name' || inputKey === 'template_language') {
            return;
          }
          
          if (inputConfig.required) {
            const inputValue = inputs[inputKey] ?? inputConfig.default ?? '';
            
            // Check if field is empty
            let isEmpty = false;
            
            if (inputConfig.type === 'array') {
              isEmpty = !Array.isArray(inputValue) || inputValue.length === 0;
            } else if (typeof inputValue === 'string') {
              isEmpty = inputValue.trim() === '';
            } else {
              isEmpty = !inputValue || inputValue === null || inputValue === undefined;
            }
            
            if (isEmpty) {
              missingRequiredFields.push(inputConfig.label || inputKey);
            }
          }
        });
        
        if (missingRequiredFields.length > 0) {
          alert(`Please fill in all required fields:\n\n${missingRequiredFields.join('\n')}\n\nThe node cannot be saved without these fields.`);
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
          <div className="space-y-4">
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
            
            {/* Filter configuration for WhatsApp trigger */}
            {(config.trigger_type === 'whatsapp_message_received' || config.trigger_type === 'whatsapp-message-received') && (
              <div className="border-t pt-4 space-y-4">
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="filter-enabled"
                    checked={config.filter?.enabled || false}
                    onChange={(e) => {
                      setConfig({
                        ...config,
                        filter: {
                          ...config.filter,
                          enabled: e.target.checked,
                          field: config.filter?.field || 'message_text',
                          match_type: config.filter?.match_type || 'exact',
                          value: config.filter?.value || ''
                        }
                      });
                    }}
                    className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <label htmlFor="filter-enabled" className="text-sm font-medium text-gray-700">
                    Enable Message Filtering
                  </label>
                </div>
                <p className="text-xs text-gray-500 -mt-2 ml-6">
                  Filter incoming messages based on field content. If disabled, all messages will trigger this workflow.
                </p>
                
                {config.filter?.enabled && (
                  <div className="ml-6 space-y-3 bg-gray-50 p-3 rounded-md">
                    {/* Field selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Field to Compare
                      </label>
                      <select
                        value={config.filter?.field || 'message_text'}
                        onChange={(e) => {
                          setConfig({
                            ...config,
                            filter: {
                              ...config.filter,
                              field: e.target.value
                            }
                          });
                        }}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {(triggerConfigs['whatsapp_message_received'] || triggerConfigs['whatsapp-message-received'])?.outputs && Object.entries((triggerConfigs['whatsapp_message_received'] || triggerConfigs['whatsapp-message-received']).outputs).map(([key, output]) => (
                          <option key={key} value={key}>
                            {output.label || key}
                          </option>
                        ))}
                      </select>
                      <p className="text-xs text-gray-500 mt-1">
                        Select which field to compare against
                      </p>
                    </div>
                    
                    {/* Match type */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Match Type
                      </label>
                      <select
                        value={config.filter?.match_type || 'exact'}
                        onChange={(e) => {
                          setConfig({
                            ...config,
                            filter: {
                              ...config.filter,
                              match_type: e.target.value
                            }
                          });
                        }}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="exact">Exact Match</option>
                        <option value="contains">Contains Substring</option>
                      </select>
                      <p className="text-xs text-gray-500 mt-1">
                        Choose how to match the value
                      </p>
                    </div>
                    
                    {/* Value to match */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Value to Match
                      </label>
                      <input
                        type="text"
                        value={config.filter?.value || ''}
                        onChange={(e) => {
                          setConfig({
                            ...config,
                            filter: {
                              ...config.filter,
                              value: e.target.value
                            }
                          });
                        }}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter value to match"
                      />
                      <p className="text-xs text-gray-500 mt-1">
                        Enter the value to match against the selected field
                      </p>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        )}
        
        {selectedNode?.data?.nodeType === 'agent' && (
          <div className="space-y-4">
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
                  value={config.settings?.model || (openaiModels[0]?.id || 'gpt-5')}
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
                    {config.settings?.model || 'gpt-5'} (API key not configured or GPT-5 models unavailable)
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
              {(groupedParentNodes.length > 0 || individualParentNodes.length > 0) && (
                <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
                  <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                    <span>✓</span> Connected Nodes - Click to insert variable:
                  </p>
                  <div className="space-y-2 max-h-64 overflow-y-auto overflow-x-hidden pr-1">
                    {/* Show combined variables for grouped nodes */}
                    {groupedParentNodes.map((group, groupIdx) => {
                      if (!group.outputs) return null;
                      
                      const nodeTypeLabel = group.type.charAt(0).toUpperCase() + group.type.slice(1);
                      const nodeLabels = group.nodes.map(n => n.data.label || n.data.config?.title || 'Untitled Node').join(', ');
                      
                      return (
                        <div key={`group_${groupIdx}`} className="bg-white rounded p-2 border border-blue-200 max-w-full overflow-hidden">
                          <div className="flex items-center gap-2 mb-1 min-w-0">
                            <span className="text-xs font-semibold text-blue-900 truncate">
                              {nodeTypeLabel} Nodes ({group.nodes.length})
                            </span>
                            <span className="text-xs text-gray-500 flex-shrink-0">Combined</span>
                          </div>
                          <div className="text-xs text-gray-600 mb-2 truncate" title={nodeLabels}>
                            {nodeLabels}
                          </div>
                          <div className="space-y-1">
                            {Object.entries(group.outputs).map(([key, output]) => {
                              const combinedValue = `{{parents.${group.type}.${key}}}`;
                              return (
                                <button
                                  key={key}
                                  type="button"
                                  onClick={() => {
                                    const textarea = promptTextareaRef.current;
                                    const currentPrompt = config.prompt || '';
                                    const cursorPos = textarea ? textarea.selectionStart : currentPrompt.length;
                                    const newPrompt = currentPrompt.slice(0, cursorPos) + combinedValue + currentPrompt.slice(cursorPos);
                                    setConfig({ ...config, prompt: newPrompt });
                                    setTimeout(() => {
                                      if (textarea) {
                                        textarea.focus();
                                        const newPos = cursorPos + combinedValue.length;
                                        textarea.setSelectionRange(newPos, newPos);
                                      }
                                    }, 0);
                                  }}
                                  className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                                  title={`Combined output from ${group.nodes.length} ${group.type} nodes. Type: ${output.type || 'string'}`}
                                >
                                  <div className="flex items-center gap-2 min-w-0">
                                    <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                                      {output.label || key}
                                    </span>
                                    <span 
                                      className="text-xs text-gray-500 font-mono truncate"
                                      title={combinedValue}
                                    >
                                      {combinedValue}
                                    </span>
                                  </div>
                                  <div className="text-xs text-gray-500 mt-0.5 truncate">
                                    Combined from {group.nodes.length} nodes • Type: {output.type || 'string'}
                                  </div>
                                </button>
                              );
                            })}
                          </div>
                        </div>
                      );
                    })}
                    
                    {/* Show individual nodes */}
                    {individualParentNodes.map((item) => {
                      const sourceNode = item.node;
                      const nodeId = sourceNode.id;
                      const nodeType = sourceNode.data.nodeType;
                      const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
                      let suggestions = [];
                      
                      if (nodeType === 'agent' && item.outputs) {
                        suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                          label: output.label || key,
                          description: `Type: ${output.type || 'string'}`,
                          value: `{{${nodeId}.${key}}}`,
                          displayValue: `{{node.${key}}}`
                        }));
                      } else if (nodeType === 'trigger' && item.outputs) {
                        suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                          label: output.label || key,
                          description: `Type: ${output.type || 'string'}`,
                          value: `{{trigger_data.${key}}}`
                        }));
                      } else if (nodeType === 'action' && item.outputs) {
                        suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                          label: output.label || key,
                          description: `Type: ${output.type || 'string'}`,
                          value: `{{${nodeId}.${key}}}`,
                          displayValue: `{{node.${key}}}`
                        }));
                      } else {
                        suggestions = [
                          { 
                            label: 'Output', 
                            description: 'The output from this node',
                            value: `{{${nodeId}}}`,
                            displayValue: `{{node}}`
                          },
                        ];
                      }
                      
                      return (
                        <div key={nodeId} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
                          <div className="flex items-center gap-2 mb-1 min-w-0">
                            <span className="text-xs font-semibold text-green-900 truncate">{nodeLabel}</span>
                            <span className="text-xs text-gray-500 flex-shrink-0">({nodeType})</span>
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
                                  setTimeout(() => {
                                    if (textarea) {
                                      textarea.focus();
                                      const newPos = cursorPos + suggestion.value.length;
                                      textarea.setSelectionRange(newPos, newPos);
                                    }
                                  }, 0);
                                }}
                                className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                                title={suggestion.description}
                              >
                                <div className="flex items-center gap-2 min-w-0">
                                  <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                                    {suggestion.label}
                                  </span>
                                  <span 
                                    className="text-xs text-gray-500 font-mono truncate"
                                    title={suggestion.value}
                                  >
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
              {(groupedParentNodes.length > 0 || individualParentNodes.length > 0) && (
                <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
                  <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                    <span>✓</span> Connected Nodes - Click to use:
                  </p>
                  <div className="space-y-2 max-h-64 overflow-y-auto overflow-x-hidden pr-1">
                    {/* Show combined variables for grouped nodes */}
                    {groupedParentNodes.map((group, groupIdx) => {
                      if (!group.outputs) return null;
                      
                      const nodeTypeLabel = group.type.charAt(0).toUpperCase() + group.type.slice(1);
                      const nodeLabels = group.nodes.map(n => n.data.label || n.data.config?.title || 'Untitled Node').join(', ');
                      
                      return (
                        <div key={`group_${groupIdx}`} className="bg-white rounded p-2 border border-blue-200 max-w-full overflow-hidden">
                          <div className="flex items-center gap-2 mb-1 min-w-0">
                            <span className="text-xs font-semibold text-blue-900 truncate">
                              {nodeTypeLabel} Nodes ({group.nodes.length})
                            </span>
                            <span className="text-xs text-gray-500 flex-shrink-0">Combined</span>
                          </div>
                          <div className="text-xs text-gray-600 mb-2 truncate" title={nodeLabels}>
                            {nodeLabels}
                          </div>
                          <div className="space-y-1">
                            {Object.entries(group.outputs).map(([key, output]) => {
                              const combinedValue = `{{parents.${group.type}.${key}}}`;
                              return (
                                <button
                                  key={key}
                                  type="button"
                                  onClick={() => setConfig({ ...config, field: combinedValue })}
                                  className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                                  title={`Combined output from ${group.nodes.length} ${group.type} nodes. Type: ${output.type || 'string'}`}
                                >
                                  <div className="flex items-center gap-2 min-w-0">
                                    <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                                      {output.label || key}
                                    </span>
                                    <span className="text-xs text-gray-500 font-mono truncate" title={combinedValue}>
                                      {combinedValue}
                                    </span>
                                  </div>
                                  <div className="text-xs text-gray-500 mt-0.5 truncate">
                                    Combined from {group.nodes.length} nodes • Type: {output.type || 'string'}
                                  </div>
                                </button>
                              );
                            })}
                          </div>
                        </div>
                      );
                    })}
                    
                    {/* Show individual nodes */}
                    {individualParentNodes.map((item) => {
                      const sourceNode = item.node;
                      const nodeId = sourceNode.id;
                      const nodeType = sourceNode.data.nodeType;
                      const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
                      let suggestions = [];
                      
                      if (nodeType === 'agent' && item.outputs) {
                        suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                          label: output.label || key,
                          description: `Type: ${output.type || 'string'}`,
                          value: `{{${nodeId}.${key}}}`
                        }));
                      } else if (nodeType === 'trigger' && item.outputs) {
                        suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                          label: output.label || key,
                          description: `Type: ${output.type || 'string'}`,
                          value: `{{trigger_data.${key}}}`
                        }));
                      } else if (nodeType === 'action' && item.outputs) {
                        suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                          label: output.label || key,
                          description: `Type: ${output.type || 'string'}`,
                          value: `{{${nodeId}.${key}}}`
                        }));
                      } else {
                        suggestions = [
                          { 
                            label: 'Output', 
                            description: 'The output from this node',
                            value: `{{${nodeId}}}` 
                          },
                        ];
                      }
                      
                      return (
                        <div key={nodeId} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
                          <div className="flex items-center gap-2 mb-1 min-w-0">
                            <span className="text-xs font-semibold text-green-900 truncate">{nodeLabel}</span>
                            <span className="text-xs text-gray-500 flex-shrink-0">({nodeType})</span>
                          </div>
                          <div className="space-y-1">
                            {suggestions.map((suggestion, idx) => (
                              <button
                                key={idx}
                                type="button"
                                onClick={() => setConfig({ ...config, field: suggestion.value })}
                                className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                                title={suggestion.description}
                              >
                                <div className="flex items-center gap-2 min-w-0">
                                  <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                                    {suggestion.label}
                                  </span>
                                  <span className="text-xs text-gray-500 truncate">
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
                placeholder="e.g., {'{{'}agent_node_id.response{'}}'} or {'{{'}trigger_data.comment_id{'}}'}"
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
        )}
        
        {selectedNode?.data?.nodeType === 'action' && (
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
                {(() => {
                  return Object.entries(toolConfig.inputs).map(([inputKey, inputConfig]) => {
                    const inputValue = config.inputs?.[inputKey] ?? inputConfig.default ?? '';
                    
                    // Skip message_type, template_name, and template_language fields
                    if (inputKey === 'message_type' || inputKey === 'template_name' || inputKey === 'template_language') {
                      return null;
                    }
                    
                    // Special handling for buttons array - show button configuration UI
                    if (inputKey === 'buttons' && toolConfig.id === 'whatsapp_send_button_message') {
                      const buttons = Array.isArray(inputValue) ? inputValue : [];
                      const buttonColors = ['#3B82F6', '#10B981', '#F59E0B']; // Blue, Green, Orange
                      
                      return (
                        <div key={inputKey}>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            {inputConfig.label || inputKey}
                            {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                            <span className="text-xs text-gray-500 ml-2">(Max 3 buttons)</span>
                          </label>
                          
                          <div className="space-y-2 mb-2">
                            {buttons.map((button, index) => (
                              <div key={index} className="flex items-center gap-2 p-2 border border-gray-300 rounded-md bg-gray-50">
                                <div 
                                  className="w-4 h-4 rounded border-2 border-gray-300"
                                  style={{ backgroundColor: button.color || buttonColors[index] || '#3B82F6' }}
                                  title={`Button ${index + 1} color`}
                                />
                                <div className="flex-1">
                                  <input
                                    type="text"
                                    value={button.id || ''}
                                    onChange={(e) => {
                                      const newButtons = [...buttons];
                                      newButtons[index] = { ...newButtons[index], id: e.target.value };
                                      setConfig({
                                        ...config,
                                        inputs: {
                                          ...(config.inputs || {}),
                                          [inputKey]: newButtons,
                                        },
                                      });
                                    }}
                                    placeholder="Button ID (e.g., btn1)"
                                    className="w-full px-2 py-1 text-xs border border-gray-300 rounded mb-1"
                                  />
                                  <input
                                    type="text"
                                    value={button.title || ''}
                                    onChange={(e) => {
                                      const newButtons = [...buttons];
                                      newButtons[index] = { ...newButtons[index], title: e.target.value };
                                      setConfig({
                                        ...config,
                                        inputs: {
                                          ...(config.inputs || {}),
                                          [inputKey]: newButtons,
                                        },
                                      });
                                    }}
                                    placeholder="Button Title"
                                    className="w-full px-2 py-1 text-xs border border-gray-300 rounded"
                                  />
                                </div>
                                <button
                                  type="button"
                                  onClick={() => {
                                    const newButtons = buttons.filter((_, i) => i !== index);
                                    setConfig({
                                      ...config,
                                      inputs: {
                                        ...(config.inputs || {}),
                                        [inputKey]: newButtons,
                                      },
                                    });
                                  }}
                                  className="text-red-500 hover:text-red-700 text-sm"
                                  title="Remove button"
                                >
                                  ✕
                                </button>
                              </div>
                            ))}
                          </div>
                          
                          {buttons.length < 3 && (
                            <button
                              type="button"
                              onClick={() => {
                                const newButtons = [...buttons, { id: `btn${buttons.length + 1}`, title: '', type: 'reply', color: buttonColors[buttons.length] || '#3B82F6' }];
                                setConfig({
                                  ...config,
                                  inputs: {
                                    ...(config.inputs || {}),
                                    [inputKey]: newButtons,
                                  },
                                });
                              }}
                              className="text-sm text-blue-600 hover:text-blue-800 mb-2"
                            >
                              + Add Button
                            </button>
                          )}
                          
                          {inputConfig.description && (
                            <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>
                          )}
                        </div>
                      );
                    }
                    
                    // Special handling for phone_number_variable - show variable selection UI
                    if (inputKey === 'phone_number_variable') {
                      // Use grouped parent nodes for variable suggestions
                      const allTriggerNodes = triggerNodes.filter(node => 
                        !connectedSourceNodes.some(connected => connected.id === node.id)
                      );
                      
                      return (
                        <div key={inputKey}>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            {inputConfig.label || inputKey}
                            {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                          </label>
                          
                          {/* Show variable suggestions */}
                          {(groupedParentNodes.length > 0 || individualParentNodes.length > 0 || allTriggerNodes.length > 0) && (
                            <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
                              <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                                <span>✓</span> Available Variables - Click to insert:
                              </p>
                              <div className="space-y-2 max-h-64 overflow-y-auto overflow-x-hidden pr-1">
                                {/* Show combined variables for grouped nodes */}
                                {groupedParentNodes.map((group, groupIdx) => {
                                  if (!group.outputs) return null;
                                  
                                  const nodeTypeLabel = group.type.charAt(0).toUpperCase() + group.type.slice(1);
                                  const nodeLabels = group.nodes.map(n => n.data.label || n.data.config?.title || 'Untitled Node').join(', ');
                                  
                                  return (
                                    <div key={`group_${groupIdx}`} className="bg-white rounded p-2 border border-blue-200 max-w-full overflow-hidden">
                                      <div className="flex items-center gap-2 mb-1 min-w-0">
                                        <span className="text-xs font-semibold text-blue-900 truncate">
                                          {nodeTypeLabel} Nodes ({group.nodes.length})
                                        </span>
                                        <span className="text-xs text-gray-500 flex-shrink-0">Combined</span>
                                      </div>
                                      <div className="text-xs text-gray-600 mb-2 truncate" title={nodeLabels}>
                                        {nodeLabels}
                                      </div>
                                      <div className="space-y-1">
                                        {Object.entries(group.outputs).map(([key, output]) => {
                                          const combinedValue = `{{parents.${group.type}.${key}}}`;
                                          return (
                                            <button
                                              key={key}
                                              type="button"
                                              onMouseDown={(e) => e.preventDefault()}
                                              onClick={() => {
                                                const inputField = document.querySelector(`input[data-input-key="${inputKey}"]`);
                                                if (inputField) {
                                                  const start = inputField.selectionStart || 0;
                                                  const end = inputField.selectionEnd || 0;
                                                  const currentValue = inputField.value || '';
                                                  const newValue = currentValue.slice(0, start) + combinedValue + currentValue.slice(end);
                                                  
                                                  setConfig({
                                                    ...config,
                                                    inputs: {
                                                      ...(config.inputs || {}),
                                                      [inputKey]: newValue,
                                                    },
                                                  });
                                                  
                                                  setTimeout(() => {
                                                    inputField.focus();
                                                    const newPos = start + combinedValue.length;
                                                    inputField.setSelectionRange(newPos, newPos);
                                                  }, 0);
                                                } else {
                                                  setConfig({
                                                    ...config,
                                                    inputs: {
                                                      ...(config.inputs || {}),
                                                      [inputKey]: combinedValue,
                                                    },
                                                  });
                                                }
                                              }}
                                              className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                                              title={`Combined output from ${group.nodes.length} ${group.type} nodes. Type: ${output.type || 'string'}`}
                                            >
                                              <div className="flex items-center gap-2 min-w-0">
                                                <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                                                  {output.label || key}
                                                </span>
                                                <span className="text-xs text-gray-500 font-mono truncate" title={combinedValue}>
                                                  {combinedValue}
                                                </span>
                                              </div>
                                              <div className="text-xs text-gray-500 mt-0.5 truncate">
                                                Combined from {group.nodes.length} nodes • Type: {output.type || 'string'}
                                              </div>
                                            </button>
                                          );
                                        })}
                                      </div>
                                    </div>
                                  );
                                })}
                                
                                {/* Show individual nodes */}
                                {individualParentNodes.map((item) => {
                                  const sourceNode = item.node;
                                  const nodeId = sourceNode.id;
                                  const nodeType = sourceNode.data.nodeType;
                                  const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
                                  let suggestions = [];
                                  
                                  if (nodeType === 'trigger' && item.outputs) {
                                    suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                      label: output.label || key,
                                      description: `Type: ${output.type || 'string'}`,
                                      value: `{{trigger_data.${key}}}`
                                    }));
                                  } else if (nodeType === 'action' && item.outputs) {
                                    suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                      label: output.label || key,
                                      description: `Type: ${output.type || 'string'}`,
                                      value: `{{${nodeId}.${key}}}`
                                    }));
                                  } else if (nodeType === 'agent' && item.outputs) {
                                    suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                      label: output.label || key,
                                      description: `Type: ${output.type || 'string'}`,
                                      value: `{{${nodeId}.${key}}}`
                                    }));
                                  }
                                  
                                  if (suggestions.length === 0) return null;
                                  
                                  return (
                                    <div key={nodeId} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
                                      <div className="flex items-center gap-2 mb-1 min-w-0">
                                        <span className="text-xs font-semibold text-green-900 truncate">{nodeLabel}</span>
                                        <span className="text-xs text-gray-500 flex-shrink-0">({nodeType})</span>
                                      </div>
                                      <div className="space-y-1">
                                        {suggestions.map((suggestion, idx) => (
                                          <button
                                            key={idx}
                                            type="button"
                                            onMouseDown={(e) => e.preventDefault()}
                                            onClick={() => {
                                              const inputField = document.querySelector(`input[data-input-key="${inputKey}"]`);
                                              if (inputField) {
                                                const start = inputField.selectionStart || 0;
                                                const end = inputField.selectionEnd || 0;
                                                const currentValue = inputField.value || '';
                                                const newValue = currentValue.slice(0, start) + suggestion.value + currentValue.slice(end);
                                                
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: newValue,
                                                  },
                                                });
                                                
                                                setTimeout(() => {
                                                  inputField.focus();
                                                  const newPos = start + suggestion.value.length;
                                                  inputField.setSelectionRange(newPos, newPos);
                                                }, 0);
                                              } else {
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: suggestion.value,
                                                  },
                                                });
                                              }
                                            }}
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
                                
                                {/* Show trigger nodes that aren't connected */}
                                {allTriggerNodes.map((sourceNode) => {
                                  const triggerId = sourceNode.data.config?.trigger_type || sourceNode.data.triggerType;
                                  if (!triggerId || !triggerConfigs[triggerId]?.outputs) return null;
                                  
                                  const suggestions = Object.entries(triggerConfigs[triggerId].outputs).map(([key, output]) => ({
                                    label: output.label || key,
                                    description: `Type: ${output.type || 'string'}`,
                                    value: `{{trigger_data.${key}}}`
                                  }));
                                  
                                  return (
                                    <div key={sourceNode.id} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
                                      <div className="flex items-center gap-2 mb-1 min-w-0">
                                        <span className="text-xs font-semibold text-green-900 truncate">
                                          {sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node'}
                                        </span>
                                        <span className="text-xs text-gray-500 flex-shrink-0">(trigger)</span>
                                      </div>
                                      <div className="space-y-1">
                                        {suggestions.map((suggestion, idx) => (
                                          <button
                                            key={idx}
                                            type="button"
                                            onMouseDown={(e) => e.preventDefault()}
                                            onClick={() => {
                                              const inputField = document.querySelector(`input[data-input-key="${inputKey}"]`);
                                              if (inputField) {
                                                const start = inputField.selectionStart || 0;
                                                const end = inputField.selectionEnd || 0;
                                                const currentValue = inputField.value || '';
                                                const newValue = currentValue.slice(0, start) + suggestion.value + currentValue.slice(end);
                                                
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: newValue,
                                                  },
                                                });
                                                
                                                setTimeout(() => {
                                                  inputField.focus();
                                                  const newPos = start + suggestion.value.length;
                                                  inputField.setSelectionRange(newPos, newPos);
                                                }, 0);
                                              } else {
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: suggestion.value,
                                                  },
                                                });
                                              }
                                            }}
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
                          )}
                          
                          <input
                            type="text"
                            data-input-key={inputKey}
                            value={inputValue}
                            onChange={(e) => setConfig({
                              ...config,
                              inputs: {
                                ...(config.inputs || {}),
                                [inputKey]: e.target.value,
                              },
                            })}
                            placeholder="e.g., {{trigger_data.phone}} or {{node_id.phone_number}}"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                          />
                          {inputConfig.description && (
                            <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>
                          )}
                        </div>
                      );
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
                  
                  // Special handling for message field - make it a larger textarea with variable suggestions
                  if (inputKey === 'message' || inputKey === 'body_text') {
                    // Use grouped parent nodes for variable suggestions
                    const allTriggerNodes = triggerNodes.filter(node => 
                      !connectedSourceNodes.some(connected => connected.id === node.id)
                    );
                    
                    return (
                      <div key={inputKey}>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          {inputConfig.label || inputKey}
                          {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                        </label>
                        
                        {/* Show variable suggestions */}
                        {(groupedParentNodes.length > 0 || individualParentNodes.length > 0 || allTriggerNodes.length > 0) && (
                          <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
                            <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                              <span>✓</span> Available Variables - Click to insert:
                            </p>
                            <div className="space-y-2 max-h-64 overflow-y-auto overflow-x-hidden pr-1">
                              {/* Show combined variables for grouped nodes */}
                              {groupedParentNodes.map((group, groupIdx) => {
                                if (!group.outputs) return null;
                                
                                const nodeTypeLabel = group.type.charAt(0).toUpperCase() + group.type.slice(1);
                                const nodeLabels = group.nodes.map(n => n.data.label || n.data.config?.title || 'Untitled Node').join(', ');
                                
                                return (
                                  <div key={`group_${groupIdx}`} className="bg-white rounded p-2 border border-blue-200 max-w-full overflow-hidden">
                                    <div className="flex items-center gap-2 mb-1 min-w-0">
                                      <span className="text-xs font-semibold text-blue-900 truncate">
                                        {nodeTypeLabel} Nodes ({group.nodes.length})
                                      </span>
                                      <span className="text-xs text-gray-500 flex-shrink-0">Combined</span>
                                    </div>
                                    <div className="text-xs text-gray-600 mb-2 truncate" title={nodeLabels}>
                                      {nodeLabels}
                                    </div>
                                    <div className="space-y-1">
                                      {Object.entries(group.outputs).map(([key, output]) => {
                                        const combinedValue = `{{parents.${group.type}.${key}}}`;
                                        return (
                                          <button
                                            key={key}
                                            type="button"
                                            onMouseDown={(e) => e.preventDefault()}
                                            onClick={() => {
                                              const textarea = lastFocusedTextareaRef.current;
                                              const storedInputKey = lastFocusedTextareaInputKeyRef.current;
                                              
                                              if (textarea && storedInputKey === inputKey) {
                                                const start = textarea.selectionStart || 0;
                                                const end = textarea.selectionEnd || 0;
                                                const currentValue = textarea.value || '';
                                                const newValue = currentValue.slice(0, start) + combinedValue + currentValue.slice(end);
                                                
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: newValue,
                                                  },
                                                });
                                                
                                                setTimeout(() => {
                                                  if (textarea) {
                                                    textarea.focus();
                                                    const newPos = start + combinedValue.length;
                                                    textarea.setSelectionRange(newPos, newPos);
                                                  }
                                                }, 0);
                                              }
                                            }}
                                            className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group min-w-0"
                                            title={`Combined output from ${group.nodes.length} ${group.type} nodes. Type: ${output.type || 'string'}`}
                                          >
                                            <div className="flex items-center gap-2 min-w-0">
                                              <span className="text-xs font-medium text-blue-700 group-hover:text-blue-900 truncate">
                                                {output.label || key}
                                              </span>
                                              <span className="text-xs text-gray-500 font-mono truncate" title={combinedValue}>
                                                {combinedValue}
                                              </span>
                                            </div>
                                            <div className="text-xs text-gray-500 mt-0.5 truncate">
                                              Combined from {group.nodes.length} nodes • Type: {output.type || 'string'}
                                            </div>
                                          </button>
                                        );
                                      })}
                                    </div>
                                  </div>
                                );
                              })}
                              
                              {/* Show individual nodes */}
                              {individualParentNodes.map((item) => {
                                const sourceNode = item.node;
                                const nodeId = sourceNode.id;
                                const nodeType = sourceNode.data.nodeType;
                                const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
                                let suggestions = [];
                                
                                if (nodeType === 'trigger' && item.outputs) {
                                  suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                    label: output.label || key,
                                    description: `Type: ${output.type || 'string'}`,
                                    value: `{{trigger_data.${key}}}`
                                  }));
                                } else if (nodeType === 'action' && item.outputs) {
                                  suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                    label: output.label || key,
                                    description: `Type: ${output.type || 'string'}`,
                                    value: `{{${nodeId}.${key}}}`
                                  }));
                                } else if (nodeType === 'agent' && item.outputs) {
                                  suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                    label: output.label || key,
                                    description: `Type: ${output.type || 'string'}`,
                                    value: `{{${nodeId}.${key}}}`
                                  }));
                                }
                                
                                if (suggestions.length === 0) return null;
                                
                                return (
                                  <div key={nodeId} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
                                    <div className="flex items-center gap-2 mb-1 min-w-0">
                                      <span className="text-xs font-semibold text-green-900 truncate">{nodeLabel}</span>
                                      <span className="text-xs text-gray-500 flex-shrink-0">({nodeType})</span>
                                    </div>
                                    <div className="space-y-1">
                                      {suggestions.map((suggestion, idx) => (
                                        <button
                                          key={idx}
                                          type="button"
                                          onMouseDown={(e) => e.preventDefault()}
                                          onClick={() => {
                                            const textarea = lastFocusedTextareaRef.current;
                                            const storedInputKey = lastFocusedTextareaInputKeyRef.current;
                                            
                                            if (textarea && storedInputKey === inputKey) {
                                              const start = textarea.selectionStart || 0;
                                              const end = textarea.selectionEnd || 0;
                                              const currentValue = textarea.value || '';
                                              const newValue = currentValue.slice(0, start) + suggestion.value + currentValue.slice(end);
                                              
                                              setConfig({
                                                ...config,
                                                inputs: {
                                                  ...(config.inputs || {}),
                                                  [inputKey]: newValue,
                                                },
                                              });
                                              
                                              setTimeout(() => {
                                                if (textarea) {
                                                  textarea.focus();
                                                  const newPos = start + suggestion.value.length;
                                                  textarea.setSelectionRange(newPos, newPos);
                                                }
                                              }, 0);
                                            }
                                          }}
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
                              
                              {/* Show trigger nodes that aren't connected */}
                              {allTriggerNodes.map((sourceNode) => {
                                const triggerId = sourceNode.data.config?.trigger_type || sourceNode.data.triggerType;
                                if (!triggerId || !triggerConfigs[triggerId]?.outputs) return null;
                                
                                const suggestions = Object.entries(triggerConfigs[triggerId].outputs).map(([key, output]) => ({
                                  label: output.label || key,
                                  description: `Type: ${output.type || 'string'}`,
                                  value: `{{trigger_data.${key}}}`
                                }));
                                
                                return (
                                  <div key={sourceNode.id} className="bg-white rounded p-2 border border-green-100 max-w-full overflow-hidden">
                                    <div className="flex items-center gap-2 mb-1 min-w-0">
                                      <span className="text-xs font-semibold text-green-900 truncate">
                                        {sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node'}
                                      </span>
                                      <span className="text-xs text-gray-500 flex-shrink-0">(trigger)</span>
                                    </div>
                                    <div className="space-y-1">
                                      {suggestions.map((suggestion, idx) => (
                                        <button
                                          key={idx}
                                          type="button"
                                          onMouseDown={(e) => e.preventDefault()}
                                          onClick={() => {
                                            const textarea = lastFocusedTextareaRef.current;
                                            const storedInputKey = lastFocusedTextareaInputKeyRef.current;
                                            
                                            if (textarea && storedInputKey === inputKey) {
                                              const start = textarea.selectionStart || 0;
                                              const end = textarea.selectionEnd || 0;
                                              const currentValue = textarea.value || '';
                                              const newValue = currentValue.slice(0, start) + suggestion.value + currentValue.slice(end);
                                              
                                              setConfig({
                                                ...config,
                                                inputs: {
                                                  ...(config.inputs || {}),
                                                  [inputKey]: newValue,
                                                },
                                              });
                                              
                                              setTimeout(() => {
                                                if (textarea) {
                                                  textarea.focus();
                                                  const newPos = start + suggestion.value.length;
                                                  textarea.setSelectionRange(newPos, newPos);
                                                }
                                              }, 0);
                                            }
                                          }}
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
                        )}
                        
                        <textarea
                          ref={(el) => {
                            if (el) {
                              lastFocusedTextareaRef.current = el;
                              lastFocusedTextareaInputKeyRef.current = inputKey;
                            }
                          }}
                          onFocus={(e) => {
                            lastFocusedTextareaRef.current = e.target;
                            lastFocusedTextareaInputKeyRef.current = inputKey;
                          }}
                          value={inputValue}
                          onChange={(e) => setConfig({
                            ...config,
                            inputs: {
                              ...(config.inputs || {}),
                              [inputKey]: e.target.value,
                            },
                          })}
                          placeholder={inputConfig.description || `Enter ${inputKey} (use {{variables}} from previous nodes)`}
                          rows={6}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        />
                        {inputConfig.description && (
                          <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>
                        )}
                      </div>
                    );
                  }
                  
                  // Handle object type inputs (for HTTP tools: url_params, query_params, headers, body, output_schema)
                  if (inputConfig.type === 'object') {
                    // Special handling for HTTP tool key-value pairs (url_params, query_params, headers, body)
                    if (inputKey === 'url_params' || inputKey === 'query_params' || inputKey === 'headers' || inputKey === 'body') {
                      const objectValue = typeof inputValue === 'object' && inputValue !== null ? inputValue : {};
                      // Convert object to array of {key, value, id} pairs
                      // Use a stable ID system: check if there's a __pairs_meta__ object that stores IDs
                      const pairsMeta = objectValue.__pairs_meta__ || {};
                      const keyValuePairs = [];
                      
                      Object.entries(objectValue).forEach(([key, value]) => {
                        // Skip the meta object
                        if (key === '__pairs_meta__') return;
                        
                        // Get or create stable ID for this pair
                        let pairId = pairsMeta[key];
                        if (!pairId) {
                          // Generate a new stable ID for this key
                          pairId = `pair_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
                        }
                        
                        keyValuePairs.push({
                          id: pairId,
                          key: key.startsWith('__empty_') ? '' : key,
                          value: typeof value === 'string' ? value : JSON.stringify(value),
                          originalKey: key // Store original key to track it
                        });
                      });
                      
                      // Use grouped parent nodes for variable suggestions
                      const allTriggerNodes = triggerNodes.filter(node => 
                        !connectedSourceNodes.some(connected => connected.id === node.id)
                      );
                      
                      return (
                        <div key={inputKey}>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            {inputConfig.label || inputKey}
                            {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                          </label>
                          
                          {/* Show variable suggestions */}
                          {(groupedParentNodes.length > 0 || individualParentNodes.length > 0 || allTriggerNodes.length > 0) && (
                            <div className="mb-3 p-3 bg-green-50 border border-green-200 rounded-md max-w-full overflow-hidden">
                              <p className="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                                <span>✓</span> Available Variables - Click to insert:
                              </p>
                              <div className="space-y-2 max-h-64 overflow-y-auto overflow-x-hidden pr-1">
                                {/* Show combined variables for grouped nodes */}
                                {groupedParentNodes.map((group, groupIdx) => {
                                  if (!group.outputs) return null;
                                  
                                  const nodeTypeLabel = group.type.charAt(0).toUpperCase() + group.type.slice(1);
                                  const nodeLabels = group.nodes.map(n => n.data.label || n.data.config?.title || 'Untitled Node').join(', ');
                                  
                                  return (
                                    <div key={`group_${groupIdx}`} className="bg-white rounded p-2 border border-blue-200 max-w-full overflow-hidden">
                                      <div className="flex items-center gap-2 mb-1 min-w-0">
                                        <span className="text-xs font-semibold text-blue-900 truncate">
                                          {nodeTypeLabel} Nodes ({group.nodes.length})
                                        </span>
                                        <span className="text-xs text-gray-500 flex-shrink-0">Combined</span>
                                      </div>
                                      <div className="text-xs text-gray-600 mb-2 truncate" title={nodeLabels}>
                                        {nodeLabels}
                                      </div>
                                      <div className="text-gray-600 mt-1 space-y-0.5">
                                        {Object.entries(group.outputs).map(([key, output]) => {
                                          const combinedValue = `{{parents.${group.type}.${key}}}`;
                                          return (
                                            <button
                                              key={key}
                                              type="button"
                                              onMouseDown={(e) => e.preventDefault()}
                                              onClick={() => {
                                                const targetInput = lastFocusedInputRef.current;
                                                const inputInfo = lastFocusedInputInfoRef.current;
                                                
                                                if (targetInput && inputInfo.index !== null && inputInfo.inputKey === inputKey) {
                                                  const start = targetInput.selectionStart || 0;
                                                  const end = targetInput.selectionEnd || 0;
                                                  const currentValue = targetInput.value || '';
                                                  const newValue = currentValue.slice(0, start) + combinedValue + currentValue.slice(end);
                                                  
                                                  const newPairs = [...keyValuePairs];
                                                  const oldPair = newPairs[inputInfo.index];
                                                  
                                                  if (inputInfo.isKey) {
                                                    newPairs[inputInfo.index] = { ...oldPair, key: newValue };
                                                  } else {
                                                    newPairs[inputInfo.index] = { ...oldPair, value: newValue };
                                                  }
                                                  
                                                  const newObject = {};
                                                  const pairsMeta = {};
                                                  
                                                  newPairs.forEach((p) => {
                                                    if (p.key && p.key.trim() !== '') {
                                                      newObject[p.key] = p.value || '';
                                                      pairsMeta[p.key] = p.id;
                                                    } else {
                                                      const tempKey = `__empty_${p.id}`;
                                                      newObject[tempKey] = p.value || '';
                                                      pairsMeta[tempKey] = p.id;
                                                    }
                                                  });
                                                  
                                                  newObject.__pairs_meta__ = pairsMeta;
                                                  
                                                  setConfig({
                                                    ...config,
                                                    inputs: {
                                                      ...(config.inputs || {}),
                                                      [inputKey]: newObject,
                                                    },
                                                  });
                                                  
                                                  setTimeout(() => {
                                                    if (targetInput) {
                                                      targetInput.focus();
                                                      const newPos = start + combinedValue.length;
                                                      targetInput.setSelectionRange(newPos, newPos);
                                                    }
                                                  }, 0);
                                                }
                                              }}
                                              className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group cursor-pointer"
                                              title={`Combined output from ${group.nodes.length} ${group.type} nodes`}
                                            >
                                              <span className="font-mono text-xs text-blue-700 group-hover:text-blue-900">{combinedValue}</span>
                                              <span className="text-xs text-gray-500 ml-2">{output.label || key}</span>
                                            </button>
                                          );
                                        })}
                                      </div>
                                    </div>
                                  );
                                })}
                                
                                {/* Show individual nodes */}
                                {individualParentNodes.map((item) => {
                                  const sourceNode = item.node;
                                  const nodeId = sourceNode.id;
                                  const nodeType = sourceNode.data.nodeType;
                                  const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
                                  
                                  let suggestions = [];
                                  if (nodeType === 'trigger' && item.outputs) {
                                    suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                      label: output.label || key,
                                      value: `{{trigger_data.${key}}}`
                                    }));
                                  } else if (nodeType === 'action' && item.outputs) {
                                    suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                      label: output.label || key,
                                      value: `{{${nodeId}.${key}}}`
                                    }));
                                  } else if (nodeType === 'agent' && item.outputs) {
                                    suggestions = Object.entries(item.outputs).map(([key, output]) => ({
                                      label: output.label || key,
                                      value: `{{${nodeId}.${key}}}`
                                    }));
                                  }
                                  
                                  if (suggestions.length === 0) return null;
                                  
                                  return (
                                    <div key={nodeId} className="bg-white rounded p-2 border border-green-100 text-xs">
                                      <div className="font-semibold text-green-900 truncate">{nodeLabel}</div>
                                      <div className="text-gray-600 mt-1 space-y-0.5">
                                        {suggestions.map((s, idx) => (
                                          <button
                                            key={idx}
                                            type="button"
                                            onMouseDown={(e) => e.preventDefault()}
                                            onClick={() => {
                                              const targetInput = lastFocusedInputRef.current;
                                              const inputInfo = lastFocusedInputInfoRef.current;
                                              
                                              if (targetInput && inputInfo.index !== null && inputInfo.inputKey === inputKey) {
                                                const start = targetInput.selectionStart || 0;
                                                const end = targetInput.selectionEnd || 0;
                                                const currentValue = targetInput.value || '';
                                                const newValue = currentValue.slice(0, start) + s.value + currentValue.slice(end);
                                                
                                                const newPairs = [...keyValuePairs];
                                                const oldPair = newPairs[inputInfo.index];
                                                
                                                if (inputInfo.isKey) {
                                                  newPairs[inputInfo.index] = { ...oldPair, key: newValue };
                                                } else {
                                                  newPairs[inputInfo.index] = { ...oldPair, value: newValue };
                                                }
                                                
                                                const newObject = {};
                                                const pairsMeta = {};
                                                
                                                newPairs.forEach((p) => {
                                                  if (p.key && p.key.trim() !== '') {
                                                    newObject[p.key] = p.value || '';
                                                    pairsMeta[p.key] = p.id;
                                                  } else {
                                                    const tempKey = `__empty_${p.id}`;
                                                    newObject[tempKey] = p.value || '';
                                                    pairsMeta[tempKey] = p.id;
                                                  }
                                                });
                                                
                                                newObject.__pairs_meta__ = pairsMeta;
                                                
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: newObject,
                                                  },
                                                });
                                                
                                                setTimeout(() => {
                                                  if (targetInput) {
                                                    targetInput.focus();
                                                    const newPos = start + s.value.length;
                                                    targetInput.setSelectionRange(newPos, newPos);
                                                  }
                                                }, 0);
                                              }
                                            }}
                                            className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group cursor-pointer"
                                            title={`Click to insert ${s.value} at cursor position`}
                                          >
                                            <span className="font-mono text-xs text-blue-700 group-hover:text-blue-900">{s.value}</span>
                                            <span className="text-xs text-gray-500 ml-2">{s.label}</span>
                                          </button>
                                        ))}
                                      </div>
                                    </div>
                                  );
                                })}
                                
                                {/* Show trigger nodes that aren't connected */}
                                {allTriggerNodes.map((sourceNode) => {
                                  const triggerId = sourceNode.data.config?.trigger_type || sourceNode.data.triggerType;
                                  if (!triggerId || !triggerConfigs[triggerId]?.outputs) return null;
                                  
                                  const suggestions = Object.entries(triggerConfigs[triggerId].outputs).map(([key, output]) => ({
                                    label: output.label || key,
                                    value: `{{trigger_data.${key}}}`
                                  }));
                                  
                                  return (
                                    <div key={sourceNode.id} className="bg-white rounded p-2 border border-green-100 text-xs">
                                      <div className="font-semibold text-green-900 truncate">
                                        {sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node'}
                                      </div>
                                      <div className="text-gray-600 mt-1 space-y-0.5">
                                        {suggestions.map((s, idx) => (
                                          <button
                                            key={idx}
                                            type="button"
                                            onMouseDown={(e) => e.preventDefault()}
                                            onClick={() => {
                                              const targetInput = lastFocusedInputRef.current;
                                              const inputInfo = lastFocusedInputInfoRef.current;
                                              
                                              if (targetInput && inputInfo.index !== null && inputInfo.inputKey === inputKey) {
                                                const start = targetInput.selectionStart || 0;
                                                const end = targetInput.selectionEnd || 0;
                                                const currentValue = targetInput.value || '';
                                                const newValue = currentValue.slice(0, start) + s.value + currentValue.slice(end);
                                                
                                                const newPairs = [...keyValuePairs];
                                                const oldPair = newPairs[inputInfo.index];
                                                
                                                if (inputInfo.isKey) {
                                                  newPairs[inputInfo.index] = { ...oldPair, key: newValue };
                                                } else {
                                                  newPairs[inputInfo.index] = { ...oldPair, value: newValue };
                                                }
                                                
                                                const newObject = {};
                                                const pairsMeta = {};
                                                
                                                newPairs.forEach((p) => {
                                                  if (p.key && p.key.trim() !== '') {
                                                    newObject[p.key] = p.value || '';
                                                    pairsMeta[p.key] = p.id;
                                                  } else {
                                                    const tempKey = `__empty_${p.id}`;
                                                    newObject[tempKey] = p.value || '';
                                                    pairsMeta[tempKey] = p.id;
                                                  }
                                                });
                                                
                                                newObject.__pairs_meta__ = pairsMeta;
                                                
                                                setConfig({
                                                  ...config,
                                                  inputs: {
                                                    ...(config.inputs || {}),
                                                    [inputKey]: newObject,
                                                  },
                                                });
                                                
                                                setTimeout(() => {
                                                  if (targetInput) {
                                                    targetInput.focus();
                                                    const newPos = start + s.value.length;
                                                    targetInput.setSelectionRange(newPos, newPos);
                                                  }
                                                }, 0);
                                              }
                                            }}
                                            className="block w-full text-left p-1.5 rounded hover:bg-blue-50 transition-colors group cursor-pointer"
                                            title={`Click to insert ${s.value} at cursor position`}
                                          >
                                            <span className="font-mono text-xs text-blue-700 group-hover:text-blue-900">{s.value}</span>
                                            <span className="text-xs text-gray-500 ml-2">{s.label}</span>
                                          </button>
                                        ))}
                                      </div>
                                    </div>
                                  );
                                })}
                              </div>
                            </div>
                          )}
                          
                          {/* Key-Value pairs list */}
                          <div className="space-y-2 mb-2">
                            {keyValuePairs.map((pair, index) => (
                              <div key={pair.id || index} className="flex items-start gap-2 p-2 border border-gray-300 rounded-md bg-gray-50">
                                <div className="flex-1 grid grid-cols-2 gap-2">
                                  <input
                                    type="text"
                                    data-input-key={`${inputKey}_key_${index}`}
                                    onFocus={(e) => {
                                      lastFocusedInputRef.current = e.target;
                                      lastFocusedInputInfoRef.current = { index: index, isKey: true, inputKey: inputKey };
                                    }}
                                    value={pair.key || ''}
                                    onChange={(e) => {
                                      const newPairs = [...keyValuePairs];
                                      const oldPair = newPairs[index];
                                      const newKey = e.target.value;
                                      newPairs[index] = { ...oldPair, key: newKey };
                                      
                                      // Convert back to object with stable IDs
                                      const newObject = {};
                                      const pairsMeta = {};
                                      
                                      newPairs.forEach((p) => {
                                        if (p.key && p.key.trim() !== '') {
                                          // Real key-value pair - use key as the object key
                                          newObject[p.key] = p.value || '';
                                          // Store the stable ID mapping
                                          pairsMeta[p.key] = p.id;
                                        } else {
                                          // Empty pair - use temporary key but preserve ID
                                          const tempKey = `__empty_${p.id}`;
                                          newObject[tempKey] = p.value || '';
                                          pairsMeta[tempKey] = p.id;
                                        }
                                      });
                                      
                                      // Store the meta information
                                      newObject.__pairs_meta__ = pairsMeta;
                                      
                                      setConfig({
                                        ...config,
                                        inputs: {
                                          ...(config.inputs || {}),
                                          [inputKey]: newObject,
                                        },
                                      });
                                    }}
                                    placeholder="Key"
                                    className="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                  />
                                  <input
                                    type="text"
                                    data-input-key={`${inputKey}_value_${index}`}
                                    onFocus={(e) => {
                                      lastFocusedInputRef.current = e.target;
                                      lastFocusedInputInfoRef.current = { index: index, isKey: false, inputKey: inputKey };
                                    }}
                                    value={pair.value || ''}
                                    onChange={(e) => {
                                      const newPairs = [...keyValuePairs];
                                      const oldPair = newPairs[index];
                                      newPairs[index] = { ...oldPair, value: e.target.value };
                                      
                                      // Convert back to object with stable IDs
                                      const newObject = {};
                                      const pairsMeta = {};
                                      
                                      newPairs.forEach((p) => {
                                        if (p.key && p.key.trim() !== '') {
                                          // Real key-value pair - use key as the object key
                                          newObject[p.key] = p.value || '';
                                          // Store the stable ID mapping
                                          pairsMeta[p.key] = p.id;
                                        } else {
                                          // Empty pair - use temporary key but preserve ID
                                          const tempKey = `__empty_${p.id}`;
                                          newObject[tempKey] = p.value || '';
                                          pairsMeta[tempKey] = p.id;
                                        }
                                      });
                                      
                                      // Store the meta information
                                      newObject.__pairs_meta__ = pairsMeta;
                                      
                                      setConfig({
                                        ...config,
                                        inputs: {
                                          ...(config.inputs || {}),
                                          [inputKey]: newObject,
                                        },
                                      });
                                    }}
                                    placeholder="Value (use {{variables}})"
                                    className="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                  />
                                </div>
                                <button
                                  type="button"
                                    onClick={() => {
                                      const newPairs = keyValuePairs.filter((_, i) => i !== index);
                                      
                                      // Convert back to object with stable IDs
                                      const newObject = {};
                                      const pairsMeta = {};
                                      
                                      newPairs.forEach((p) => {
                                        if (p.key && p.key.trim() !== '') {
                                          // Real key-value pair
                                          newObject[p.key] = p.value || '';
                                          pairsMeta[p.key] = p.id;
                                        } else {
                                          // Empty pair - preserve with its ID
                                          const tempKey = `__empty_${p.id}`;
                                          newObject[tempKey] = p.value || '';
                                          pairsMeta[tempKey] = p.id;
                                        }
                                      });
                                      
                                      // Store the meta information
                                      newObject.__pairs_meta__ = pairsMeta;
                                      
                                      setConfig({
                                        ...config,
                                        inputs: {
                                          ...(config.inputs || {}),
                                          [inputKey]: newObject,
                                        },
                                      });
                                    }}
                                  className="text-red-500 hover:text-red-700 text-sm flex-shrink-0 mt-1"
                                  title="Remove item"
                                >
                                  ✕
                                </button>
                              </div>
                            ))}
                          </div>
                          
                          {/* Add button */}
                          <button
                            type="button"
                            onClick={() => {
                              // Add new empty pair to the list with unique stable ID
                              const newId = `pair_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
                              const newPairs = [...keyValuePairs, { id: newId, key: '', value: '', originalKey: '' }];
                              
                              // Convert back to object with stable IDs
                              const newObject = {};
                              const pairsMeta = {};
                              
                              newPairs.forEach((p) => {
                                if (p.key && p.key.trim() !== '') {
                                  // Real key-value pair
                                  newObject[p.key] = p.value || '';
                                  pairsMeta[p.key] = p.id;
                                } else {
                                  // Empty pair - use temporary key but preserve stable ID
                                  const tempKey = `__empty_${p.id}`;
                                  newObject[tempKey] = p.value || '';
                                  pairsMeta[tempKey] = p.id;
                                }
                              });
                              
                              // Store the meta information
                              newObject.__pairs_meta__ = pairsMeta;
                              
                              setConfig({
                                ...config,
                                inputs: {
                                  ...(config.inputs || {}),
                                  [inputKey]: newObject,
                                },
                              });
                            }}
                            className="text-sm text-blue-600 hover:text-blue-800 mb-2 flex items-center gap-1"
                          >
                            <span>+</span> Add {inputKey === 'url_params' ? 'URL Parameter' : inputKey === 'query_params' ? 'Query Parameter' : inputKey === 'headers' ? 'Header' : 'Field'}
                          </button>
                          
                          {inputConfig.description && (
                            <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>
                          )}
                          {inputKey === 'body' && (
                            <p className="text-xs text-blue-600 mt-1">
                              💡 Tip: Use variables in values like {'{{trigger_data.name}}'}
                            </p>
                          )}
                        </div>
                      );
                    }
                    
                    // For output_schema, keep JSON textarea
                    const objectValue = typeof inputValue === 'object' && inputValue !== null ? inputValue : {};
                    const objectString = JSON.stringify(objectValue, null, 2);
                    
                    return (
                      <div key={inputKey}>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          {inputConfig.label || inputKey}
                          {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
                        </label>
                        <textarea
                          value={objectString}
                          onChange={(e) => {
                            try {
                              const parsed = JSON.parse(e.target.value);
                              setConfig({
                                ...config,
                                inputs: {
                                  ...(config.inputs || {}),
                                  [inputKey]: parsed,
                                },
                              });
                            } catch {
                              // Invalid JSON, store as string temporarily
                              setConfig({
                                ...config,
                                inputs: {
                                  ...(config.inputs || {}),
                                  [inputKey]: e.target.value,
                                },
                              });
                            }
                          }}
                          placeholder={inputKey === 'output_schema' ? '{"status": "string", "data": {"id": "number"}}' : '{"key": "value"}'}
                          rows={inputKey === 'output_schema' ? 8 : 4}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-xs"
                        />
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

