import React from 'react';
import VariableSuggestions from '../../common/VariableSuggestions';

/**
 * Phone Number Variable Input Component
 * 
 * WhatsApp-specific input for phone number variables
 * Supports variable insertion from connected nodes
 */
export default function PhoneNumberVariableInput({ 
  inputKey, 
  inputConfig, 
  inputValue, 
  updateInputValue, 
  config, 
  setConfig, 
  variableProps, 
  renderLabel, 
  renderDescription 
}) {
  return (
    <div>
      {renderLabel()}
      
      <VariableSuggestions
        {...variableProps}
        insertionConfig={{
          mode: 'input',
          inputKey: inputKey,
          setConfig,
          config
        }}
        title="Available Variables - Click to insert:"
      />
      
      <input
        type="text"
        data-input-key={inputKey}
        value={inputValue}
        onChange={(e) => updateInputValue(e.target.value)}
        placeholder="e.g., {{trigger_data.phone}} or {{node_id.phone_number}}"
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
      />
      {renderDescription()}
    </div>
  );
}

