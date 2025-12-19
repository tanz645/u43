import { useState, useEffect } from 'react';
import { useWorkflowStore } from '../../../store/workflowStore';

/**
 * Hook to manage node configuration initialization and updates
 * 
 * @param {Object} selectedNode - The currently selected node
 * @param {Function} fetchToolConfig - Function to fetch tool config
 * @param {Function} setToolConfig - Function to set tool config
 * @param {Function} fetchOpenAIModels - Function to fetch OpenAI models
 * @returns {Object} Object with config and setConfig
 */
export function useNodeConfig(selectedNode, fetchToolConfig, setToolConfig, fetchOpenAIModels) {
  const { toolConfigs } = useWorkflowStore();
  const [config, setConfig] = useState({});

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
            setToolConfig(foundConfig);
          } else {
            fetchToolConfig(configToSet.tool_id);
          }
        } else {
          setToolConfig(null);
        }
      } else if (selectedNode.data.nodeType === 'agent') {
        // For agent nodes, fetch OpenAI models
        setConfig(nodeConfig);
        if (fetchOpenAIModels) {
          fetchOpenAIModels(nodeConfig, setConfig);
        }
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
  }, [selectedNode, toolConfigs, fetchToolConfig, setToolConfig, fetchOpenAIModels]);

  return {
    config,
    setConfig
  };
}

