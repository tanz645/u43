import { useMemo } from 'react';
import { generateVariableSuggestions, generateCombinedVariable } from '../utils/variableHelpers';
import { getNodeOutputs } from '../utils/nodeOutputs';

/**
 * Hook for preparing variable suggestions data
 * 
 * @param {Object} params - Parameters
 * @param {Array} groupedParentNodes - Grouped parent nodes
 * @param {Array} individualParentNodes - Individual parent nodes
 * @param {Array} triggerNodes - All trigger nodes in workflow
 * @param {Array} connectedSourceNodes - Connected source nodes
 * @param {Object} configs - Configuration objects (toolConfigs, triggerConfigs, agentConfigs)
 * @returns {Object} Prepared variable suggestions data
 */
export const useVariableSuggestions = ({
  groupedParentNodes = [],
  individualParentNodes = [],
  triggerNodes = [],
  connectedSourceNodes = [],
  configs = {}
}) => {
  const { toolConfigs = {}, triggerConfigs = {}, agentConfigs = {} } = configs;

  // Filter trigger nodes that aren't already connected
  const availableTriggerNodes = useMemo(() => {
    return triggerNodes.filter(node => 
      !connectedSourceNodes.some(connected => connected.id === node.id)
    );
  }, [triggerNodes, connectedSourceNodes]);

  // Prepare grouped variable suggestions
  const groupedSuggestions = useMemo(() => {
    return groupedParentNodes
      .filter(group => group.outputs && Object.keys(group.outputs).length > 0)
      .map((group, groupIdx) => {
        const nodeTypeLabel = group.type.charAt(0).toUpperCase() + group.type.slice(1);
        const nodeLabels = group.nodes.map(n => 
          n.data.label || n.data.config?.title || 'Untitled Node'
        ).join(', ');

        return {
          id: `group_${groupIdx}`,
          type: 'grouped',
          nodeTypeLabel,
          nodeLabels,
          nodeCount: group.nodes.length,
          outputs: Object.entries(group.outputs).map(([key, output]) => ({
            key,
            label: output.label || key,
            type: output.type || 'string',
            value: generateCombinedVariable(group.type, key),
            description: `Combined from ${group.nodes.length} nodes • Type: ${output.type || 'string'}`
          }))
        };
      });
  }, [groupedParentNodes]);

  // Prepare individual node suggestions
  const individualSuggestions = useMemo(() => {
    return individualParentNodes
      .filter(item => item.outputs && Object.keys(item.outputs).length > 0)
      .map((item) => {
        const sourceNode = item.node;
        const nodeId = sourceNode.id;
        const nodeType = sourceNode.data.nodeType;
        const nodeLabel = sourceNode.data.label || sourceNode.data.config?.title || 'Untitled Node';
        
        const suggestions = generateVariableSuggestions(sourceNode, item.outputs);

        return {
          id: nodeId,
          type: 'individual',
          nodeType,
          nodeLabel,
          suggestions
        };
      });
  }, [individualParentNodes]);

  // Prepare trigger node suggestions
  const triggerSuggestions = useMemo(() => {
    return availableTriggerNodes
      .map((node) => {
        const nodeId = node.id;
        const nodeLabel = node.data.label || node.data.config?.title || 'Untitled Node';
        
        // Get outputs for trigger node
        const outputs = getNodeOutputs(node, { toolConfigs, triggerConfigs, agentConfigs });
        
        if (!outputs || Object.keys(outputs).length === 0) {
          return null;
        }
        
        // Generate suggestions for trigger outputs
        const suggestions = Object.entries(outputs).map(([key, output]) => ({
          label: output.label || key,
          description: `Type: ${output.type || 'string'}`,
          value: `{{trigger_data.${key}}}`
        }));
        
        return {
          id: nodeId,
          type: 'trigger',
          nodeLabel,
          suggestions
        };
      })
      .filter(Boolean); // Remove null entries
  }, [availableTriggerNodes, triggerConfigs]);

  const hasSuggestions = groupedSuggestions.length > 0 || 
                        individualSuggestions.length > 0 || 
                        triggerSuggestions.length > 0;

  return {
    groupedSuggestions,
    individualSuggestions,
    triggerSuggestions,
    availableTriggerNodes,
    hasSuggestions
  };
};

