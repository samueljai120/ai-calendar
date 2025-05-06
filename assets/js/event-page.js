jQuery(document).ready(function($) {
    'use strict';

    class EventPage {
        constructor() {
            this.eventContainer = $('.event-details');
            if (!this.eventContainer.length) return;

            this.eventId = this.eventContainer.data('event-id');
            this.state = {
                loading: false,
                error: null,
                data: null
            };

            this.init();
        }

        init() {
            this.loadEventData();
            this.initializeEventHandlers();
        }

        async loadEventData() {
            if (!this.eventId) return;

            this.setState({ loading: true });
            try {
                const response = await $.ajax({
                    url: `${aiCalendar.rest_url}wp/v2/ai_calendar_event/${this.eventId}`,
                    method: 'GET',
                    beforeSend: (xhr) => {
                        xhr.setRequestHeader('X-WP-Nonce', aiCalendar.nonce);
                    }
                });

                this.setState({
                    loading: false,
                    data: response,
                    error: null
                });

                this.updateUI();
            } catch (error) {
                this.setState({
                    loading: false,
                    error: error.responseJSON?.message || 'Error loading event data'
                });
                this.showError();
            }
        }

        setState(newState) {
            this.state = { ...this.state, ...newState };
            this.updateLoadingState();
        }

        updateLoadingState() {
            if (this.state.loading) {
                this.eventContainer.addClass('loading');
            } else {
                this.eventContainer.removeClass('loading');
            }
        }

        updateUI() {
            const { data } = this.state;
            if (!data) return;

            // Update dynamic content
            this.updateMetaSection(data);
            this.updateRegistrationStatus(data);
            this.initializeSharingButtons(data);
        }

        updateMetaSection(data) {
            const meta = data.event_meta;
            
            // Update capacity if it changes
            if (meta.capacity) {
                const capacityElement = this.eventContainer.find('.event-capacity-value');
                if (capacityElement.length) {
                    const currentCapacity = parseInt(meta.capacity);
                    capacityElement.text(`${currentCapacity} people`);
                }
            }

            // Update status
            const statusElement = this.eventContainer.find('.event-status');
            if (statusElement.length && meta.status) {
                statusElement.removeClass('upcoming ongoing completed cancelled')
                           .addClass(meta.status)
                           .text(meta.status.charAt(0).toUpperCase() + meta.status.slice(1));
            }
        }

        updateRegistrationStatus(data) {
            const meta = data.event_meta;
            if (!meta.capacity) return;

            const currentCapacity = parseInt(meta.capacity);
            const registeredCount = parseInt(meta.registered_count || 0);

            if (registeredCount >= currentCapacity) {
                this.eventContainer.addClass('fully-booked');
                // Add fully booked message if not exists
                if (!this.eventContainer.find('.event-fully-booked').length) {
                    $('<div class="event-fully-booked">This event is fully booked</div>')
                        .insertAfter(this.eventContainer.find('.event-capacity'));
                }
            }
        }

        initializeSharingButtons(data) {
            const shareContainer = this.eventContainer.find('.event-sharing');
            if (!shareContainer.length) {
                // Create sharing container if it doesn't exist
                const sharingHtml = `
                    <div class="event-sharing">
                        <button class="share-button" data-platform="facebook">
                            <span class="dashicons dashicons-facebook"></span>
                        </button>
                        <button class="share-button" data-platform="twitter">
                            <span class="dashicons dashicons-twitter"></span>
                        </button>
                        <button class="share-button" data-platform="email">
                            <span class="dashicons dashicons-email"></span>
                        </button>
                    </div>
                `;
                this.eventContainer.find('.event-meta-additional').append(sharingHtml);
            }

            // Add sharing functionality
            $('.share-button').on('click', (e) => {
                const platform = $(e.currentTarget).data('platform');
                this.shareEvent(platform, data);
            });
        }

        shareEvent(platform, data) {
            const url = window.location.href;
            const title = data.title.rendered;
            const description = data.excerpt.rendered.replace(/<[^>]+>/g, '');

            let shareUrl;
            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
                    break;
                case 'email':
                    shareUrl = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(description)}\n\n${encodeURIComponent(url)}`;
                    break;
            }

            if (shareUrl) {
                if (platform === 'email') {
                    window.location.href = shareUrl;
                } else {
                    window.open(shareUrl, '_blank', 'width=600,height=400');
                }
            }
        }

        showError() {
            if (!this.state.error) return;

            const errorHtml = `
                <div class="event-error">
                    <p>${this.state.error}</p>
                    <button class="retry-button">Retry</button>
                </div>
            `;

            this.eventContainer.html(errorHtml);
            this.eventContainer.find('.retry-button').on('click', () => {
                this.loadEventData();
            });
        }

        initializeEventHandlers() {
            // Add any additional event handlers here
            $(window).on('event_data_updated', () => {
                this.loadEventData();
            });
        }
    }

    // Initialize the event page
    new EventPage();
}); 
 
 
 
 
 
 
 