import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ReactFlowProvider } from 'reactflow';
import WorkflowCanvas from './components/WorkflowCanvas';
import NodePalette from './components/NodePalette';
import NodeConfigPanel from './components/NodeConfigPanel';
import { useWorkflowStore } from './store/workflowStore';
import './index.css';

function WorkflowBuilderApp() {
  const { workflowId, workflowTitle, workflowDescription, workflowStatus, lastUpdated, setWorkflow, getWorkflowData, updateNode } = useWorkflowStore();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState('draft');
  const [saveStatus, setSaveStatus] = useState(null); // 'saving', 'saved', 'error'
  const [lastSaved, setLastSaved] = useState(null);
  
  useEffect(() => {
    // Load workflow if editing
    const workflowData = window.u43WorkflowData;
    if (workflowData) {
      setWorkflow(workflowData);
      setTitle(workflowData.title || '');
      setDescription(workflowData.description || '');
      setStatus(workflowData.status || 'draft');
      if (workflowData.updated_at) {
        setLastSaved(workflowData.updated_at);
      } else if (workflowData.created_at) {
        setLastSaved(workflowData.created_at);
      }
    }
  }, [setWorkflow]);
  
  // Update store when title/description changes
  useEffect(() => {
    const { updateWorkflowTitle, updateWorkflowDescription, updateWorkflowStatus } = useWorkflowStore.getState();
    // Note: We'll handle this in the save function instead
  }, [title, description, status]);
  
  const handleSave = async () => {
    setSaveStatus('saving');
    
    const workflowData = getWorkflowData();
    
    // Update workflow metadata in store
    useWorkflowStore.setState({
      workflowTitle: title || workflowData.title,
      workflowDescription: description || workflowData.description,
      workflowStatus: status || workflowData.status,
    });
    
    // Get updated workflow data
    const updatedData = useWorkflowStore.getState().getWorkflowData();
    
    const formData = new FormData();
    formData.append('action', workflowId ? 'u43_update_workflow' : 'u43_create_workflow');
    formData.append('workflow_id', workflowId || '');
    formData.append('title', updatedData.title);
    formData.append('description', updatedData.description);
    formData.append('status', updatedData.status);
    formData.append('workflow_data', JSON.stringify({
      nodes: updatedData.nodes,
      edges: updatedData.edges,
    }));
    formData.append('u43_nonce', window.u43Nonce);
    
    try {
      const response = await fetch(window.u43AjaxUrl, {
        method: 'POST',
        body: formData,
      });
      
      const result = await response.json();
      if (result.success) {
        setSaveStatus('saved');
        const now = new Date().toISOString();
        setLastSaved(now);
        
        // Update workflow ID if it was a new workflow
        if (result.data?.id && !workflowId) {
          useWorkflowStore.setState({ workflowId: result.data.id });
          // Update URL without reload
          const newUrl = window.location.pathname + '?page=u43-builder&workflow_id=' + result.data.id;
          window.history.pushState({}, '', newUrl);
        }
        
        // Update last updated timestamp if provided
        if (result.data?.updated_at) {
          useWorkflowStore.setState({ lastUpdated: result.data.updated_at });
          setLastSaved(result.data.updated_at);
        }
        
        // Clear save status after 3 seconds
        setTimeout(() => {
          setSaveStatus(null);
        }, 3000);
      } else {
        setSaveStatus('error');
        alert('Error saving workflow: ' + (result.data?.message || 'Unknown error'));
        setTimeout(() => {
          setSaveStatus(null);
        }, 5000);
      }
    } catch (error) {
      setSaveStatus('error');
      alert('Error saving workflow: ' + error.message);
      setTimeout(() => {
        setSaveStatus(null);
      }, 5000);
    }
  };
  
  const handleCancel = () => {
    if (confirm('Are you sure you want to leave? Any unsaved changes will be lost.')) {
      window.location.href = window.u43AdminUrl + 'admin.php?page=u43';
    }
  };
  
  const formatTimestamp = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleString();
  };
  
  return (
    <div className="flex h-full bg-gray-50" style={{ height: '100%', width: '100%' }}>
      <NodePalette />
      
      <div className="flex-1 flex flex-col" style={{ minWidth: 0 }}>
        {/* Header */}
        <div className="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0">
          <div className="flex items-center justify-between mb-4">
            <div className="flex-1">
              <div className="flex items-center gap-3">
              <h1 className="text-xl font-semibold text-gray-900">
                {title || 'New Workflow'}
              </h1>
                {lastSaved && (
                  <span className="text-xs text-gray-500">
                    Last saved: {formatTimestamp(lastSaved)}
                  </span>
                )}
                {saveStatus === 'saving' && (
                  <span className="text-xs text-blue-600 flex items-center gap-1">
                    <span className="animate-spin">⏳</span> Saving...
                  </span>
                )}
                {saveStatus === 'saved' && (
                  <span className="text-xs text-green-600">✓ Saved</span>
                )}
                {saveStatus === 'error' && (
                  <span className="text-xs text-red-600">✗ Error</span>
                )}
              </div>
            </div>
            <div className="flex gap-2">
              <button
                onClick={handleCancel}
                className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
              >
                Cancel
              </button>
              <select
                value={status}
                onChange={(e) => setStatus(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="paused">Paused</option>
              </select>
              <button
                onClick={handleSave}
                disabled={saveStatus === 'saving'}
                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {saveStatus === 'saving' ? 'Saving...' : 'Save Workflow'}
              </button>
            </div>
          </div>
          <div>
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Workflow Title"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        
        {/* Canvas */}
        <div className="flex-1 relative">
          <WorkflowCanvas />
        </div>
      </div>
      
      <NodeConfigPanel />
    </div>
  );
}

// Initialize the app
const container = document.getElementById('u43-workflow-builder');
if (container) {
  const root = createRoot(container);
  root.render(
    <ReactFlowProvider>
      <WorkflowBuilderApp />
    </ReactFlowProvider>
  );
}

