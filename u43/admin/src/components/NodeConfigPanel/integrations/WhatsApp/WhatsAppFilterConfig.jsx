import React from 'react';

/**
 * WhatsApp Filter Configuration Component
 * 
 * Handles filter configuration for WhatsApp message triggers
 * Allows filtering messages based on field content
 */
export default function WhatsAppFilterConfig({ config, setConfig, triggerConfigs }) {
  return (
    <div className="border-t pt-4 space-y-4">
      <div className="flex items-center gap-2">
        <input
          type="checkbox"
          id="filter-enabled"
          checked={config.filter?.enabled || false}
          onChange={(e) => {
            setConfig({
              ...config,
              filter: {
                ...config.filter,
                enabled: e.target.checked,
                field: config.filter?.field || 'message_text',
                match_type: config.filter?.match_type || 'exact',
                value: config.filter?.value || ''
              }
            });
          }}
          className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
        />
        <label htmlFor="filter-enabled" className="text-sm font-medium text-gray-700">
          Enable Message Filtering
        </label>
      </div>
      <p className="text-xs text-gray-500 -mt-2 ml-6">
        Filter incoming messages based on field content. If disabled, all messages will trigger this workflow.
      </p>
      
      {config.filter?.enabled && (
        <div className="ml-6 space-y-3 bg-gray-50 p-3 rounded-md">
          {/* Field selection */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Field to Compare
            </label>
            <select
              value={config.filter?.field || 'message_text'}
              onChange={(e) => {
                setConfig({
                  ...config,
                  filter: {
                    ...config.filter,
                    field: e.target.value
                  }
                });
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {(triggerConfigs['whatsapp_message_received'] || triggerConfigs['whatsapp-message-received'])?.outputs && Object.entries((triggerConfigs['whatsapp_message_received'] || triggerConfigs['whatsapp-message-received']).outputs).map(([key, output]) => (
                <option key={key} value={key}>
                  {output.label || key}
                </option>
              ))}
            </select>
            <p className="text-xs text-gray-500 mt-1">
              Select which field to compare against
            </p>
          </div>
          
          {/* Match type */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Match Type
            </label>
            <select
              value={config.filter?.match_type || 'exact'}
              onChange={(e) => {
                setConfig({
                  ...config,
                  filter: {
                    ...config.filter,
                    match_type: e.target.value
                  }
                });
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="exact">Exact Match</option>
              <option value="contains">Contains Substring</option>
            </select>
            <p className="text-xs text-gray-500 mt-1">
              Choose how to match the value
            </p>
          </div>
          
          {/* Value to match */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Value to Match
            </label>
            <input
              type="text"
              value={config.filter?.value || ''}
              onChange={(e) => {
                setConfig({
                  ...config,
                  filter: {
                    ...config.filter,
                    value: e.target.value
                  }
                });
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter value to match"
            />
            <p className="text-xs text-gray-500 mt-1">
              Enter the value to match against the selected field
            </p>
          </div>
        </div>
      )}
    </div>
  );
}

