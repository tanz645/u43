import { create } from 'zustand';
import { addEdge, applyNodeChanges, applyEdgeChanges } from 'reactflow';
import { getNodeOutputs } from '../components/NodeConfigPanel/utils/nodeOutputs';

/**
 * Helper function to check if a node has multiple outputs
 * Works generically for any node type including condition nodes and future node types
 * 
 * @param {Object} node - The node object
 * @param {Object} configs - Configs object with toolConfigs, triggerConfigs, agentConfigs
 * @returns {boolean} True if node has multiple outputs
 */
const hasMultipleOutputs = (node, configs) => {
  if (!node) return false;
  
  const nodeType = node.data?.nodeType;
  
  // Condition nodes always have 2 outputs (true/false)
  if (nodeType === 'condition') {
    return true;
  }
  
  // For all other node types, check outputs using getNodeOutputs
  const outputs = getNodeOutputs(node, configs);
  return outputs && Object.keys(outputs).length > 1;
};

/**
 * Helper function to get edge label from source node and handle
 * 
 * This function is designed to work automatically for any node type that defines outputs.
 * It will work for:
 * - Condition nodes (hardcoded outputs)
 * - Trigger/Agent/Action nodes (from config files)
 * - Future node types with static outputs (from config files)
 * - Future node types with dynamic outputs (from node.data.config.outputs or node.data.outputs)
 * 
 * **Dynamic Outputs Support:**
 * When a node's configuration changes and outputs are updated dynamically (e.g., in node.data.config.outputs),
 * the edge labels will automatically update because:
 * 1. getNodeOutputs checks node.data.config.outputs FIRST (before config files)
 * 2. updateNode() automatically updates edge labels when node config changes
 * 
 * @param {Object} sourceNode - The source node
 * @param {string} sourceHandle - The source handle ID (output field name)
 * @param {Object} configs - Configs object with toolConfigs, triggerConfigs, agentConfigs
 * @returns {string|null} Label string or null
 */
const getEdgeLabel = (sourceNode, sourceHandle, configs) => {
  if (!sourceNode || !sourceHandle) return null;
  
  const nodeType = sourceNode.data?.nodeType;
  
  // Handle condition nodes directly (they have hardcoded outputs)
  if (nodeType === 'condition') {
    const conditionOutputs = {
      'true': { type: 'boolean', label: 'True' },
      'false': { type: 'boolean', label: 'False' },
    };
    const output = conditionOutputs[sourceHandle];
    return output ? output.label : null;
  }
  
  // Get outputs for all other node types (including future node types with dynamic outputs)
  // getNodeOutputs will check node.data.config.outputs FIRST (for dynamic outputs),
  // then fall back to config files (for static outputs)
  const outputs = getNodeOutputs(sourceNode, configs);
  if (!outputs || !outputs[sourceHandle]) return null;
  
  // Return the label from the output config, or fallback to the handle ID
  const output = outputs[sourceHandle];
  return output.label || sourceHandle;
};

/**
 * Workflow Store using Zustand
 */
export const useWorkflowStore = create((set, get) => ({
  // Workflow state
  workflowId: null,
  workflowTitle: '',
  workflowDescription: '',
  workflowStatus: 'draft',
  lastUpdated: null,
  
  // React Flow state
  nodes: [],
  edges: [],
  
  // UI state
  selectedNode: null,
  showConfigPanel: false,
  showNodePalette: true,
  
  // Tool configs cache (loaded from node-types endpoint)
  toolConfigs: {},
  triggerConfigs: {},
  agentConfigs: {},
  
  // Set tool configs
  setToolConfigs: (configs) => {
    set({ toolConfigs: configs });
  },
  
  // Set trigger configs
  setTriggerConfigs: (configs) => {
    set({ triggerConfigs: configs });
  },
  
  // Set agent configs
  setAgentConfigs: (configs) => {
    set({ agentConfigs: configs });
  },
  
  // Actions
  setWorkflow: (workflow) => {
    const workflowData = workflow.workflow_data || {};
    const workflowNodes = workflowData.nodes || [];
    const workflowEdges = workflowData.edges || [];
    
    // Convert workflow nodes to React Flow format
    const nodes = workflowNodes.map(node => {
      const config = node.config || {};
      
      // If trigger_type is at node level, move it to config for consistency
      if (node.type === 'trigger' && node.trigger_type && !config.trigger_type) {
        config.trigger_type = node.trigger_type;
      }
      
      // For agent nodes, ensure settings are preserved and upgrade old models
      if (node.type === 'agent' && node.config?.settings) {
        config.settings = { ...node.config.settings };
        // Upgrade old default model to new default
        if (!config.settings.model || config.settings.model === 'gpt-3.5-turbo' || config.settings.model === 'gpt-5') {
          config.settings.model = 'gpt-5.2';
        }
      } else if (node.type === 'agent') {
        // If no settings exist, set defaults
        config.settings = {
          provider: 'openai',
          model: 'gpt-5.2',
          temperature: 0.7,
        };
      }
      
      return {
      id: node.id,
      type: 'workflowNode',
      position: node.position || { x: 250, y: 250 },
      data: {
        label: node.config?.title || `${node.type} Node`,
        nodeType: node.type,
          config: config,
      },
      };
    });
    
    // Convert workflow edges to React Flow format with labels
    const state = get();
    const configs = {
      toolConfigs: state.toolConfigs,
      triggerConfigs: state.triggerConfigs,
      agentConfigs: state.agentConfigs,
    };
    
    // Convert edges with labels
    const edges = workflowEdges.map(edge => {
      const sourceNode = nodes.find(n => n.id === edge.from);
      const sourceHandle = edge.sourceHandle || null;
      
      // Get label from source node outputs
      let label = null;
      if (sourceNode && sourceHandle) {
        label = getEdgeLabel(sourceNode, sourceHandle, configs);
      }
      
      // Only include label if source node has multiple outputs
      // Use generic helper function that works for any node type
      const multipleOutputs = hasMultipleOutputs(sourceNode, configs);
      
      return {
        id: edge.id || `edge_${edge.from}_${edge.to}`,
        source: edge.from,
        target: edge.to,
        sourceHandle: sourceHandle,
        targetHandle: edge.targetHandle || null,
        type: 'workflowEdge',
        animated: true,
        data: multipleOutputs && label ? { label } : {},
      };
    });
    
    set({
      workflowId: workflow.id,
      workflowTitle: workflow.title,
      workflowDescription: workflow.description,
      workflowStatus: workflow.status,
      lastUpdated: workflow.updated_at || workflow.created_at || null,
      nodes: nodes,
      edges: edges,
    });
  },
  
  setNodes: (nodes) => set({ nodes }),
  
  setEdges: (edges) => set({ edges }),
  
  onNodesChange: (changes) => {
    set({
      nodes: applyNodeChanges(changes, get().nodes),
    });
  },
  
  onEdgesChange: (changes) => {
    set({
      edges: applyEdgeChanges(changes, get().edges),
    });
  },
  
  onConnect: (connection) => {
    const state = get();
    
    // Get source node to determine if we need a label
    const sourceNode = state.nodes.find(n => n.id === connection.source);
    const sourceHandle = connection.sourceHandle || null;
    
    const configs = {
      toolConfigs: state.toolConfigs,
      triggerConfigs: state.triggerConfigs,
      agentConfigs: state.agentConfigs,
    };
    
    // Get label from source node outputs
    let label = null;
    if (sourceNode && sourceHandle) {
      label = getEdgeLabel(sourceNode, sourceHandle, configs);
    }
    
    // Check if source node has multiple outputs (works generically for any node type)
    const multipleOutputs = hasMultipleOutputs(sourceNode, configs);
    
    // Create edge with label data if needed
    const edgeWithData = {
      ...connection,
      type: 'workflowEdge',
      data: multipleOutputs && label ? { label } : {},
    };
    
    const newEdges = addEdge(edgeWithData, state.edges);
    
    // Auto-populate condition node field when connected from agent/trigger node
    if (connection.target) {
      const targetNode = state.nodes.find(n => n.id === connection.target);
      if (targetNode && targetNode.data.nodeType === 'condition') {
        if (sourceNode) {
          const sourceNodeType = sourceNode.data.nodeType;
          let suggestedField = '';
          
          if (sourceNodeType === 'agent') {
            // For agent nodes, use the sourceHandle (output field) if specified
            // Otherwise default to decision
            const outputField = connection.sourceHandle || 'decision';
            suggestedField = `{{${connection.source}.${outputField}}}`;
          } else if (sourceNodeType === 'trigger') {
            // For trigger nodes, suggest common trigger data fields
            suggestedField = `{{trigger_data.comment_id}}`;
          } else {
            // For other nodes, use the node ID
            suggestedField = `{{${connection.source}}}`;
          }
          
          // Update condition node config if field is empty
          const currentConfig = targetNode.data.config || {};
          if (!currentConfig.field || currentConfig.field === '') {
            const updatedNodes = state.nodes.map(node =>
              node.id === connection.target
                ? {
                    ...node,
                    data: {
                      ...node.data,
                      config: {
                        ...node.data.config,
                        field: suggestedField,
                      },
                    },
                  }
                : node
            );
            set({ nodes: updatedNodes, edges: newEdges });
            return;
          }
        }
      }
    }
    
    set({ edges: newEdges });
  },
  
  addNode: (nodeType, position, nodeData = {}) => {
    try {
      const config = {};
      
      // Set trigger_type for trigger nodes
      if (nodeType === 'trigger' && nodeData.triggerType) {
        config.trigger_type = nodeData.triggerType;
      }
      
      // Set agent_id for agent nodes
      if (nodeType === 'agent' && nodeData.agentId) {
        config.agent_id = nodeData.agentId;
        // Set default settings from agent config (if available)
        // Default settings will be loaded from agent config file
        config.settings = {
          provider: 'openai',
          model: 'gpt-5.2',
          temperature: 0.7,
        };
      }
      
      // Set tool_id for action nodes
      if (nodeType === 'action' && nodeData.toolId) {
        config.tool_id = nodeData.toolId;
        
        // Map tool_id back to actionType for dropdown selection
        const toolIdToActionTypeMap = {
          'wordpress_approve_comment': 'approve_comment',
          'wordpress_spam_comment': 'spam_comment',
          'wordpress_delete_comment': 'delete_comment',
          'wordpress_send_email': 'send_email',
        };
        
        // Check if tool_id matches a known mapping
        const actionType = toolIdToActionTypeMap[nodeData.toolId];
        if (actionType) {
          config.actionType = actionType;
        } else {
          // If no mapping found, use tool_id as actionType
          config.actionType = nodeData.toolId;
        }
      }
      
      // Set default config for condition nodes
      if (nodeType === 'condition') {
        config.field = nodeData.field || '';
        config.operator = nodeData.operator || 'equals';
        config.value = nodeData.value || '';
      }
      
      // Set label from nodeData if provided, with fallback
      const label = nodeData?.label || (nodeType === 'condition' ? 'Condition' : `${nodeType} Node`);
      
    const newNode = {
        id: `node_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      type: 'workflowNode',
      position: position || { x: 250, y: 250 },
      data: {
          label: label,
        nodeType: nodeType,
          config: config,
          category: nodeData?.category,
          icon: nodeData?.icon,
      },
    };
    
    set({
      nodes: [...get().nodes, newNode],
    });
    
    return newNode;
    } catch (error) {
      console.error('Error adding node:', error, { nodeType, position, nodeData });
      return null;
    }
  },
  
  setNodesFromWorkflow: (workflowNodes) => {
    const nodes = workflowNodes.map(node => ({
      id: node.id,
      type: 'workflowNode',
      position: node.position || { x: 250, y: 250 },
      data: {
        label: node.config?.title || `${node.type} Node`,
        nodeType: node.type,
        config: node.config || {},
      },
    }));
    set({ nodes });
  },
  
  selectNode: (nodeId) => {
    try {
    const node = get().nodes.find(n => n.id === nodeId);
      if (node && node.data) {
    set({
      selectedNode: node,
      showConfigPanel: !!node,
    });
      } else {
        console.warn('Node not found or missing data:', nodeId);
        set({
          selectedNode: null,
          showConfigPanel: false,
        });
      }
    } catch (error) {
      console.error('Error selecting node:', error);
      set({
        selectedNode: null,
        showConfigPanel: false,
      });
    }
  },
  
  updateNode: (nodeId, data) => {
    const state = get();
    const updatedNodes = state.nodes.map(node =>
      node.id === nodeId
        ? { 
            ...node, 
            data: { 
              ...node.data, 
              ...data,
              // Deep merge config if both exist
              config: data.config 
                ? { ...node.data.config, ...data.config }
                : (data.config !== undefined ? data.config : node.data.config),
              // Update label if title is provided
              label: data.label || data.config?.title || node.data.label,
            } 
          }
        : node
    );
    
    // Update edge labels for edges connected from this node
    const configs = {
      toolConfigs: state.toolConfigs,
      triggerConfigs: state.triggerConfigs,
      agentConfigs: state.agentConfigs,
    };
    
    const updatedEdges = state.edges.map(edge => {
      if (edge.source === nodeId && edge.sourceHandle) {
        const sourceNode = updatedNodes.find(n => n.id === nodeId);
        const label = getEdgeLabel(sourceNode, edge.sourceHandle, configs);
        
        // Use generic helper function that works for any node type
        const multipleOutputs = hasMultipleOutputs(sourceNode, configs);
        
        return {
          ...edge,
          data: multipleOutputs && label ? { label } : {},
        };
      }
      return edge;
    });
    
    set({
      nodes: updatedNodes,
      edges: updatedEdges,
    });
  },
  
  deleteNode: (nodeId) => {
    set({
      nodes: get().nodes.filter(n => n.id !== nodeId),
      edges: get().edges.filter(e => e.source !== nodeId && e.target !== nodeId),
      selectedNode: null,
      showConfigPanel: false,
    });
  },
  
  duplicateNode: (nodeId) => {
    try {
      const state = get();
      const originalNode = state.nodes.find(n => n.id === nodeId);
      
      if (!originalNode) {
        console.error('Node not found for duplication:', nodeId);
        return null;
      }
      
      // Helper function to remove variable references from config values
      const removeVariables = (value) => {
        if (typeof value === 'string') {
          // Remove template variables like {{node_id.field}} or {{trigger_data.field}}
          return value.replace(/\{\{[^}]+\}\}/g, '').trim();
        } else if (Array.isArray(value)) {
          return value.map(item => removeVariables(item));
        } else if (value && typeof value === 'object') {
          const cleaned = {};
          for (const key in value) {
            cleaned[key] = removeVariables(value[key]);
          }
          return cleaned;
        }
        return value;
      };
      
      // Create new node ID
      const newNodeId = `node_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
      
      // Position the duplicate node offset to the right and slightly down
      const offsetX = 200;
      const offsetY = 50;
      const newPosition = {
        x: originalNode.position.x + offsetX,
        y: originalNode.position.y + offsetY,
      };
      
      // Deep clone and clean the config
      const cleanedConfig = removeVariables(originalNode.data.config || {});
      
      // Deep clone the node data to avoid reference issues
      const duplicatedNode = {
        id: newNodeId,
        type: originalNode.type,
        position: newPosition,
        data: {
          ...originalNode.data,
          label: `${originalNode.data.label} (Copy)`,
          config: {
            ...cleanedConfig,
            title: `${originalNode.data.config?.title || originalNode.data.label} (Copy)`,
          },
        },
      };
      
      // Add the duplicated node (no edges - not connected to parent)
      set({
        nodes: [...state.nodes, duplicatedNode],
      });
      
      // Select the duplicated node
      set({
        selectedNode: duplicatedNode,
        showConfigPanel: true,
      });
      
      return duplicatedNode;
    } catch (error) {
      console.error('Error duplicating node:', error, { nodeId });
      return null;
    }
  },
  
  toggleNodePalette: () => {
    set({ showNodePalette: !get().showNodePalette });
  },
  
  toggleConfigPanel: () => {
    set({ showConfigPanel: !get().showConfigPanel });
  },
  
  getWorkflowData: () => {
    const state = get();
    return {
      title: state.workflowTitle,
      description: state.workflowDescription,
      status: state.workflowStatus,
      nodes: state.nodes.map(node => {
        const nodeData = {
        id: node.id,
        type: node.data.nodeType,
        position: node.position,
        config: {
          ...node.data.config,
          title: node.data.label || node.data.config?.title,
          description: node.data.description || node.data.config?.description,
        },
        };
        
        // Include trigger_type at node level for trigger nodes (for backward compatibility)
        if (node.data.nodeType === 'trigger' && node.data.config?.trigger_type) {
          nodeData.trigger_type = node.data.config.trigger_type;
        }
        
        // Ensure tool_id is set for action nodes (map from actionType if needed)
        if (node.data.nodeType === 'action') {
          const config = node.data.config || {};
          if (!config.tool_id && config.actionType) {
            const toolIdMap = {
              'approve_comment': 'wordpress_approve_comment',
              'spam_comment': 'wordpress_spam_comment',
              'delete_comment': 'wordpress_delete_comment',
              'send_email': 'wordpress_send_email',
            };
            nodeData.config.tool_id = toolIdMap[config.actionType] || config.actionType;
          }
        }
        
        return nodeData;
      }),
      edges: state.edges.map(edge => ({
        id: edge.id,
        from: edge.source,
        to: edge.target,
        sourceHandle: edge.sourceHandle || null, // Preserve handle ID for condition nodes
        targetHandle: edge.targetHandle || null,
        // Note: label is not saved to database as it can be regenerated from source node outputs
      })),
    };
  },
}));

