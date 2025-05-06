// Custom jQuery modifications to handle deprecated features
jQuery(function($) {
    // Replace deprecated mutation events with custom events
    $.fn.onDOMChange = function(callback) {
        return this.each(function() {
            const element = this;
            
            // Use MutationObserver instead of DOMSubtreeModified
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' || mutation.type === 'subtree') {
                        callback.call(element, mutation);
                    }
                });
            });

            observer.observe(element, {
                childList: true,
                subtree: true
            });

            return () => observer.disconnect();
        });
    };

    // Custom event to replace DOMSubtreeModified
    $(document).on('DOMContentModified', function(e) {
        // Handle DOM modifications here
        if (window.aiCalendarSettings && window.aiCalendarSettings.onDOMChange) {
            window.aiCalendarSettings.onDOMChange(e);
        }
    });

    // Override jQuery.migrate warning
    if ($.migrateWarnings) {
        $.migrateWarnings = [];
    }
}); 