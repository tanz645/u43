// NodePalette component
import { useState, useEffect, useMemo } from 'react';
import { useWorkflowStore } from '../store/workflowStore';
import { iconMap, renderIcon } from './Icons';

// Transform API response to node format
function transformApiNodes(apiData) {
  const nodes = [];
  
  // Transform triggers
  if (apiData.triggers) {
    Object.entries(apiData.triggers).forEach(([id, trigger]) => {
      // Determine category based on ID
      let category = 'General';
      if (id.startsWith('whatsapp_')) {
        category = 'WhatsApp';
      } else if (id.startsWith('wordpress_')) {
        category = 'WordPress';
      }
      
      nodes.push({
    id: 'trigger',
        nodeId: id,
        label: trigger.name || id,
        description: trigger.description || '',
        icon: iconMap[trigger.icon] || iconMap.default,
        category: category,
        nodeType: 'trigger',
        triggerType: id,
      });
    });
  }
  
  // Transform agents
  if (apiData.agents) {
    Object.entries(apiData.agents).forEach(([id, agent]) => {
      nodes.push({
    id: 'agent',
        nodeId: id,
        label: agent.name || id,
        description: agent.description || '',
        icon: iconMap[agent.icon] || '🤖',
        category: 'General',
        nodeType: 'agent',
        agentId: id,
      });
    });
  }
  
  // Transform tools (actions)
  if (apiData.tools) {
    Object.entries(apiData.tools).forEach(([id, tool]) => {
      // Determine category based on ID
      let category = 'General';
      if (id.startsWith('whatsapp_')) {
        category = 'WhatsApp';
      } else if (id.startsWith('wordpress_')) {
        category = 'WordPress';
      }
      
      nodes.push({
    id: 'action',
        nodeId: id,
        label: tool.name || id,
        description: tool.description || '',
        icon: iconMap[tool.icon] || '⚙️',
        category: category,
        nodeType: 'action',
        toolId: id,
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
    category: 'General',
    nodeType: 'logic',
  });
  
  return nodes;
}

export default function NodePalette() {
  const { showNodePalette, toggleNodePalette, addNode } = useWorkflowStore();
  const [nodeTypes, setNodeTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [expandedCategories, setExpandedCategories] = useState({});
  
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
        
        // Store tool configs in the store for use in NodeConfigPanel
        if (data.tool_configs) {
          console.log('Storing tool configs in store:', Object.keys(data.tool_configs));
          const { setToolConfigs } = useWorkflowStore.getState();
          setToolConfigs(data.tool_configs);
        } else {
          console.warn('No tool_configs found in node-types response:', data);
        }
        
        // Store trigger configs (extract from triggers data)
        if (data.triggers) {
          const triggerConfigs = {};
          Object.entries(data.triggers).forEach(([id, trigger]) => {
            triggerConfigs[id] = trigger;
          });
          const { setTriggerConfigs } = useWorkflowStore.getState();
          setTriggerConfigs(triggerConfigs);
        }
        
        // Store trigger configs (from trigger_configs if available, otherwise extract from triggers data)
        if (data.trigger_configs) {
          const { setTriggerConfigs } = useWorkflowStore.getState();
          setTriggerConfigs(data.trigger_configs);
        } else if (data.triggers) {
          const triggerConfigs = {};
          Object.entries(data.triggers).forEach(([id, trigger]) => {
            triggerConfigs[id] = trigger;
          });
          const { setTriggerConfigs } = useWorkflowStore.getState();
          setTriggerConfigs(triggerConfigs);
        }
        
        // Store agent configs (from agent_configs if available, otherwise extract from agents data)
        if (data.agent_configs) {
          const { setAgentConfigs } = useWorkflowStore.getState();
          setAgentConfigs(data.agent_configs);
        } else if (data.agents) {
          const agentConfigs = {};
          Object.entries(data.agents).forEach(([id, agent]) => {
            agentConfigs[id] = agent;
          });
          const { setAgentConfigs } = useWorkflowStore.getState();
          setAgentConfigs(agentConfigs);
        }
      } catch (err) {
        console.error('Error fetching node types:', err);
        setError(err.message);
        setNodeTypes([]);
      } finally {
        setLoading(false);
      }
    };
    
    fetchNodeTypes();
  }, []);
  
  // Filter nodes based on search query
  const filteredNodes = useMemo(() => {
    if (!searchQuery.trim()) {
      return nodeTypes;
    }
    
    const query = searchQuery.toLowerCase();
    return nodeTypes.filter(node => 
      node.label.toLowerCase().includes(query) ||
      node.description.toLowerCase().includes(query) ||
      node.category.toLowerCase().includes(query) ||
      node.nodeType.toLowerCase().includes(query)
    );
  }, [nodeTypes, searchQuery]);
  
  // Group nodes by node type first, then by category
  const groupedNodes = useMemo(() => {
    const grouped = {};
    
    filteredNodes.forEach(node => {
      const nodeType = node.nodeType || 'other';
      const category = node.category || 'General';
      
      if (!grouped[nodeType]) {
        grouped[nodeType] = {};
      }
      
      if (!grouped[nodeType][category]) {
        grouped[nodeType][category] = [];
      }
      
      grouped[nodeType][category].push(node);
    });
    
    return grouped;
  }, [filteredNodes]);
  
  // Auto-expand categories that have matching nodes when searching
  useEffect(() => {
    if (searchQuery.trim()) {
      const newExpanded = {};
      Object.entries(groupedNodes).forEach(([nodeType, categories]) => {
        Object.entries(categories).forEach(([category, nodes]) => {
          if (nodes.length > 0) {
            const key = `${nodeType}-${category}`;
            newExpanded[key] = true;
          }
        });
      });
      setExpandedCategories(newExpanded);
    }
    // When search is cleared, don't reset - keep user's manual expand/collapse state
  }, [searchQuery, groupedNodes]);
  
  const handleDragStart = (event, nodeType, label, nodeData) => {
    event.dataTransfer.setData('application/reactflow', JSON.stringify({
      type: nodeType,
      label: label,
      triggerType: nodeData?.triggerType,
      agentId: nodeData?.agentId,
      toolId: nodeData?.toolId,
      category: nodeData?.category,
      icon: nodeData?.icon,
    }));
    event.dataTransfer.effectAllowed = 'move';
  };
  
  const toggleCategory = (nodeType, category) => {
    const key = `${nodeType}-${category}`;
    setExpandedCategories(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };
  
  const isCategoryExpanded = (nodeType, category) => {
    const key = `${nodeType}-${category}`;
    // If searching, auto-expand categories with matching nodes
    if (searchQuery.trim()) {
      return expandedCategories[key] !== false; // Default to expanded when searching
    }
    // When not searching, use user's toggle state (default to collapsed)
    return expandedCategories[key] === true;
  };
  
  const nodeTypeLabels = {
    trigger: 'Triggers',
    action: 'Actions',
    agent: 'Agents',
    logic: 'Logic',
    other: 'Other',
  };
  
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
    <div className="w-64 bg-white border-r border-gray-200 h-full overflow-y-auto flex-shrink-0 flex flex-col" style={{ height: '100%' }}>
      <div className="p-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
        <h3 className="font-semibold text-gray-900">Nodes</h3>
        <button
          onClick={toggleNodePalette}
          className="text-gray-500 hover:text-gray-700"
        >
          ✕
        </button>
      </div>
      
      {/* Search Bar */}
      <div className="p-2 border-b border-gray-200 flex-shrink-0">
        <input
          type="text"
          placeholder="Search nodes..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
      
      <div className="flex-1 overflow-y-auto p-2">
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
            <p className="text-sm">No nodes found</p>
            {searchQuery && (
              <p className="text-xs mt-1">Try a different search term</p>
            )}
          </div>
        )}
        
        {!loading && !error && Object.entries(groupedNodes).map(([nodeType, categories]) => (
          <div key={nodeType} className="mb-6">
            <h3 className="text-sm font-bold text-gray-700 uppercase tracking-wide px-2 py-2 mb-2 border-b border-gray-200">
              {nodeTypeLabels[nodeType] || nodeType}
            </h3>
            
            {Object.entries(categories).map(([category, nodes]) => {
              const categoryKey = `${nodeType}-${category}`;
              const isExpanded = isCategoryExpanded(nodeType, category);
              
              return (
                <div key={categoryKey} className="mb-2">
                  <button
                    onClick={() => toggleCategory(nodeType, category)}
                    className="w-full flex items-center justify-between px-2 py-1.5 text-xs font-semibold text-gray-600 uppercase tracking-wide hover:bg-gray-50 rounded transition-colors"
                  >
                    <span className="flex items-center gap-2">
                      <span className={`transform transition-transform ${isExpanded ? 'rotate-90' : ''}`}>
                        ▶
                      </span>
              {category}
                    </span>
                    <span className="text-gray-400 text-xs">({nodes.length})</span>
                  </button>
                  
                  {isExpanded && (
                    <div className="space-y-1 mt-1 ml-4">
              {nodes.map((node, index) => (
                <div
                  key={`${node.nodeId || node.id}-${index}`}
                  draggable
                  onDragStart={(e) => handleDragStart(e, node.id, node.label, node)}
                          className="flex items-center gap-1.5 p-2 rounded hover:bg-gray-100 cursor-grab active:cursor-grabbing"
                >
                          <span className="flex-shrink-0">{renderIcon(node.icon, node.category)}</span>
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
                  )}
                </div>
              );
            })}
          </div>
        ))}
      </div>
    </div>
  );
}
