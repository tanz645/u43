import { create } from 'zustand';
import { addEdge, applyNodeChanges, applyEdgeChanges } from 'reactflow';

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
      
      // For agent nodes, ensure settings are preserved
      if (node.type === 'agent' && node.config?.settings) {
        config.settings = node.config.settings;
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
    
    // Convert workflow edges to React Flow format
    const edges = workflowEdges.map(edge => ({
      id: edge.id || `edge_${edge.from}_${edge.to}`,
      source: edge.from,
      target: edge.to,
      sourceHandle: edge.sourceHandle || null, // Preserve handle ID for condition nodes
      targetHandle: edge.targetHandle || null,
      type: 'smoothstep',
      animated: true,
    }));
    
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
    const newEdges = addEdge(connection, state.edges);
    
    // Auto-populate condition node field when connected from agent/trigger node
    if (connection.target) {
      const targetNode = state.nodes.find(n => n.id === connection.target);
      if (targetNode && targetNode.data.nodeType === 'condition') {
        const sourceNode = state.nodes.find(n => n.id === connection.source);
        if (sourceNode) {
          const sourceNodeType = sourceNode.data.nodeType;
          let suggestedField = '';
          
          if (sourceNodeType === 'agent') {
            // For agent nodes, suggest common output fields
            suggestedField = `{{${connection.source}.decision}}`;
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
          model: 'gpt-3.5-turbo',
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
    set({
      nodes: get().nodes.map(node =>
        node.id === nodeId
          ? { 
              ...node, 
              data: { 
                ...node.data, 
                ...data,
                // Update label if title is provided
                label: data.label || data.config?.title || node.data.label,
              } 
            }
          : node
      ),
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
      })),
    };
  },
}));

