import React from 'react';
import WhatsAppFilterConfig from '../integrations/WhatsApp/WhatsAppFilterConfig';

/**
 * Trigger Node Configuration Component
 * 
 * Handles configuration for trigger nodes including:
 * - Trigger type selection
 * - Filter configuration (for WhatsApp triggers)
 */
export default function TriggerNodeConfig({ config, setConfig, triggerConfigs }) {
  return (
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Trigger Type <span className="text-red-500">*</span>
        </label>
        <input
          type="text"
          value={config.trigger_type || 'wordpress_comment_post'}
          onChange={(e) => setConfig({ ...config, trigger_type: e.target.value })}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="wordpress_comment_post"
        />
        <p className="text-xs text-gray-500 mt-1">
          The trigger ID that this node listens to
        </p>
      </div>
      
      {/* Filter configuration for WhatsApp trigger */}
      {(config.trigger_type === 'whatsapp_message_received' || config.trigger_type === 'whatsapp-message-received') && (
        <WhatsAppFilterConfig
          config={config}
          setConfig={setConfig}
          triggerConfigs={triggerConfigs}
        />
      )}
    </div>
  );
}

