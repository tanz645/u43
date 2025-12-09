/**
 * Icon Components and Mappings
 * 
 * Centralized icon components and mappings for workflow nodes
 * 
 * @package U43
 */

import React from 'react';

/**
 * WhatsApp Icon Component
 */
export const WhatsAppIcon = ({ className = "w-5 h-5" }) => (
  <svg 
    className={className} 
    viewBox="0 0 24 24" 
    fill="currentColor"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
  </svg>
);

/**
 * WordPress Icon Component
 * Official WordPress logo
 */
export const WordPressIcon = ({ className = "w-5 h-5" }) => (
  <svg 
    className={className} 
    viewBox="0 0 560 400" 
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <g fill="#21759b" fillRule="nonzero" transform="matrix(2.44844 0 0 2.44844 130 50.0049)">
      <path d="m8.708 61.26c0 20.802 12.089 38.779 29.619 47.298l-25.069-68.686c-2.916 6.536-4.55 13.769-4.55 21.388z"/>
      <path d="m96.74 58.608c0-6.495-2.333-10.993-4.334-14.494-2.664-4.329-5.161-7.995-5.161-12.324 0-4.831 3.664-9.328 8.825-9.328.233 0 .454.029.681.042-9.35-8.566-21.807-13.796-35.489-13.796-18.36 0-34.513 9.42-43.91 23.688 1.233.037 2.395.063 3.382.063 5.497 0 14.006-.667 14.006-.667 2.833-.167 3.167 3.994.337 4.329 0 0-2.847.335-6.015.501l19.138 56.925 11.501-34.493-8.188-22.434c-2.83-.166-5.511-.501-5.511-.501-2.832-.166-2.5-4.496.332-4.329 0 0 8.679.667 13.843.667 5.496 0 14.006-.667 14.006-.667 2.835-.167 3.168 3.994.337 4.329 0 0-2.853.335-6.015.501l18.992 56.494 5.242-17.517c2.272-7.269 4.001-12.49 4.001-16.989z"/>
      <path d="m62.184 65.857-15.768 45.819c4.708 1.384 9.687 2.141 14.846 2.141 6.12 0 11.989-1.058 17.452-2.979-.141-.225-.269-.464-.374-.724z"/>
      <path d="m107.376 36.046c.226 1.674.354 3.471.354 5.404 0 5.333-.996 11.328-3.996 18.824l-16.053 46.413c15.624-9.111 26.133-26.038 26.133-45.426.001-9.137-2.333-17.729-6.438-25.215z"/>
      <path d="m61.262 0c-33.779 0-61.262 27.481-61.262 61.26 0 33.783 27.483 61.263 61.262 61.263 33.778 0 61.265-27.48 61.265-61.263-.001-33.779-27.487-61.26-61.265-61.26zm0 119.715c-32.23 0-58.453-26.223-58.453-58.455 0-32.23 26.222-58.451 58.453-58.451 32.229 0 58.45 26.221 58.45 58.451 0 32.232-26.221 58.455-58.45 58.455z"/>
    </g>
  </svg>
);

/**
 * WooCommerce Icon Component
 * WooCommerce logo (simplified version)
 */
export const WooCommerceIcon = ({ className = "w-5 h-5" }) => (
  <svg 
    className={className} 
    viewBox="0 0 24 24" 
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="#96588a"/>
    <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="#96588a"/>
    <circle cx="12" cy="12" r="2" fill="#96588a"/>
  </svg>
);

/**
 * Icon mapping for API icon strings to emojis/components
 * 
 * Special markers (like 'whatsapp-icon') indicate custom React components
 * Regular strings are emoji characters
 */
export const iconMap = {
  // WordPress icons
  'comment': '💬',
  'trash': '🗑️',
  'spam': '🚫',
  'approve': '✅',
  'delete': '🗑️',
  
  // AI/Agent icons
  'brain': '🤖',
  'agent': '🤖',
  
  // Communication icons
  'email': '📧',
  'whatsapp': 'whatsapp-icon', // Custom SVG component
  'woocommerce': 'woocommerce-icon', // Custom SVG component
  
  // Action/Tool icons
  'check': '✓',
  'action': '⚙️',
  'tool': '🔧',
  
  // Default icons
  'trigger': '⚡',
  'default': '⚡',
};

/**
 * Render icon helper function
 * 
 * Determines whether to render a custom React component or an emoji
 * 
 * @param {string} icon - Icon identifier from iconMap
 * @param {string} category - Category of the node (for category-specific icons)
 * @returns {React.ReactElement} - Rendered icon component or emoji span
 */
export const renderIcon = (icon, category = null) => {
  // Handle category-specific icons (check category first for better organization)
  if (category === 'WhatsApp' || icon === 'whatsapp-icon') {
    return <WhatsAppIcon className="w-5 h-5 text-green-600" />;
  }
  
  if (category === 'WordPress' || category === 'wordpress') {
    return <WordPressIcon className="w-7 h-7 text-blue-600" style={{ minWidth: '28px', minHeight: '28px' }} />;
  }
  
  if (category === 'WooCommerce' || category === 'woocommerce' || icon === 'woocommerce-icon') {
    return <WooCommerceIcon className="w-7 h-7 text-purple-600" style={{ minWidth: '28px', minHeight: '28px' }} />;
  }
  
  // Handle emoji icons
  return <span className="text-lg">{icon}</span>;
};

/**
 * Get icon by key
 * 
 * @param {string} key - Icon key from iconMap
 * @returns {string|string} - Icon value (emoji or component marker)
 */
export const getIcon = (key) => {
  return iconMap[key] || iconMap.default;
};

