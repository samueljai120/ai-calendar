/**
 * Event Time Fixer for AI Calendar
 * 
 * This script ensures that event time settings are properly displayed and handled
 */

(function() {
    'use strict';

    // Initialize when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Event Time Fixer: Loaded');
        console.log('Available elements with ID _event_is_full_day:', document.getElementById('_event_is_full_day'));
        console.log('Container ai-calendar-time-settings exists:', !!document.getElementById('ai-calendar-time-settings'));
        
        // Check if we already have the time settings UI (added by PHP)
        if (document.getElementById('ai-calendar-time-settings')) {
            console.log('Event Time Fixer: Time settings UI already exists');
            initExistingTimeSettings();
            return;
        }
        
        // If no UI exists, add it (fallback approach)
        console.log('Event Time Fixer: Adding time settings UI as fallback');
        addTimeSettingsUI();
    });
    
    /**
     * Initialize the existing time settings UI's behavior
     */
    function initExistingTimeSettings() {
        // Make sure the toggle function works by adding a listener directly to the checkbox
        const checkbox = document.getElementById('_event_is_full_day');
        console.log('Found checkbox element:', checkbox);
        
        if (checkbox) {
            console.log('Event Time Fixer: Setting up time toggle on existing UI');
            
            checkbox.addEventListener('change', function() {
                console.log('Checkbox changed to:', this.checked);
                toggleTimeFields(this.checked);
            });
            
            // Apply the toggle immediately based on current state
            console.log('Initial checkbox state:', checkbox.checked);
            toggleTimeFields(checkbox.checked);
        } else {
            console.log('Event Time Fixer: Failed to find checkbox element with ID _event_is_full_day');
        }
    }
    
    /**
     * Add a new time settings UI to the page if one wasn't added by PHP
     */
    function addTimeSettingsUI() {
        // Look for potential containers to insert our UI
        const containers = [
            document.getElementById('ai_calendar_event_details'),
            document.querySelector('.postbox'),
            document.getElementById('titlediv')
        ];
        
        console.log('Potential containers:', 
            containers.map(c => c ? c.tagName + (c.id ? '#' + c.id : '') : 'null').join(', '));
        
        // Find the first valid container
        let container = null;
        for (let i = 0; i < containers.length; i++) {
            if (containers[i]) {
                container = containers[i];
                break;
            }
        }
        
        if (!container) {
            console.error('Event Time Fixer: No container found to add time settings UI');
            return;
        }
        
        console.log('Using container:', container.tagName + (container.id ? '#' + container.id : ''));
        
        // Create the time settings UI
        const timeSettingsDiv = document.createElement('div');
        timeSettingsDiv.id = 'ai-calendar-time-settings';
        timeSettingsDiv.style.backgroundColor = '#f0f7fb';
        timeSettingsDiv.style.border = '1px solid #3498db';
        timeSettingsDiv.style.borderRadius = '4px';
        timeSettingsDiv.style.padding = '15px';
        timeSettingsDiv.style.margin = '15px 0';
        
        // Get existing values
        const initialValues = getInitialValues();
        console.log('Initial values:', initialValues);
        
        // Create the HTML content
        timeSettingsDiv.innerHTML = `
            <h3 style="margin-top: 0; margin-bottom: 15px; color: #2980b9;">
                Event Time Settings
            </h3>
            
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; font-weight: bold; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        id="_event_is_full_day" 
                        name="_event_is_full_day" 
                        value="1"
                        ${initialValues.isFullDay ? 'checked' : ''}
                        style="margin-right: 10px; width: auto;"
                    >
                    <span>This is a full day event</span>
                </label>
                <p style="color: #666; margin: 5px 0 0 25px; font-style: italic;">
                    Check this box if the event lasts all day without specific start/end times.
                </p>
            </div>
            
            <div id="event-time-fields" style="display: ${initialValues.isFullDay ? 'none' : 'grid'}; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div>
                    <label for="_event_start_time" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        Start Time
                    </label>
                    <input 
                        type="time" 
                        id="_event_start_time" 
                        name="_event_start_time" 
                        value="${initialValues.startTime}"
                        style="width: 100%;"
                        ${initialValues.isFullDay ? 'disabled' : ''}
                    >
                </div>
                <div>
                    <label for="_event_end_time" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        End Time
                    </label>
                    <input 
                        type="time" 
                        id="_event_end_time" 
                        name="_event_end_time" 
                        value="${initialValues.endTime}"
                        style="width: 100%;"
                        ${initialValues.isFullDay ? 'disabled' : ''}
                    >
                </div>
            </div>
            
            <div style="margin-top: 15px; padding: 10px; background-color: #e7f4ff; border-left: 4px solid #2271b1; font-size: 13px;">
                <strong>Note:</strong>
                For events with specific times, uncheck "Full day event" and set the start and end times above. 
                These times will appear in the calendar and event preview.
            </div>
        `;
        
        // Insert the UI at the top of the container
        container.insertBefore(timeSettingsDiv, container.firstChild);
        console.log('Time settings UI added successfully');
        
        // Add event listener for the checkbox
        const checkbox = document.getElementById('_event_is_full_day');
        if (checkbox) {
            console.log('Added event listener to new checkbox');
            checkbox.addEventListener('change', function() {
                console.log('New checkbox changed to:', this.checked);
                toggleTimeFields(this.checked);
            });
        } else {
            console.error('Failed to find new checkbox after adding it to the DOM');
        }
    }
    
    /**
     * Get initial values for the time fields
     */
    function getInitialValues() {
        // Check for global data object first (set in the Admin.php file)
        if (window.aiCalendarEventData) {
            console.log('Event Time Fixer: Using aiCalendarEventData for initial values', window.aiCalendarEventData);
            return {
                startTime: window.aiCalendarEventData.startTime || '',
                endTime: window.aiCalendarEventData.endTime || '',
                isFullDay: window.aiCalendarEventData.isFullDay || false
            };
        }
        
        // Otherwise look for existing input fields on the page
        console.log('Event Time Fixer: Searching for existing input fields');
        const existingStartTime = document.querySelector('[name="_event_start_time"]');
        const existingEndTime = document.querySelector('[name="_event_end_time"]');
        const existingFullDay = document.querySelector('[name="_event_is_full_day"]');
        
        console.log('Found existing fields:', {
            startTime: existingStartTime ? existingStartTime.value : 'not found',
            endTime: existingEndTime ? existingEndTime.value : 'not found',
            fullDay: existingFullDay ? existingFullDay.checked : 'not found'
        });
        
        // Get values from existing fields if they exist
        const startTime = existingStartTime ? existingStartTime.value : '';
        const endTime = existingEndTime ? existingEndTime.value : '';
        let isFullDay = existingFullDay ? existingFullDay.checked : false;
        
        // If no explicit full day setting, check if times are empty or 00:00
        if (!existingFullDay && ((startTime === '' || startTime === '00:00') && (endTime === '' || endTime === '00:00'))) {
            console.log('No explicit full day setting, but times indicate full day');
            isFullDay = true;
        }
        
        return { startTime, endTime, isFullDay };
    }
    
    /**
     * Toggle time fields based on full day checkbox
     */
    function toggleTimeFields(isFullDay) {
        const timeFields = document.getElementById('event-time-fields');
        const startTimeInput = document.getElementById('_event_start_time');
        const endTimeInput = document.getElementById('_event_end_time');
        
        console.log('Toggling time fields:', {
            isFullDay,
            timeFields: timeFields ? true : false,
            startTimeInput: startTimeInput ? true : false,
            endTimeInput: endTimeInput ? true : false
        });
        
        if (isFullDay) {
            if (timeFields) timeFields.style.display = 'none';
            if (startTimeInput) {
                startTimeInput.disabled = true;
                startTimeInput.value = '';
            }
            if (endTimeInput) {
                endTimeInput.disabled = true;
                endTimeInput.value = '';
            }
        } else {
            if (timeFields) timeFields.style.display = 'grid';
            if (startTimeInput) startTimeInput.disabled = false;
            if (endTimeInput) endTimeInput.disabled = false;
        }
    }
})(); 