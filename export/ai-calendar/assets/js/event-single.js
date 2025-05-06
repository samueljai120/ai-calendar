class EventSingle {
    constructor() {
        // Initialize properties
        this.container = document.querySelector('.event-single');
        if (!this.container) {
            console.log('Event single container not found');
            return;
        }

        this.eventId = this.container.querySelector('#post-' + this.getEventId());
        this.registerButton = this.container.querySelector('.register-button');
        this.addToCalendarButton = this.container.querySelector('.add-to-calendar');
        this.shareButtons = this.container.querySelectorAll('.share-button');
        
        // Get event data from meta tags
        this.eventData = aiCalendarEvent.eventData;
        this.shareUrls = aiCalendarEvent.shareUrls;
        
        // Initialize features
        this.initRegistration();
        this.initAddToCalendar();
        this.initShareButtons();
        this.initExportButtons();
    }

    getEventId() {
        const article = document.querySelector('.event-single article');
        return article ? article.id.replace('post-', '') : null;
    }

    initRegistration() {
        if (!this.registerButton) return;

        this.registerButton.addEventListener('click', async (e) => {
            e.preventDefault();
            
            try {
                const response = await this.handleRegistration();
                if (response.success) {
                    this.showNotification('success', aiCalendar.i18n.registration_success);
                    this.updateCapacityDisplay(response.remaining);
                } else {
                    this.showNotification('error', response.message || aiCalendar.i18n.registration_error);
                }
            } catch (error) {
                console.error('Registration error:', error);
                this.showNotification('error', aiCalendar.i18n.registration_error);
            }
        });
    }

    async handleRegistration() {
        const formData = new FormData();
        formData.append('action', 'ai_calendar_register');
        formData.append('event_id', this.getEventId());
        formData.append('nonce', aiCalendar.nonce);

        const response = await fetch(aiCalendar.ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });

        return await response.json();
    }

    updateCapacityDisplay(remaining) {
        const capacityValue = this.container.querySelector('.capacity-value');
        const capacityProgress = this.container.querySelector('.capacity-progress');
        
        if (capacityValue && remaining !== undefined) {
            capacityValue.textContent = `${remaining} ${aiCalendar.i18n.spots}`;
        }
        
        if (capacityProgress && remaining !== undefined) {
            const total = parseInt(aiCalendar.eventCapacity);
            const percentage = ((total - remaining) / total) * 100;
            capacityProgress.style.width = `${percentage}%`;
        }
    }

    initAddToCalendar() {
        if (!this.addToCalendarButton) return;

        this.addToCalendarButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            const calendarUrl = this.generateCalendarUrl();
            window.open(calendarUrl, '_blank');
        });
    }

    generateCalendarUrl() {
        const { title, startDate, endDate, location, description, url } = this.eventData;
        
        // Format for Google Calendar
        const start = new Date(startDate).toISOString().replace(/-|:|\.\d\d\d/g, '');
        const end = new Date(endDate).toISOString().replace(/-|:|\.\d\d\d/g, '');
        
        return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&dates=${start}/${end}&location=${encodeURIComponent(location)}&details=${encodeURIComponent(description + '\n\nEvent URL: ' + url)}`;
    }

    initShareButtons() {
        jQuery('.share-button').on('click', (e) => {
            e.preventDefault();
            const url = jQuery(e.currentTarget).attr('href');
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
    }

    initExportButtons() {
        jQuery('.export-button').on('click', (e) => {
            const isIcal = jQuery(e.currentTarget).hasClass('ical');
            if (!isIcal) return; // Only handle iCal downloads

            e.preventDefault();
            const url = jQuery(e.currentTarget).attr('href');
            
            // Show loading state
            jQuery(e.currentTarget).addClass('loading');
            
            // Download the file
            fetch(url)
                .then(response => response.blob())
                .then(blob => {
                    const link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = 'event.ics';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                })
                .catch(error => {
                    console.error('Error downloading iCal file:', error);
                    alert('Sorry, there was an error downloading the calendar file. Please try again.');
                })
                .finally(() => {
                    jQuery(e.currentTarget).removeClass('loading');
                });
        });
    }

    showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `ai-calendar-notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Remove after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof aiCalendarEvent !== 'undefined') {
        new EventSingle();
    }
}); 