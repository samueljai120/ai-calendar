/**
 * Block Editor Direct Injection for AI Calendar
 * This script directly targets the block editor UI to add the full day checkbox
 */

(function() {
    'use strict';
    
    // Run when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('AI Calendar: Direct injection script loaded');
        
        // Start watching for the event panel to appear
        startObservingDOM();
    });
    
    /**
     * Start observing DOM changes to find when the event panel appears
     */
    function startObservingDOM() {
        // Look for the event panel immediately and then set up an observer
        checkForEventPanel();
        
        // Create a mutation observer to watch for changes to the DOM
        const observer = new MutationObserver(function(mutations) {
            checkForEventPanel();
        });
        
        // Start observing the body for child changes
        observer.observe(document.body, { 
            childList: true, 
            subtree: true 
        });
        
        // Also set a timer to check periodically
        setInterval(checkForEventPanel, 1000);
    }
    
    /**
     * Check if the event panel exists and add our checkbox if needed
     */
    function checkForEventPanel() {
        // Only proceed on event post type pages
        if (!document.body.classList.contains('post-type-ai_calendar_event') && 
            !document.title.includes('Event')) {
            return;
        }
        
        // Look for the time inputs by matching their pattern
        const timeInputs = Array.from(document.querySelectorAll('input[type="time"]'));
        if (timeInputs.length < 2) {
            return; // Need both start and end time inputs
        }
        
        // Find the event details panel container
        const parentPanel = timeInputs[0].closest('.components-panel__body');
        if (!parentPanel) {
            return;
        }
        
        // Check if we've already added our checkbox
        if (document.getElementById('ai-calendar-full-day-checkbox')) {
            return;
        }
        
        console.log('AI Calendar: Found event panel with time inputs', timeInputs);
        
        // Find the right position to inject our checkbox
        // We want to place it between the date pickers and time pickers
        const firstTimeWrapper = timeInputs[0].closest('.components-base-control');
        if (!firstTimeWrapper) {
            return;
        }
        
        // Create the full day checkbox element
        const fullDayContainer = document.createElement('div');
        fullDayContainer.className = 'components-base-control ai-calendar-full-day-control';
        fullDayContainer.style.cssText = 'padding: 10px; margin: 10px 0; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;';
        
        fullDayContainer.innerHTML = `
            <div class="components-base-control__field" style="display: flex; align-items: flex-start;">
                <input
                    type="checkbox"
                    id="ai-calendar-full-day-checkbox"
                    class="components-checkbox-control__input"
                    style="margin-top: 3px; margin-right: 10px;"
                >
                <div>
                    <label for="ai-calendar-full-day-checkbox" class="components-checkbox-control__label" style="font-weight: bold; display: block; margin-bottom: 4px;">
                        Full Day Event
                    </label>
                    <p class="components-base-control__help" style="margin-top: 0; font-size: 12px; color: #757575;">
                        Check this box for events that last the entire day without specific start/end times.
                    </p>
                </div>
            </div>
        `;
        
        // Insert before the first time input
        firstTimeWrapper.parentNode.insertBefore(fullDayContainer, firstTimeWrapper);
        
        // Now add the event listener to the checkbox
        const checkbox = document.getElementById('ai-calendar-full-day-checkbox');
        if (checkbox) {
            // First update the checkbox state based on the current values
            updateCheckboxFromTimeFields(checkbox, timeInputs);
            
            // Then add the change listener
            checkbox.addEventListener('change', function() {
                handleFullDayChange(this.checked, timeInputs);
            });
            
            // Create a hidden field to store the full day status
            createHiddenField();
            
            console.log('AI Calendar: Successfully added full day checkbox');
        }
    }
    
    /**
     * Update the checkbox state based on the time fields
     */
    function updateCheckboxFromTimeFields(checkbox, timeInputs) {
        // If both time fields are empty, check the box
        const allEmpty = timeInputs.every(input => !input.value || input.value === '00:00');
        
        console.log('AI Calendar: Checking if all time fields are empty:', allEmpty);
        console.log('AI Calendar: Time values:', timeInputs.map(input => input.value));
        
        // Also check for existing hidden field value
        const hiddenField = document.querySelector('input[name="_event_is_full_day"]');
        const existingValue = hiddenField ? hiddenField.value === '1' : false;
        
        checkbox.checked = allEmpty || existingValue;
        
        // If checkbox is checked, hide the time fields
        if (checkbox.checked) {
            handleFullDayChange(true, timeInputs);
        }
    }
    
    /**
     * Handle when the full day checkbox changes
     */
    function handleFullDayChange(isFullDay, timeInputs) {
        console.log('AI Calendar: Full day changed to', isFullDay);
        
        // Store original values in data attributes
        timeInputs.forEach(input => {
            if (!input.dataset.originalValue && input.value) {
                input.dataset.originalValue = input.value;
            }
        });
        
        // Handle the time inputs
        timeInputs.forEach(input => {
            const wrapper = input.closest('.components-base-control');
            
            if (isFullDay) {
                // Hide and disable time inputs for full day events
                if (wrapper) {
                    wrapper.style.display = 'none';
                }
                input.disabled = true;
                input.value = ''; // Clear the value
            } else {
                // Show and enable time inputs
                if (wrapper) {
                    wrapper.style.display = '';
                }
                input.disabled = false;
                
                // Restore original value if available
                if (input.dataset.originalValue) {
                    input.value = input.dataset.originalValue;
                }
            }
        });
        
        // Update the hidden field
        updateHiddenField(isFullDay);
    }
    
    /**
     * Create a hidden field to store the full day status
     */
    function createHiddenField() {
        // Check if it already exists
        if (document.getElementById('ai-calendar-full-day-hidden')) {
            return;
        }
        
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.id = 'ai-calendar-full-day-hidden';
        hiddenField.name = '_event_is_full_day';
        hiddenField.value = '0';
        
        // Add it to the form
        document.body.appendChild(hiddenField);
        
        console.log('AI Calendar: Created hidden field for full day status');
    }
    
    /**
     * Update the hidden field value
     */
    function updateHiddenField(isFullDay) {
        let hiddenField = document.getElementById('ai-calendar-full-day-hidden');
        
        if (!hiddenField) {
            createHiddenField();
            hiddenField = document.getElementById('ai-calendar-full-day-hidden');
        }
        
        hiddenField.value = isFullDay ? '1' : '0';
        console.log('AI Calendar: Updated hidden field value to', hiddenField.value);
        
        // Also save the value directly using the WordPress REST API if available
        saveFullDayStatusToAPI(isFullDay);
    }
    
    /**
     * Try to save the full day status directly via the WordPress REST API
     */
    function saveFullDayStatusToAPI(isFullDay) {
        // Check if we can get the post ID from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post');
        
        if (!postId) {
            console.log('AI Calendar: Could not determine post ID for API update');
            return;
        }
        
        // Check if the REST API is available
        if (!window.wp || !window.wp.apiFetch) {
            console.log('AI Calendar: WordPress API not available');
            return;
        }
        
        // Save directly via the REST API
        window.wp.apiFetch({
            path: `/wp/v2/ai_calendar_event/${postId}`,
            method: 'POST',
            data: {
                meta: {
                    _event_is_full_day: isFullDay ? '1' : '0'
                }
            }
        }).then(function(response) {
            console.log('AI Calendar: Successfully saved full day status via API', response);
        }).catch(function(error) {
            console.error('AI Calendar: Error saving full day status via API', error);
        });
    }
})(); 