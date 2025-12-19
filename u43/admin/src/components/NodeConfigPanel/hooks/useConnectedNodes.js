import { useMemo } from 'react';
import { useWorkflowStore } from '../../../store/workflowStore';

/**
 * Hook to get connected source nodes and trigger nodes
 * 
 * @returns {Object} Object with connectedSourceNodes and triggerNodes
 */
export function useConnectedNodes(selectedNode) {
  const { nodes, edges } = useWorkflowStore();

  const connectedSourceNodes = useMemo(() => {
    if (!selectedNode) {
      return [];
    }
    
    // For action nodes, only show connected nodes when configuring tool inputs that support variables
    // For condition and agent nodes, always show connected nodes
    const nodeType = selectedNode.data?.nodeType;
    if (nodeType !== 'condition' && nodeType !== 'agent' && nodeType !== 'action') {
      return [];
    }
    
    const incomingEdges = edges.filter(edge => edge.target === selectedNode.id);
    return incomingEdges.map(edge => {
      const sourceNode = nodes.find(n => n.id === edge.source);
      return sourceNode;
    }).filter(Boolean);
  }, [selectedNode, nodes, edges]);

  const triggerNodes = useMemo(() => {
    if (!selectedNode) {
      return [];
    }
    
    const nodeType = selectedNode.data?.nodeType;
    // Only show trigger nodes for nodes that can use variables (condition, agent, action)
    if (nodeType !== 'condition' && nodeType !== 'agent' && nodeType !== 'action') {
      return [];
    }
    
    // Find all trigger nodes in the workflow
    return nodes.filter(node => node.data?.nodeType === 'trigger');
  }, [selectedNode, nodes]);

  return {
    connectedSourceNodes,
    triggerNodes
  };
}

