import React from 'react';

/**
 * WhatsApp Button Input Component
 * 
 * Handles configuration of WhatsApp button messages
 * Supports up to 3 buttons with ID and title
 */
export default function WhatsAppButtonInput({ 
  inputKey, 
  inputConfig, 
  inputValue, 
  updateInputValue, 
  renderLabel, 
  renderDescription 
}) {
  const buttons = Array.isArray(inputValue) ? inputValue : [];
  const buttonColors = ['#3B82F6', '#10B981', '#F59E0B']; // Blue, Green, Orange

  return (
    <div>
      {renderLabel()}
      <span className="text-xs text-gray-500 ml-2">(Max 3 buttons)</span>
      
      <div className="space-y-2 mb-2">
        {buttons.map((button, index) => (
          <div key={index} className="flex items-center gap-2 p-2 border border-gray-300 rounded-md bg-gray-50">
            <div 
              className="w-4 h-4 rounded border-2 border-gray-300"
              style={{ backgroundColor: button.color || buttonColors[index] || '#3B82F6' }}
              title={`Button ${index + 1} color`}
            />
            <div className="flex-1">
              <input
                type="text"
                value={button.id || ''}
                onChange={(e) => {
                  const newButtons = [...buttons];
                  newButtons[index] = { ...newButtons[index], id: e.target.value };
                  updateInputValue(newButtons);
                }}
                placeholder="Button ID (e.g., btn1)"
                className="w-full px-2 py-1 text-xs border border-gray-300 rounded mb-1"
              />
              <input
                type="text"
                value={button.title || ''}
                onChange={(e) => {
                  const newButtons = [...buttons];
                  newButtons[index] = { ...newButtons[index], title: e.target.value };
                  updateInputValue(newButtons);
                }}
                placeholder="Button Title"
                className="w-full px-2 py-1 text-xs border border-gray-300 rounded"
              />
            </div>
            <button
              type="button"
              onClick={() => {
                const newButtons = buttons.filter((_, i) => i !== index);
                updateInputValue(newButtons);
              }}
              className="text-red-500 hover:text-red-700 text-sm"
              title="Remove button"
            >
              ✕
            </button>
          </div>
        ))}
      </div>
      
      {buttons.length < 3 && (
        <button
          type="button"
          onClick={() => {
            const newButtons = [...buttons, { 
              id: `btn${buttons.length + 1}`, 
              title: '', 
              type: 'reply', 
              color: buttonColors[buttons.length] || '#3B82F6' 
            }];
            updateInputValue(newButtons);
          }}
          className="text-sm text-blue-600 hover:text-blue-800 mb-2"
        >
          + Add Button
        </button>
      )}
      
      {renderDescription()}
    </div>
  );
}

