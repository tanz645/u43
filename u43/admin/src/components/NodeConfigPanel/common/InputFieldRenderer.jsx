import React, { useRef } from 'react';
import VariableSuggestions from './VariableSuggestions';
import { insertVariableAtCursor } from '../utils/variableHelpers';
import { isHiddenInput } from '../utils/inputHelpers';
import WhatsAppButtonInput from '../integrations/WhatsApp/WhatsAppButtonInput';
import PhoneNumberVariableInput from '../integrations/WhatsApp/PhoneNumberVariableInput';
import PhoneNumbersArrayInput from '../integrations/WhatsApp/PhoneNumbersArrayInput';
import KeyValuePairsInput from '../integrations/HTTP/KeyValuePairsInput';

/**
 * InputFieldRenderer Component
 * 
 * Renders input fields based on their configuration and type
 * 
 * @param {Object} props - Component props
 * @param {string} inputKey - The input key/name
 * @param {Object} inputConfig - Input configuration from tool config
 * @param {*} inputValue - Current input value
 * @param {Object} config - Full node config
 * @param {Function} setConfig - Config update function
 * @param {Object} toolConfig - Tool configuration
 * @param {Object} variableProps - Props for variable suggestions
 * @param {React.Ref} lastFocusedTextareaRef - Ref for tracking focused textarea
 * @param {React.Ref} lastFocusedTextareaInputKeyRef - Ref for tracking textarea input key
 * @param {React.Ref} lastFocusedInputRef - Ref for tracking focused input (for key-value pairs)
 * @param {React.Ref} lastFocusedInputInfoRef - Ref for tracking input info (for key-value pairs)
 */
export default function InputFieldRenderer({
  inputKey,
  inputConfig,
  inputValue,
  config,
  setConfig,
  toolConfig,
  variableProps = {},
  lastFocusedTextareaRef = null,
  lastFocusedTextareaInputKeyRef = null,
  lastFocusedInputRef = null,
  lastFocusedInputInfoRef = null,
  connectedSourceNodes = []
}) {
  // Skip hidden inputs
  if (isHiddenInput(inputKey)) {
    return null;
  }

  // Helper to update input value
  const updateInputValue = (newValue) => {
    setConfig({
      ...config,
      inputs: {
        ...(config.inputs || {}),
        [inputKey]: newValue,
      },
    });
  };

  // Render label
  const renderLabel = () => (
    <label className="block text-sm font-medium text-gray-700 mb-1">
      {inputConfig.label || inputKey}
      {inputConfig.required && <span className="text-red-500 ml-1">*</span>}
    </label>
  );

  // Render description
  const renderDescription = () => {
    if (!inputConfig.description) return null;
    return <p className="text-xs text-gray-500 mt-1">{inputConfig.description}</p>;
  };

  // Special handling for WhatsApp buttons
  if (inputKey === 'buttons' && toolConfig?.id === 'whatsapp_send_button_message') {
    return <WhatsAppButtonInput
      inputKey={inputKey}
      inputConfig={inputConfig}
      inputValue={inputValue}
      updateInputValue={updateInputValue}
      renderLabel={renderLabel}
      renderDescription={renderDescription}
    />;
  }

  // Special handling for phone_number_variable (WhatsApp)
  if (inputKey === 'phone_number_variable') {
    return <PhoneNumberVariableInput
      inputKey={inputKey}
      inputConfig={inputConfig}
      inputValue={inputValue}
      updateInputValue={updateInputValue}
      config={config}
      setConfig={setConfig}
      variableProps={variableProps}
      renderLabel={renderLabel}
      renderDescription={renderDescription}
    />;
  }

  // Special handling for message/body_text
  if (inputKey === 'message' || inputKey === 'body_text') {
    return <MessageTextareaInput
      inputKey={inputKey}
      inputConfig={inputConfig}
      inputValue={inputValue}
      updateInputValue={updateInputValue}
      config={config}
      setConfig={setConfig}
      variableProps={variableProps}
      lastFocusedTextareaRef={lastFocusedTextareaRef}
      lastFocusedTextareaInputKeyRef={lastFocusedTextareaInputKeyRef}
      renderLabel={renderLabel}
      renderDescription={renderDescription}
    />;
  }

  // Handle array type inputs
  if (inputConfig.type === 'array' || (Array.isArray(inputConfig.type) && inputConfig.type.includes('array'))) {
    // Special handling for phone_numbers (WhatsApp)
    if (inputKey === 'phone_numbers') {
      return <PhoneNumbersArrayInput
        inputKey={inputKey}
        inputConfig={inputConfig}
        inputValue={inputValue}
        updateInputValue={updateInputValue}
        renderLabel={renderLabel}
        renderDescription={renderDescription}
      />;
    }
    
    return <GenericArrayInput
      inputKey={inputKey}
      inputConfig={inputConfig}
      inputValue={inputValue}
      updateInputValue={updateInputValue}
      renderLabel={renderLabel}
      renderDescription={renderDescription}
    />;
  }

  // Handle select/dropdown inputs
  if (inputConfig.options) {
    return <SelectInput
      inputKey={inputKey}
      inputConfig={inputConfig}
      inputValue={inputValue}
      updateInputValue={updateInputValue}
      renderLabel={renderLabel}
      renderDescription={renderDescription}
    />;
  }

  // Handle object type inputs
  if (inputConfig.type === 'object') {
    // Special handling for HTTP tool key-value pairs
    if (inputKey === 'url_params' || inputKey === 'query_params' || inputKey === 'headers' || inputKey === 'body') {
      return <KeyValuePairsInput
        inputKey={inputKey}
        inputConfig={inputConfig}
        inputValue={inputValue}
        updateInputValue={updateInputValue}
        config={config}
        setConfig={setConfig}
        variableProps={variableProps}
        lastFocusedInputRef={lastFocusedInputRef}
        lastFocusedInputInfoRef={lastFocusedInputInfoRef}
        renderLabel={renderLabel}
        renderDescription={renderDescription}
        triggerNodes={variableProps.triggerNodes}
        triggerConfigs={variableProps.triggerConfigs}
        connectedSourceNodes={variableProps.connectedSourceNodes}
      />;
    }
    
    return <ObjectTextareaInput
      inputKey={inputKey}
      inputConfig={inputConfig}
      inputValue={inputValue}
      updateInputValue={updateInputValue}
      renderLabel={renderLabel}
      renderDescription={renderDescription}
    />;
  }

  // Default: text/string input
  return <TextInput
    inputKey={inputKey}
    inputConfig={inputConfig}
    inputValue={inputValue}
    updateInputValue={updateInputValue}
    renderLabel={renderLabel}
    renderDescription={renderDescription}
  />;
}

// ============================================================================
// Individual Input Type Components
// ============================================================================

/**
 * Message/Textarea Input Component with Variables
 */
function MessageTextareaInput({ inputKey, inputConfig, inputValue, updateInputValue, config, setConfig, variableProps, lastFocusedTextareaRef, lastFocusedTextareaInputKeyRef, renderLabel, renderDescription }) {
  return (
    <div>
      {renderLabel()}
      
      <VariableSuggestions
        {...variableProps}
        onInsertVariable={(variable) => {
          const textarea = lastFocusedTextareaRef?.current;
          const storedInputKey = lastFocusedTextareaInputKeyRef?.current;
          
          if (textarea && storedInputKey === inputKey) {
            const newValue = insertVariableAtCursor(textarea, variable);
            updateInputValue(newValue);
          }
        }}
        title="Available Variables - Click to insert:"
      />
      
      <textarea
        ref={(el) => {
          if (el && lastFocusedTextareaRef) {
            lastFocusedTextareaRef.current = el;
            if (lastFocusedTextareaInputKeyRef) {
              lastFocusedTextareaInputKeyRef.current = inputKey;
            }
          }
        }}
        onFocus={(e) => {
          if (lastFocusedTextareaRef) {
            lastFocusedTextareaRef.current = e.target;
            if (lastFocusedTextareaInputKeyRef) {
              lastFocusedTextareaInputKeyRef.current = inputKey;
            }
          }
        }}
        value={inputValue}
        onChange={(e) => updateInputValue(e.target.value)}
        placeholder={inputConfig.description || `Enter ${inputKey} (use {{variables}} from previous nodes)`}
        rows={6}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
      />
      {renderDescription()}
    </div>
  );
}


/**
 * Generic Array Input (JSON format)
 */
function GenericArrayInput({ inputKey, inputConfig, inputValue, updateInputValue, renderLabel, renderDescription }) {
  return (
    <div>
      {renderLabel()}
      <textarea
        value={Array.isArray(inputValue) ? JSON.stringify(inputValue, null, 2) : (inputValue || '[]')}
        onChange={(e) => {
          try {
            const parsed = JSON.parse(e.target.value);
            updateInputValue(Array.isArray(parsed) ? parsed : [parsed]);
          } catch {
            updateInputValue(e.target.value);
          }
        }}
        placeholder={inputConfig.description || `Enter ${inputKey} as JSON array`}
        rows={3}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-xs"
      />
      <p className="text-xs text-gray-500 mt-1">
        {inputConfig.description || `Array of ${inputKey}`}
      </p>
    </div>
  );
}

/**
 * Select/Dropdown Input Component
 */
function SelectInput({ inputKey, inputConfig, inputValue, updateInputValue, renderLabel, renderDescription }) {
  return (
    <div>
      {renderLabel()}
      <select
        value={inputValue}
        onChange={(e) => updateInputValue(e.target.value)}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        {Object.entries(inputConfig.options).map(([optValue, optLabel]) => (
          <option key={optValue} value={optValue}>{optLabel}</option>
        ))}
      </select>
      {renderDescription()}
    </div>
  );
}


/**
 * Object Textarea Input (JSON format)
 */
function ObjectTextareaInput({ inputKey, inputConfig, inputValue, updateInputValue, renderLabel, renderDescription }) {
  const objectValue = typeof inputValue === 'object' && inputValue !== null ? inputValue : {};
  const objectString = JSON.stringify(objectValue, null, 2);

  return (
    <div>
      {renderLabel()}
      <textarea
        value={objectString}
        onChange={(e) => {
          try {
            const parsed = JSON.parse(e.target.value);
            updateInputValue(parsed);
          } catch {
            updateInputValue(e.target.value);
          }
        }}
        placeholder={inputKey === 'output_schema' ? '{"status": "string", "data": {"id": "number"}}' : '{"key": "value"}'}
        rows={inputKey === 'output_schema' ? 8 : 4}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-xs"
      />
      {renderDescription()}
    </div>
  );
}

/**
 * Text Input Component (default)
 */
function TextInput({ inputKey, inputConfig, inputValue, updateInputValue, renderLabel, renderDescription }) {
  return (
    <div>
      {renderLabel()}
      <input
        type="text"
        value={inputValue}
        onChange={(e) => updateInputValue(e.target.value)}
        placeholder={inputConfig.description || `Enter ${inputKey}`}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      {renderDescription()}
    </div>
  );
}

