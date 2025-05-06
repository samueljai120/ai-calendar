jQuery(document).ready(function($) {
    'use strict';

    // Handle social share buttons
    $('.share-button').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const width = 600;
        const height = 400;
        const left = (screen.width/2)-(width/2);
        const top = (screen.height/2)-(height/2);
        
        window.open(
            url,
            'share',
            'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left
        );
    });

    class AiCalendar {
        constructor(container, options = {}) {
            this.$container = $(container);
            this.options = options;
            this.events = {};
            
            // Set current date to first day of current month
            this.currentDate = new Date();
            this.currentDate.setDate(1);

            this.init();
        }

        init() {
            // Attach event listeners
            this.$container.find('.prev-month').on('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendar();
                this.fetchEvents();
            });
            
            this.$container.find('.next-month').on('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.renderCalendar();
                this.fetchEvents();
            });
            
            this.$container.find('.today-button').on('click', () => {
                const today = new Date();
                this.currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
                this.renderCalendar();
                this.fetchEvents();
                
                // Scroll to today's container and highlight it
                const todayContainer = this.$container.find('.day-container.today');
                if (todayContainer.length) {
                    todayContainer[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    todayContainer.addClass('highlight');
                    setTimeout(() => todayContainer.removeClass('highlight'), 2000);
                }
            });

            // Handle day container clicks
            this.$container.on('click', '.day-container:not(.empty)', (e) => {
                const $dayContainer = $(e.currentTarget);
                const date = $dayContainer.data('date');
                const dayEvents = this.events[date] || [];
                
                if (dayEvents.length > 0) {
                    this.showEventPreview(date, dayEvents);
                }
            });

            // Handle event clicks
            this.$container.on('click', '.event', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent triggering the day container click
                const $event = $(e.currentTarget);
                const eventId = $event.data('event-id');
                const $dayContainer = $event.closest('.day-container');
                const date = $dayContainer.data('date');
                const dayEvents = this.events[date] || [];
                this.showEventPreview(date, dayEvents);
            });

            // Handle modal close
            this.$container.find('.close-modal').on('click', () => {
                this.$container.find('.event-preview-modal').hide();
            });

            // Close modal when clicking outside
            this.$container.find('.event-preview-modal').on('click', (e) => {
                if ($(e.target).hasClass('event-preview-modal')) {
                    $(e.target).hide();
                }
            });
            
            // Initial render and fetch
            this.renderCalendar();
            this.fetchEvents();
        }
        
        renderCalendar() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            
            // Update month display
            this.$container.find('.current-month').text(
                monthNames[this.currentDate.getMonth()] + ' ' + this.currentDate.getFullYear()
            );
            
            // Clear existing events
            this.$container.find('.events-container').empty();
            this.$container.find('.day-container').removeClass('has-events');
            
            // Update today marker
            const today = new Date();
            this.$container.find('.day-container').removeClass('today');
            if (today.getMonth() === this.currentDate.getMonth() && 
                today.getFullYear() === this.currentDate.getFullYear()) {
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const selector = '.day-container[data-date="' + year + '-' + month + '-' + day + '"]';
                this.$container.find(selector).addClass('today');
            }
        }
        
        fetchEvents() {
            $.ajax({
                url: aiCalendar.ajaxurl,
                method: 'POST',
                data: {
                    action: 'fetch_calendar_events',
                    nonce: aiCalendar.nonce,
                    year: this.currentDate.getFullYear(),
                    month: this.currentDate.getMonth() + 1
                },
                success: (response) => {
                    if (response.success) {
                        // Check if the response format includes a data wrapper with events property
                        if (response.data && typeof response.data === 'object') {
                            if (response.data.events) {
                                // New format - data.events contains grouped events
                                this.events = response.data.events || {};
                            } else {
                                // Previous format - data directly contains grouped events
                                this.events = response.data || {};
                            }
                        } else {
                            this.events = {};
                        }
                        
                        // Process event time values
                        Object.values(this.events).forEach(dayEvents => {
                            dayEvents.forEach(event => {
                                // Ensure is_full_day is a boolean
                                event.is_full_day = Boolean(event.is_full_day);
                                
                                // Check if the server already provided formatted time values
                                const hasServerFormattedTime = event._time_display && 
                                    event._time_display !== 'No time available' && 
                                    event._time_display !== 'Time not specified';
                                
                                // Skip time processing for full day events or events with pre-formatted time
                                if (event.is_full_day || hasServerFormattedTime) {
                                    return;
                                }
                                
                                // For non-full day events without server-formatted time
                                if (!event.is_full_day) {
                                    // We no longer treat 00:00 as empty by default, as it could be midnight
                                    const hasEmptyStartTime = !event.start_time;
                                    const hasEmptyEndTime = !event.end_time;
                                    
                                    // Format the times
                                    event._formatted_start_time = hasEmptyStartTime ? '' : this.formatTime(event.start_time);
                                    event._formatted_end_time = hasEmptyEndTime ? '' : this.formatTime(event.end_time);
                                    
                                    if (event._formatted_start_time && event._formatted_end_time) {
                                        event._time_display = `${event._formatted_start_time} - ${event._formatted_end_time}`;
                                    } else if (event._formatted_start_time) {
                                        event._time_display = `From ${event._formatted_start_time}`;
                                    } else if (event._formatted_end_time) {
                                        event._time_display = `Until ${event._formatted_end_time}`;
                                    } else {
                                        event._time_display = 'No time available';
                                    }
                                }
                            });
                        });
                        
                        this.renderEvents();
                    }
                },
                error: (xhr, status, error) => {
                    // Handle AJAX error silently
                }
            });
        }

        renderEvents() {
            // Clear existing events first
            this.$container.find('.events-container').empty();
            
            // Calculate available space for events
            Object.entries(this.events).forEach(([date, dayEvents]) => {
                const $dayContainer = this.$container.find(`[data-date="${date}"]`);
                if (!$dayContainer.length) return;
                
                const $eventsContainer = $dayContainer.find('.events-container');
                $eventsContainer.empty();
                
                // Calculate available space for events
                const containerHeight = $dayContainer.height();
                const dayHeaderHeight = $dayContainer.find('.day-header').outerHeight(true) || 30;
                const availableHeight = containerHeight - dayHeaderHeight - 10; // Account for padding
                const eventHeight = 24; // Event height + margin (adjust based on your CSS)
                
                // Calculate max slots based on container space
                let maxSlotsBySpace = Math.floor(availableHeight / eventHeight);
                maxSlotsBySpace = Math.max(1, maxSlotsBySpace); // Ensure at least 1 slot
                
                // Set base max slots by screen size
                let maxSlotsByScreenSize;
                if (window.matchMedia('(min-width: 985px)').matches) {
                    maxSlotsByScreenSize = 3; // Desktop and larger screens
                } else if (window.matchMedia('(min-width: 481px)').matches) {
                    maxSlotsByScreenSize = 2; // Tablet
                } else {
                    maxSlotsByScreenSize = 1; // Mobile
                }
                
                // Use the smaller of the two limits to ensure no overflow
                const MAX_SLOTS = Math.min(maxSlotsBySpace, maxSlotsByScreenSize);
                
                // Calculate how many events to display and how many remain
                let displayCount, remainingCount;
                
                if (dayEvents.length <= MAX_SLOTS) {
                    // If all events can fit within max slots, show them all
                    displayCount = dayEvents.length;
                    remainingCount = 0;
                    $eventsContainer.removeClass('has-hidden-events');
                } else {
                    // More events than can fit, reserve one slot for "+X more"
                    displayCount = MAX_SLOTS - 1;
                    displayCount = Math.max(1, displayCount); // Show at least one event
                    remainingCount = dayEvents.length - displayCount;
                    $eventsContainer.addClass('has-hidden-events');
                }
                
                // Get visible events
                const visibleEvents = dayEvents.slice(0, displayCount);
                
                // Add visible events
                visibleEvents.forEach(event => {
                    const $event = $('<div>', {
                        class: `event${event.is_multi_day ? ' multi-day' : ''}${event.is_start ? ' event-start' : ''}${event.is_end ? ' event-end' : ''}`,
                        'data-event-id': event.id,
                        title: event.title
                    }).text(event.title);
                    
                    $eventsContainer.append($event);
                });
                
                // Add more events indicator if needed
                if (remainingCount > 0) {
                    const $moreEvents = $('<div>', {
                        class: 'more-events',
                        text: `+${remainingCount} more`,
                        title: `${remainingCount} more events`
                    });
                    
                    $eventsContainer.append($moreEvents);
                    
                    // Add data attributes for CSS to handle display
                    $eventsContainer
                        .attr('data-total-events', dayEvents.length)
                        .attr('data-hidden-count', remainingCount);
                }
                
                // Add has-events class to container
                $dayContainer.addClass('has-events');
                
                // Set the total event count as a data attribute on the container
                $dayContainer.attr('data-event-count', dayEvents.length);
            });
            
            // Ensure all containers have consistent layout
            this.$container.find('.day-container').each(function() {
                const $this = $(this);
                const $dayDiv = $this.find('> div');
                
                if ($dayDiv.length > 0) {
                    const $events = $dayDiv.find('.events-container');
                    const $dayHeader = $dayDiv.find('.day-header');
                    
                    // Make sure day-number is at the top and events-container is directly below it
                    $dayDiv.css({
                        'display': 'flex',
                        'flex-direction': 'column',
                        'overflow': 'hidden'
                    });
                    
                    $dayHeader.css({
                        'order': '1'
                    });
                    
                    $events.css({
                        'order': '2',
                        'overflow': 'hidden'
                    });
                }
            });
            
            // Apply direct inline styles to ensure "+X more" indicator visibility
            this.$container.find('.more-events').each(function() {
                // Use direct attributes for better CSS precedence
                this.style.setProperty('display', 'flex', 'important');
                this.style.setProperty('visibility', 'visible', 'important');
                this.style.setProperty('opacity', '1', 'important');
            });
        }
        
        formatDate(dateString) {
            const date = new Date(dateString);
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            return date.toLocaleDateString(undefined, options);
        }
        
        formatTime(timeString) {
            // If null or undefined, return default time
            if (timeString === null || timeString === undefined) {
                return '';
            }
            
            // Ensure we're dealing with a string and trim it
            timeString = String(timeString).trim();
            
            // Skip empty times
            if (timeString === '') {
                return '';
            }
            
            // If already in 12-hour format with AM/PM, return as is
            if (/\d{1,2}:\d{2}\s?(AM|PM|am|pm)/i.test(timeString)) {
                return timeString;
            }
            
            // Specifically handle 00:00 - not always empty, could be midnight
            // Only return empty if the context suggests it's meant to be empty
            if ((timeString === '00:00' || timeString === '0:00') && !this.options.preserveMidnight) {
                return '';
            }
            
            try {
                // Parse the time components
                let hours, minutes;
                
                // Handle various formats
                if (timeString.includes(':')) {
                    const parts = timeString.split(':');
                    hours = parseInt(parts[0], 10);
                    minutes = parseInt(parts[1], 10);
                } else {
                    // Single number format (e.g., "9")
                    hours = parseInt(timeString, 10);
                    minutes = 0;
                }
                
                // Validate time components
                if (isNaN(hours) || hours < 0 || hours > 23) {
                    return '';
                }
                
                if (isNaN(minutes) || minutes < 0 || minutes > 59) {
                    minutes = 0;
                }
                
                // Convert to 12-hour format
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // Convert 0 to 12
                
                // Format the result
                return `${hours}:${String(minutes).padStart(2, '0')} ${ampm}`;
            } catch (error) {
                return '';
            }
        }
        
        showEventPreview(date, events) {
            const $modal = this.$container.find('.event-preview-modal');
            const $content = $modal.find('.event-preview-content');
            
            if (!events || events.length === 0) {
                return;
            }
            
            // Extract event IDs for fetching fresh data
            const eventIds = events.map(event => event.id);
            
            // Show loading state
            $modal.fadeIn(100);
            $content.html('<div class="loading-events"><span class="spinner"></span> Loading event details...</div>');
            
            // Fetch fresh event data to ensure we have the latest 
            $.ajax({
                url: aiCalendar.ajaxurl,
                method: 'POST',
                data: {
                    action: 'get_event_details',
                    nonce: aiCalendar.nonce,
                    event_ids: eventIds,
                    _cache_bust: new Date().getTime() // Add cache busting parameter
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.renderEventPreview(date, response.data.events || events);
                    } else {
                        // Fallback to the original events if the AJAX call fails
                        this.renderEventPreview(date, events);
                    }
                },
                error: () => {
                    // Fallback to the original events if the AJAX call fails
                    this.renderEventPreview(date, events);
                }
            });
        }
        
        renderEventPreview(date, events) {
            const $modal = this.$container.find('.event-preview-modal');
            const $content = $modal.find('.event-preview-content');
            
            // Build preview content
            let previewHtml = `
                <div class="preview-header">
                    <h3 class="preview-date">${this.formatDate(date)}</h3>
                    <button type="button" class="close-modal" aria-label="Close preview">&times;</button>
                </div>
                <div class="preview-events">
            `;
            
            events.forEach(event => {
                // Make sure is_full_day is a boolean
                event.is_full_day = Boolean(event.is_full_day);
                
                // Set up the time display
                let timeDisplay;
                
                if (event.is_full_day) {
                    // Full day events always show "Full day"
                    timeDisplay = 'Full day';
                } else if (event._time_display && event._time_display !== 'No time available') {
                    // If the server has provided a pre-formatted time display that isn't "No time available", use it
                    timeDisplay = event._time_display;
                } else {
                    // If there's no pre-formatted display or it's "No time available", 
                    // try to format the times manually using the raw values
                    const formattedStart = event._formatted_start_time || this.formatTime(event.start_time);
                    const formattedEnd = event._formatted_end_time || this.formatTime(event.end_time);
                    
                    if (formattedStart && formattedEnd) {
                        timeDisplay = `${formattedStart} - ${formattedEnd}`;
                    } else if (formattedStart) {
                        timeDisplay = `From ${formattedStart}`;
                    } else if (formattedEnd) {
                        timeDisplay = `Until ${formattedEnd}`;
                    } else {
                        // Last resort - try to directly access any time-related metadata
                        timeDisplay = 'No time available';
                    }
                }
                
                previewHtml += `
                    <div class="preview-event" data-event-id="${event.id}">
                        <div class="event-image">
                            ${event.featured_image ? `
                                <img src="${event.featured_image}" alt="${event.title}" />
                            ` : `
                                <div class="no-image">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </div>
                            `}
                        </div>
                        <div class="event-details">
                            <h4 class="event-title">${event.title}</h4>
                            <div class="event-meta">
                                <div class="event-time">
                                    <span class="time-icon">⏰</span>
                                    <span class="time-text">${timeDisplay}</span>
                                </div>
                                ${event.location ? `
                                    <div class="event-location">
                                        <span class="location-icon">📍</span>
                                        <span>${event.location}</span>
                                    </div>
                                ` : ''}
                            </div>
                            ${event.description ? `
                                <div class="event-description">${event.description}</div>
                            ` : ''}
                            ${event.url ? `
                                <a href="${event.url}" class="event-link" target="_blank" rel="noopener noreferrer">
                                    Learn More
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            previewHtml += `</div>`;
            
            // Update modal content
            $content.html(previewHtml);
            
            // Show the modal with fade effect
            $modal.fadeIn(200);
            
            // Close button event
            $modal.find('.close-modal').off('click').on('click', () => {
                $modal.fadeOut(200);
            });

            // Close on click outside
            $modal.off('click').on('click', function(e) {
                if ($(e.target).hasClass('event-preview-modal')) {
                    $(e.target).fadeOut(200);
                }
            });
        }
        
        // Simple time formatter - minimal processing
        simpleDateFormat(timeString) {
            if (!timeString) return '';
            
            // Skip empty or zero times
            if (timeString === '00:00' || timeString === '') return '';
            
            try {
                // Handle time in format HH:MM
                const parts = timeString.split(':');
                let hours = parseInt(parts[0], 10);
                const minutes = parts.length > 1 ? parts[1].padStart(2, '0') : '00';
                
                // Convert to 12-hour format
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                
                return `${hours}:${minutes} ${ampm}`;
            } catch (e) {
                // If any error, return the original string
                return timeString;
            }
        }

        closeEventModal($modal) {
            // Close modal when clicking outside
            $modal.on('click', (e) => {
                if ($(e.target).is($modal)) {
                    $modal.fadeOut(200);
                }
            });
        }
    }

    // Initialize calendar on document ready
    $('.ai-calendar').each(function() {
        const options = $(this).data('options') || {};
        new AiCalendar(this, options);
    });
}); 