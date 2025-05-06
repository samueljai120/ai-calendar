/**
 * AI Calendar - Block Editor Enhancements
 * 
 * This script adds functionality to the event editor page for handling
 * full day events and time field visibility.
 */

(function() {
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded');
        
        // Find the full day checkbox
        const fullDayCheckbox = document.querySelector('input[name="_event_is_full_day"], input[name="all_day"]');
        
        if (fullDayCheckbox) {
            console.log('Found checkbox, initial state:', fullDayCheckbox.checked);
            
            // Find time field containers
            const timeFields = document.querySelectorAll('.time-field, .components-datetime__time, [id*="event_start_time"], [id*="event_end_time"]');
            
            // Initial toggle based on checkbox state
            toggleTimeFields(fullDayCheckbox.checked);
            
            // Add event listener to checkbox
            fullDayCheckbox.addEventListener('change', function() {
                console.log('Checkbox changed:', this.checked);
                toggleTimeFields(this.checked);
            });
            
            // Also check for changes when block editor updates
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'attributes') {
                        const currentState = fullDayCheckbox.checked;
                        toggleTimeFields(currentState);
                    }
                });
            });
            
            // Observe changes to the editor container
            const editorContainer = document.querySelector('.block-editor-block-list__layout, .edit-post-layout');
            if (editorContainer) {
                observer.observe(editorContainer, { 
                    childList: true, 
                    subtree: true,
                    attributes: true
                });
            }
        }
        
        // Function to handle time fields visibility
        function toggleTimeFields(isFullDay) {
            console.log('Toggle time fields:', isFullDay);
            
            // Get all time-related fields
            const timeFields = document.querySelectorAll('.time-field, .components-datetime__time, [id*="event_start_time"], [id*="event_end_time"]');
            
            // Toggle visibility
            timeFields.forEach(function(field) {
                // Get the container - either the field itself or a parent element
                let container = field;
                
                // Try to find parent container if it's a block editor field
                if (field.closest('.components-panel__row')) {
                    container = field.closest('.components-panel__row');
                } else if (field.closest('.components-base-control')) {
                    container = field.closest('.components-base-control');
                } else if (field.closest('tr')) {
                    container = field.closest('tr');
                }
                
                // Toggle visibility
                if (isFullDay) {
                    // Hide time fields for full day events
                    container.style.display = 'none';
                    
                    // If using classic editor, also clear the values
                    if (field.tagName === 'INPUT') {
                        field.value = '';
                    }
                } else {
                    // Show time fields for regular events
                    container.style.display = '';
                    
                    // If using classic editor, set default values if empty
                    if (field.tagName === 'INPUT' && field.value === '') {
                        if (field.id.includes('start_time')) {
                            field.value = '09:00';
                        } else if (field.id.includes('end_time')) {
                            field.value = '17:00';
                        }
                    }
                }
            });
        }
        
        // Intercept save errors and provide better handling
        if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
            // Listen for API fetch errors
            const originalFetch = window.fetch;
            window.fetch = function(url, options) {
                // Only intercept requests to our REST endpoint
                if (url && url.toString().includes('/wp/v2/ai_calendar_event')) {
                    console.log('Intercepting REST API call:', url);
                    
                    // Call the original fetch
                    return originalFetch(url, options)
                        .then(response => {
                            if (!response.ok) {
                                console.error('REST API error:', response.status, response.statusText);
                                // Log the response for debugging
                                response.clone().text().then(text => {
                                    console.error('Error response:', text);
                                });
                            }
                            return response;
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            throw error;
                        });
                }
                
                // For other requests, just pass through
                return originalFetch(url, options);
            };
            
            // Listen for save errors
            let hasSaveError = false;
            wp.data.subscribe(() => {
                try {
                    const coreStore = wp.data.select('core');
                    const editorStore = wp.data.select('core/editor');
                    
                    if (!coreStore || !editorStore) return;
                    
                    // Check for saving activity
                    const isSaving = editorStore.isSavingPost();
                    const wasSuccessful = !editorStore.getLastPostError();
                    
                    if (isSaving) {
                        hasSaveError = false;
                    } else if (!isSaving && !wasSuccessful && !hasSaveError) {
                        hasSaveError = true;
                        const error = editorStore.getLastPostError();
                        
                        console.error('Save error detected:', error);
                        
                        // Show a more helpful error message
                        if (error && error.message) {
                            // Create a custom notice
                            wp.data.dispatch('core/notices').createErrorNotice(
                                'Error saving event: ' + error.message + '. Please check the browser console for more details.',
                                { id: 'save-error-notice', isDismissible: true }
                            );
                        }
                    }
                } catch (err) {
                    console.error('Error in save error handler:', err);
                }
            });
        }
    });
})(); 