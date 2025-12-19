import { useState, useCallback } from 'react';

/**
 * Hook to fetch and manage OpenAI models
 * 
 * @returns {Object} Object with openaiModels, loadingModels, and fetchOpenAIModels function
 */
export function useOpenAIModels() {
  const [openaiModels, setOpenaiModels] = useState([]);
  const [loadingModels, setLoadingModels] = useState(false);

  const fetchOpenAIModels = useCallback(async (currentConfig = null, setConfig = null) => {
    if (!window.u43RestUrl) {
      console.warn('REST URL not available');
      return;
    }
    
    try {
      setLoadingModels(true);
      const response = await fetch(`${window.u43RestUrl}openai/models`, {
        headers: {
          'X-WP-Nonce': window.u43RestNonce || '',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        setOpenaiModels(data.models || []);
        
        // Set default model if not set and models are available
        // Use setConfig callback to read current state instead of closure value
        // This prevents stale closure issues when async operation completes
        if (data.models && data.models.length > 0 && setConfig) {
          setConfig(prevConfig => {
            // If currentConfig was provided, use it for the check; otherwise use prevConfig
            const configToCheck = currentConfig || prevConfig;
            
            // Only set default if model is not already set
            if (!configToCheck.settings?.model) {
              const defaultModel = data.default_model || data.models[0].id;
              return {
                ...prevConfig,
                settings: {
                  ...prevConfig.settings,
                  model: defaultModel,
                  provider: prevConfig.settings?.provider || 'openai'
                }
              };
            }
            
            // Return unchanged config if model is already set
            return prevConfig;
          });
        }
      } else {
        console.warn('Failed to fetch OpenAI models:', response.status);
        // Set empty array on error
        setOpenaiModels([]);
      }
    } catch (error) {
      console.error('Error fetching OpenAI models:', error);
      setOpenaiModels([]);
    } finally {
      setLoadingModels(false);
    }
  }, []);

  return {
    openaiModels,
    loadingModels,
    fetchOpenAIModels
  };
}

