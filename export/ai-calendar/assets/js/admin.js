/**
 * AI Calendar Admin JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }

        // Handle full day event toggle
        const fullDayCheckbox = $('#_event_is_full_day');
        const startTimeField = $('#_event_start_time').closest('.form-row');
        const endTimeField = $('#_event_end_time').closest('.form-row');
        
        // Function to toggle time fields
        function toggleTimeFields() {
            if (fullDayCheckbox.is(':checked')) {
                startTimeField.addClass('hidden');
                endTimeField.addClass('hidden');
            } else {
                startTimeField.removeClass('hidden');
                endTimeField.removeClass('hidden');
            }
        }
        
        // Toggle time fields on page load
        toggleTimeFields();
        
        // Toggle time fields when checkbox is clicked
        fullDayCheckbox.on('change', toggleTimeFields);

        // Validate dates when submitting the form
        $('form#post').on('submit', function(e) {
            const startDate = $('#_event_start_date').val();
            const endDate = $('#_event_end_date').val();
            
            if (!startDate) {
                alert('Please enter a start date for the event.');
                $('#_event_start_date').focus();
                e.preventDefault();
                return false;
            }
            
            if (!endDate) {
                alert('Please enter an end date for the event.');
                $('#_event_end_date').focus();
                e.preventDefault();
                return false;
            }
            
            // If not full day, validate times
            if (!fullDayCheckbox.is(':checked')) {
                const startTime = $('#_event_start_time').val();
                const endTime = $('#_event_end_time').val();
                
                if (!startTime) {
                    alert('Please enter a start time for the event.');
                    $('#_event_start_time').focus();
                    e.preventDefault();
                    return false;
                }
                
                if (!endTime) {
                    alert('Please enter an end time for the event.');
                    $('#_event_end_time').focus();
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    });
})(jQuery); 