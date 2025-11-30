/**
 * Remove All Data Script
 * Handles confirmation dialog and browser storage cleanup
 *
 * @package U43
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle click on "Remove All Data" link
        $('.u43-remove-all-data').on('click', function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const url = $link.attr('href');
            
            // Show custom confirmation dialog
            const confirmed = confirm(
                u43RemoveData.confirmMessage + '\n\n' +
                'This will delete:\n' +
                '- All workflows\n' +
                '- All execution logs\n' +
                '- All credentials and settings\n' +
                '- All cached data\n\n' +
                'This action cannot be undone!'
            );
            
            if (confirmed) {
                // Clean browser storage before redirecting
                cleanupBrowserStorage();
                
                // Redirect to confirmation page
                window.location.href = url;
            }
            
            return false;
        });
        
        // If we're on the confirmation page and user confirms, clean storage
        if ($('.u43-remove-data-confirmation').length > 0) {
            $('.u43-remove-data-confirmation a.button-primary').on('click', function(e) {
                cleanupBrowserStorage();
            });
        }
    });
    
    /**
     * Clean up browser storage
     */
    function cleanupBrowserStorage() {
        try {
            // Clear localStorage items related to U43
            if (typeof Storage !== 'undefined') {
                const keysToRemove = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && (key.indexOf('u43') !== -1 || key.indexOf('workflow') !== -1)) {
                        keysToRemove.push(key);
                    }
                }
                keysToRemove.forEach(function(key) {
                    localStorage.removeItem(key);
                });
                
                // Clear sessionStorage items related to U43
                const sessionKeysToRemove = [];
                for (let i = 0; i < sessionStorage.length; i++) {
                    const key = sessionStorage.key(i);
                    if (key && (key.indexOf('u43') !== -1 || key.indexOf('workflow') !== -1)) {
                        sessionKeysToRemove.push(key);
                    }
                }
                sessionKeysToRemove.forEach(function(key) {
                    sessionStorage.removeItem(key);
                });
            }
            
            // Clear IndexedDB if available (for React Flow or other libraries)
            if ('indexedDB' in window) {
                // Try to delete databases that might contain U43 data
                const dbNames = ['workflow', 'u43', 'reactflow'];
                dbNames.forEach(function(dbName) {
                    try {
                        indexedDB.deleteDatabase(dbName);
                    } catch (e) {
                        console.log('Could not delete IndexedDB:', dbName, e);
                    }
                });
            }
            
            console.log('U43: Browser storage cleaned');
        } catch (e) {
            console.error('U43: Error cleaning browser storage:', e);
        }
    }
    
})(jQuery);

