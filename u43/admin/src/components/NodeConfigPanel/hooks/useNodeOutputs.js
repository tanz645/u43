import { useMemo } from 'react';
import { getNodeOutputs, getOutputSignature, isButtonMessageNode, normalizeId } from '../utils/nodeOutputs';

/**
 * Hook for calculating and grouping node outputs
 * 
 * @param {Object} params - Parameters
 * @param {Array} connectedSourceNodes - Connected source nodes
 * @param {Object} configs - Configuration objects (toolConfigs, triggerConfigs, agentConfigs)
 * @returns {Object} Object with grouped and individual parent nodes
 */
export const useNodeOutputs = (connectedSourceNodes = [], configs = {}) => {
  const { toolConfigs = {}, triggerConfigs = {}, agentConfigs = {} } = configs;
  
  return useMemo(() => {
    if (connectedSourceNodes.length === 0) {
      return { grouped: [], individual: [] };
    }
    
    // Group nodes by type and output signature
    // For agent nodes, also group by agent_id to ensure nodes with same agent type are grouped
    const groups = {};
    const individualNodes = [];
    
    connectedSourceNodes.forEach(node => {
      const nodeType = node.data?.nodeType;
      const outputs = getNodeOutputs(node, { toolConfigs, triggerConfigs, agentConfigs });
      const signature = getOutputSignature(outputs);
      
      // For agent nodes, include agent_id in the grouping key to ensure same agent types are grouped
      let groupKey;
      if (nodeType === 'agent') {
        const agentId = node.data.config?.agent_id || node.data.agentId || '';
        const { withUnderscores } = normalizeId(agentId);
        // Group by agent_id first, then by output signature
        groupKey = `${nodeType}_${withUnderscores}_${signature || 'no_outputs'}`;
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
    const buttonMessageNodes = connectedSourceNodes.filter(node => isButtonMessageNode(node));
    
    // If we have multiple button message nodes, combine all their buttons
    if (buttonMessageNodes.length > 1) {
      const combinedButtonOutputs = {};
      const allButtonNodes = [];
      
      buttonMessageNodes.forEach(node => {
        const outputs = getNodeOutputs(node, { toolConfigs, triggerConfigs, agentConfigs });
        if (outputs) {
          // Merge all button outputs (unique button IDs)
          Object.assign(combinedButtonOutputs, outputs);
          allButtonNodes.push(node);
        }
      });
      
      // Remove button message nodes from regular groups
      Object.keys(groups).forEach(groupKey => {
        groups[groupKey].nodes = groups[groupKey].nodes.filter(node => !isButtonMessageNode(node));
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
          outputs: getNodeOutputs(allButtonNodes[0], { toolConfigs, triggerConfigs, agentConfigs }),
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
          const { withUnderscores, withHyphens } = normalizeId(agentId);
          const agentConfig = agentConfigs[agentId] || agentConfigs[withUnderscores] || agentConfigs[withHyphens];
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
            outputs: getNodeOutputs(node, { toolConfigs, triggerConfigs, agentConfigs }),
            isCombined: false
          });
        });
      }
    });
    
    return { grouped, individual: individualNodes };
  }, [connectedSourceNodes, toolConfigs, triggerConfigs, agentConfigs]);
};

