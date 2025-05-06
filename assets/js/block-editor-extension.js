/**
 * Block Editor Extension for AI Calendar
 * This script adds functionality to the WordPress block editor for events
 */

(function() {
    'use strict';
    
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('AI Calendar: Block editor extension loaded');
        // Wait a moment for the block editor to initialize
        setTimeout(initializeBlockEditorExtensions, 500);
    });
    
    /**
     * Initialize extensions for the block editor
     */
    function initializeBlockEditorExtensions() {
        // Check if we're in the block editor and on an event page
        const isEventEditor = document.body.classList.contains('post-type-ai_calendar_event') || 
                           document.title.includes('Event');
        
        if (!isEventEditor) {
            console.log('AI Calendar: Not on event editor page');
            return;
        }
        
        // Look for the Event Details panel to be available in the DOM
        const checkForEventPanel = setInterval(function() {
            const eventDetailsPanel = document.querySelector('.block-editor-panel-color-gradient-settings, .components-panel__body, [aria-label="Event Details"]');
            
            if (eventDetailsPanel) {
                console.log('AI Calendar: Found Event Details panel', eventDetailsPanel);
                clearInterval(checkForEventPanel);
                addFullDayCheckbox(eventDetailsPanel);
            }
        }, 500);
        
        // Fallback - if we haven't found it after 10 seconds, try a different approach
        setTimeout(function() {
            clearInterval(checkForEventPanel);
            const timeInputs = document.querySelectorAll('input[type="time"]');
            if (timeInputs.length > 0) {
                console.log('AI Calendar: Found time inputs, adding checkbox');
                const container = timeInputs[0].closest('.components-panel__body');
                if (container) {
                    addFullDayCheckbox(container);
                }
            }
        }, 10000);
    }
    
    /**
     * Add Full Day Event checkbox to the specified container
     */
    function addFullDayCheckbox(container) {
        // Check if checkbox already exists
        if (document.getElementById('ai-calendar-full-day-checkbox')) {
            console.log('AI Calendar: Full day checkbox already exists');
            return;
        }
        
        // Find the time input fields
        const timeInputs = container.querySelectorAll('input[type="time"]');
        if (timeInputs.length === 0) {
            console.log('AI Calendar: No time inputs found');
            return;
        }
        
        // Get the parent element to insert the checkbox
        const timeFieldParent = timeInputs[0].closest('div');
        if (!timeFieldParent) {
            console.log('AI Calendar: Could not find parent for time inputs');
            return;
        }
        
        // Create the checkbox container
        const checkboxContainer = document.createElement('div');
        checkboxContainer.id = 'ai-calendar-full-day-container';
        checkboxContainer.style.cssText = 'margin: 15px 0; padding: 10px; background-color: #f9f9f9; border-radius: 4px; border: 1px solid #ddd; display: flex; align-items: flex-start;';
        
        // Create the checkbox
        const checkboxHTML = `
            <input 
                type="checkbox" 
                id="ai-calendar-full-day-checkbox" 
                name="_event_is_full_day" 
                value="1"
                style="margin-top: 4px; margin-right: 10px;"
            >
            <div>
                <label for="ai-calendar-full-day-checkbox" style="font-weight: bold; display: block; margin-bottom: 5px;">
                    Full Day Event
                </label>
                <p style="margin-top: 0; font-style: italic; color: #666; font-size: 12px;">
                    Check this box if the event lasts all day. Time fields will be hidden.
                </p>
            </div>
        `;
        
        checkboxContainer.innerHTML = checkboxHTML;
        
        // Insert before the time fields
        timeFieldParent.parentNode.insertBefore(checkboxContainer, timeFieldParent);
        
        // Add event listener to the checkbox
        const checkbox = document.getElementById('ai-calendar-full-day-checkbox');
        const startTimeInput = timeInputs[0];
        const endTimeInput = timeInputs.length > 1 ? timeInputs[1] : null;
        
        if (checkbox && startTimeInput) {
            // Hide time fields if checkbox is checked
            checkbox.addEventListener('change', function() {
                const isFullDay = this.checked;
                
                console.log('AI Calendar: Full day checkbox changed', isFullDay);
                
                if (isFullDay) {
                    // Store original values to restore later if needed
                    startTimeInput.dataset.originalValue = startTimeInput.value;
                    if (endTimeInput) endTimeInput.dataset.originalValue = endTimeInput.value;
                    
                    // Clear values and hide time fields
                    startTimeInput.value = '';
                    if (endTimeInput) endTimeInput.value = '';
                    
                    startTimeInput.closest('.components-base-control').style.display = 'none';
                    if (endTimeInput) endTimeInput.closest('.components-base-control').style.display = 'none';
                    
                    // Store the full day status in a hidden field
                    saveFullDayStatus(true);
                } else {
                    // Restore original values if available
                    if (startTimeInput.dataset.originalValue) {
                        startTimeInput.value = startTimeInput.dataset.originalValue;
                    }
                    
                    if (endTimeInput && endTimeInput.dataset.originalValue) {
                        endTimeInput.value = endTimeInput.dataset.originalValue;
                    }
                    
                    // Show time fields
                    startTimeInput.closest('.components-base-control').style.display = '';
                    if (endTimeInput) endTimeInput.closest('.components-base-control').style.display = '';
                    
                    // Update the full day status
                    saveFullDayStatus(false);
                }
            });
            
            // Check for existing full day status
            checkExistingFullDayStatus(checkbox, startTimeInput, endTimeInput);
        }
    }
    
    /**
     * Save the full day status to the post meta
     */
    function saveFullDayStatus(isFullDay) {
        console.log('AI Calendar: Saving full day status:', isFullDay);
        
        // Create or update a hidden input field to store the full day status
        let hiddenField = document.getElementById('ai-calendar-full-day-hidden');
        
        if (!hiddenField) {
            hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.id = 'ai-calendar-full-day-hidden';
            hiddenField.name = '_event_is_full_day';
            document.body.appendChild(hiddenField);
        }
        
        hiddenField.value = isFullDay ? '1' : '0';
        
        // Dispatch a custom event that our plugin can listen for
        const event = new CustomEvent('ai-calendar-full-day-changed', {
            detail: { isFullDay: isFullDay }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Check for existing full day status
     */
    function checkExistingFullDayStatus(checkbox, startTimeInput, endTimeInput) {
        // Check if both time inputs are empty or if there's a hidden field
        const hiddenField = document.querySelector('input[name="_event_is_full_day"]');
        
        if (hiddenField && hiddenField.value === '1') {
            console.log('AI Calendar: Found existing full day status in hidden field');
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
            return;
        }
        
        // If both time fields are empty, consider it a full day event
        if ((!startTimeInput.value || startTimeInput.value === '00:00') && 
            (!endTimeInput || !endTimeInput.value || endTimeInput.value === '00:00')) {
            console.log('AI Calendar: Both time fields are empty, assuming full day event');
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
    }
})(); 