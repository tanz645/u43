import { useState, useCallback } from 'react';
import { useWorkflowStore } from '../../../store/workflowStore';

/**
 * Hook to fetch and manage tool configuration
 * 
 * @returns {Object} Object with toolConfig, loadingToolConfig, and fetchToolConfig function
 */
export function useToolConfig() {
  const { toolConfigs } = useWorkflowStore();
  const [toolConfig, setToolConfig] = useState(null);
  const [loadingToolConfig, setLoadingToolConfig] = useState(false);

  const fetchToolConfig = useCallback(async (toolId) => {
    if (!toolId) return;
    
    // First check cached tool configs from store (try both underscore and hyphen versions)
    let foundConfig = toolConfigs?.[toolId];
    if (!foundConfig) {
      const withUnderscores = toolId.replace(/-/g, '_');
      const withHyphens = toolId.replace(/_/g, '-');
      foundConfig = toolConfigs?.[withUnderscores] || toolConfigs?.[withHyphens];
    }
    
    if (foundConfig) {
      setToolConfig(foundConfig);
      return;
    }
    
    // If not in cache, try to fetch from API
    if (!window.u43RestUrl) {
      console.warn('REST URL not available');
      setToolConfig(null);
      return;
    }
    
    try {
      setLoadingToolConfig(true);
      
      // Try the specific tool endpoint
      const toolResponse = await fetch(`${window.u43RestUrl}tools/${toolId}`, {
        headers: {
          'X-WP-Nonce': window.u43RestNonce || '',
        },
      });
      
      if (toolResponse.ok) {
        const toolData = await toolResponse.json();
        setToolConfig(toolData);
      } else {
        console.warn(`Tool config not found for ${toolId}`, toolResponse.status);
        setToolConfig(null);
      }
    } catch (error) {
      console.error('Error fetching tool config:', error);
      setToolConfig(null);
    } finally {
      setLoadingToolConfig(false);
    }
  }, [toolConfigs]);

  return {
    toolConfig,
    loadingToolConfig,
    fetchToolConfig,
    setToolConfig
  };
}

