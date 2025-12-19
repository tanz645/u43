import React from 'react';

/**
 * Phone Numbers Array Input Component
 * 
 * WhatsApp-specific input for multiple phone numbers
 * Accepts comma-separated phone numbers
 */
export default function PhoneNumbersArrayInput({ 
  inputKey, 
  inputConfig, 
  inputValue, 
  updateInputValue, 
  renderLabel, 
  renderDescription 
}) {
  const phoneArray = Array.isArray(inputValue) ? inputValue : (inputValue ? [inputValue] : []);
  const phoneString = phoneArray.join(', ');

  return (
    <div>
      {renderLabel()}
      <textarea
        value={phoneString}
        onChange={(e) => {
          const phones = e.target.value
            .split(',')
            .map(p => p.trim())
            .filter(p => p.length > 0);
          updateInputValue(phones);
        }}
        placeholder="Enter phone numbers separated by commas (e.g., 8801345318757, +1234567890)"
        rows={3}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <p className="text-xs text-gray-500 mt-1">
        {inputConfig.description || `Enter phone numbers separated by commas`}
      </p>
    </div>
  );
}

