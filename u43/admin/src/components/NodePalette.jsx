// NodePalette component
import { useState, useEffect } from 'react';
import { useWorkflowStore } from '../store/workflowStore';

// Icon mapping for API icon strings to emojis
const iconMap = {
  'comment': '💬',
  'trash': '🗑️',
  'brain': '🤖',
  'check': '✓',
  'spam': '🚫',
  'email': '📧',
  'approve': '✅',
  'delete': '🗑️',
  'default': '⚡',
  'trigger': '⚡',
  'agent': '🤖',
  'action': '⚙️',
  'tool': '🔧',
};

// Transform API response to node format
function transformApiNodes(apiData) {
  const nodes = [];
  
  // Transform triggers
  if (apiData.triggers) {
    Object.entries(apiData.triggers).forEach(([id, trigger]) => {
      nodes.push({
    id: 'trigger',
        nodeId: id, // Store the actual trigger ID
        label: trigger.name || id,
        description: trigger.description || '',
        icon: iconMap[trigger.icon] || iconMap.default,
    category: 'Triggers',
        triggerType: id, // The trigger ID from config
      });
    });
  }
  
  // Transform agents
  if (apiData.agents) {
    Object.entries(apiData.agents).forEach(([id, agent]) => {
      nodes.push({
    id: 'agent',
        nodeId: id, // Store the actual agent ID
        label: agent.name || id,
        description: agent.description || '',
        icon: iconMap[agent.icon] || '🤖',
    category: 'Agents',
        agentId: id, // The agent ID from config
      });
    });
  }
  
  // Transform tools (actions)
  if (apiData.tools) {
    Object.entries(apiData.tools).forEach(([id, tool]) => {
      nodes.push({
    id: 'action',
        nodeId: id, // Store the actual tool ID
        label: tool.name || id,
        description: tool.description || '',
        icon: iconMap[tool.icon] || '⚙️',
    category: 'Actions',
        toolId: id, // The tool ID from config
      });
    });
  }
  
  // Add condition node (built-in)
  nodes.push({
    id: 'condition',
    nodeId: 'condition',
    label: 'Condition',
    description: 'Branch workflow based on conditions',
    icon: '🔀',
    category: 'Logic',
  });
  
  return nodes;
}

export default function NodePalette() {
  const { showNodePalette, toggleNodePalette, addNode } = useWorkflowStore();
  const [nodeTypes, setNodeTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    // Fetch node types from API
    const fetchNodeTypes = async () => {
      try {
        setLoading(true);
        setError(null);
        
        const restUrl = window.u43RestUrl || '/wp-json/u43/v1/';
        const response = await fetch(`${restUrl}node-types`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.u43RestNonce || '',
          },
          credentials: 'include',
        });
        
        if (!response.ok) {
          throw new Error(`Failed to fetch node types: ${response.statusText}`);
        }
        
        const data = await response.json();
        const transformedNodes = transformApiNodes(data);
        setNodeTypes(transformedNodes);
      } catch (err) {
        console.error('Error fetching node types:', err);
        setError(err.message);
        // Fallback to empty array on error
        setNodeTypes([]);
      } finally {
        setLoading(false);
      }
    };
    
    fetchNodeTypes();
  }, []);
  
  const handleDragStart = (event, nodeType, label, nodeData) => {
    event.dataTransfer.setData('application/reactflow', JSON.stringify({
      type: nodeType,
      label: label,
      triggerType: nodeData?.triggerType,
      agentId: nodeData?.agentId,
      toolId: nodeData?.toolId,
    }));
    event.dataTransfer.effectAllowed = 'move';
  };
  
  const groupedNodes = nodeTypes.reduce((acc, node) => {
    if (!acc[node.category]) {
      acc[node.category] = [];
    }
    acc[node.category].push(node);
    return acc;
  }, {});
  
  if (!showNodePalette) {
    return (
      <button
        onClick={toggleNodePalette}
        className="fixed left-0 top-1/2 -translate-y-1/2 bg-blue-600 text-white px-2 py-4 rounded-r-lg shadow-lg z-10"
      >
        ▶
      </button>
    );
  }
  
  return (
    <div className="w-64 bg-white border-r border-gray-200 h-full overflow-y-auto flex-shrink-0" style={{ height: '100%' }}>
      <div className="p-4 border-b border-gray-200 flex items-center justify-between">
        <h3 className="font-semibold text-gray-900">Nodes</h3>
        <button
          onClick={toggleNodePalette}
          className="text-gray-500 hover:text-gray-700"
        >
          ✕
        </button>
      </div>
      
      <div className="p-2">
        {loading && (
          <div className="p-4 text-center text-gray-500">
            <div className="animate-spin inline-block w-6 h-6 border-2 border-gray-300 border-t-blue-600 rounded-full"></div>
            <p className="mt-2 text-sm">Loading nodes...</p>
          </div>
        )}
        
        {error && (
          <div className="p-4 bg-red-50 border border-red-200 rounded-md">
            <p className="text-sm text-red-800">Error loading nodes: {error}</p>
            <p className="text-xs text-red-600 mt-1">Please refresh the page to try again.</p>
          </div>
        )}
        
        {!loading && !error && Object.keys(groupedNodes).length === 0 && (
          <div className="p-4 text-center text-gray-500">
            <p className="text-sm">No nodes available</p>
          </div>
        )}
        
        {!loading && !error && Object.entries(groupedNodes).map(([category, nodes]) => (
          <div key={category} className="mb-4">
            <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2 py-1">
              {category}
            </h4>
            <div className="space-y-1">
              {nodes.map((node, index) => (
                <div
                  key={`${node.nodeId || node.id}-${index}`}
                  draggable
                  onDragStart={(e) => handleDragStart(e, node.id, node.label, node)}
                  className="flex items-center gap-2 p-2 rounded hover:bg-gray-100 cursor-grab active:cursor-grabbing"
                >
                  <span className="text-lg">{node.icon}</span>
                  <div className="flex-1 min-w-0">
                    <div className="text-sm font-medium text-gray-900 truncate">
                      {node.label}
                    </div>
                    <div className="text-xs text-gray-500 truncate">
                      {node.description}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

