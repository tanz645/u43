import { useCallback, useRef } from 'react';
import ReactFlow, {
  Background,
  Controls,
  MiniMap,
  useReactFlow,
} from 'reactflow';
import 'reactflow/dist/style.css';
import { useWorkflowStore } from '../store/workflowStore';
import WorkflowNode from './WorkflowNode';
import WorkflowEdge from './WorkflowEdge';

const nodeTypes = {
  workflowNode: WorkflowNode,
};

const edgeTypes = {
  workflowEdge: WorkflowEdge,
};

export default function WorkflowCanvas() {
  const {
    nodes,
    edges,
    onNodesChange,
    onEdgesChange,
    onConnect,
    addNode,
    selectNode,
  } = useWorkflowStore();
  
  const reactFlowWrapper = useRef(null);
  const { screenToFlowPosition } = useReactFlow();
  
  const onDragOver = useCallback((event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
  }, []);
  
  const onDrop = useCallback(
    (event) => {
      event.preventDefault();
      
      try {
        const dataString = event.dataTransfer.getData('application/reactflow');
        if (!dataString) {
          return;
        }
        
        const data = JSON.parse(dataString);
      
      if (!data || !data.type) {
        return;
      }
      
      const position = screenToFlowPosition({
        x: event.clientX,
        y: event.clientY,
      });
      
        // Prepare nodeData object for addNode
        const nodeData = {
          label: data.label || `${data.type} Node`,
          triggerType: data.triggerType,
          agentId: data.agentId,
          toolId: data.toolId,
          category: data.category,
          icon: data.icon,
        };
        
        const newNode = addNode(data.type, position, nodeData);
      if (newNode) {
        selectNode(newNode.id);
        }
      } catch (error) {
        console.error('Error handling node drop:', error);
        // Don't crash, just log the error
      }
    },
    [screenToFlowPosition, addNode, selectNode]
  );
  
  return (
    <div className="workflow-canvas flex-1" ref={reactFlowWrapper} style={{ width: '100%', height: '100%' }}>
      <ReactFlow
        nodes={nodes}
        edges={edges}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onDrop={onDrop}
        onDragOver={onDragOver}
        onNodeClick={(event, node) => {
          event.stopPropagation();
          selectNode(node.id);
        }}
        nodeTypes={nodeTypes}
        edgeTypes={edgeTypes}
        defaultEdgeOptions={{
          type: 'workflowEdge',
          animated: true,
        }}
        fitView
      >
        <Background />
        <Controls />
        <MiniMap
          nodeColor={(node) => {
            const colors = {
              trigger: '#10b981',
              action: '#3b82f6',
              agent: '#8b5cf6',
              condition: '#f59e0b',
            };
            return colors[node.data?.nodeType] || '#6b7280';
          }}
          maskColor="rgba(0, 0, 0, 0.1)"
        />
      </ReactFlow>
    </div>
  );
}

