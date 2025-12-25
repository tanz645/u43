import { useMemo } from 'react';
import { useWorkflowStore } from '../../../store/workflowStore';

/**
 * Check if a target node is reachable from a trigger node (BFS traversal)
 * 
 * @param {string} triggerNodeId - The trigger node ID
 * @param {string} targetNodeId - The target node ID to check
 * @param {Array} nodes - All nodes in the workflow
 * @param {Array} edges - All edges in the workflow
 * @returns {boolean} True if target node is reachable from trigger node
 */
function isNodeReachableFromTrigger(triggerNodeId, targetNodeId, nodes, edges) {
  // If checking the trigger node itself, it's always reachable
  if (triggerNodeId === targetNodeId) {
    return true;
  }
  
  // Build forward graph (from -> to)
  const graph = {};
  edges.forEach(edge => {
    const from = edge.source || edge.from;
    const to = edge.target || edge.to;
    if (from && to) {
      if (!graph[from]) {
        graph[from] = [];
      }
      graph[from].push(to);
    }
  });
  
  // BFS from trigger node to find if target node is reachable
  const queue = [triggerNodeId];
  const visited = new Set([triggerNodeId]);
  
  while (queue.length > 0) {
    const current = queue.shift();
    
    // Check if we reached the target
    if (current === targetNodeId) {
      return true;
    }
    
    // Add all connected nodes to the queue
    if (graph[current]) {
      for (const nextNodeId of graph[current]) {
        if (!visited.has(nextNodeId)) {
          visited.add(nextNodeId);
          queue.push(nextNodeId);
        }
      }
    }
  }
  
  return false;
}

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
    const allTriggerNodes = nodes.filter(node => node.data?.nodeType === 'trigger');
    
    // Filter to only include trigger nodes that are connected to the selected node
    // A trigger node is connected if the selected node is reachable from the trigger
    return allTriggerNodes.filter(triggerNode => {
      return isNodeReachableFromTrigger(triggerNode.id, selectedNode.id, nodes, edges);
    });
  }, [selectedNode, nodes, edges]);

  return {
    connectedSourceNodes,
    triggerNodes
  };
}

