<?php
/**
 * Template part for displaying event actions (export and share buttons)
 * Redesigned with modern social sharing buttons and calendar export functionality
 * Inspired by leading WordPress plugins like Monarch and AddToAny
 */

// Ensure we have the required variables
if (!isset($event_id)) $event_id = get_the_ID();
if (!isset($event_title)) $event_title = get_the_title();
if (!isset($event_url)) $event_url = get_permalink();
if (!isset($start_date)) $start_date = get_post_meta($event_id, '_event_start_date', true);
if (!isset($end_date)) $end_date = get_post_meta($event_id, '_event_end_date', true);
if (!isset($location)) $location = get_post_meta($event_id, '_event_location', true);

// Format dates for Google Calendar
$start_datetime = new DateTime($start_date);
$end_datetime = new DateTime($end_date);
$google_start = $start_datetime->format('Ymd\THis\Z');
$google_end = $end_datetime->format('Ymd\THis\Z');

// Generate Google Calendar URL
$google_url = add_query_arg([
    'action' => 'TEMPLATE',
    'text' => urlencode($event_title),
    'dates' => urlencode($google_start . '/' . $google_end),
    'details' => urlencode(wp_strip_all_tags(get_the_content())),
    'location' => urlencode($location),
    'sf' => true,
    'output' => 'xml'
], 'https://calendar.google.com/calendar/render');

// Generate iCal URL
$ical_url = add_query_arg([
    'action' => 'ai_calendar_ical_export',
    'event_id' => $event_id,
    'nonce' => wp_create_nonce('ai_calendar_ical_export')
], admin_url('admin-ajax.php'));

// Social share URLs with UTM parameters for better tracking
$share_urls = [
    'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($event_url . '?utm_source=facebook&utm_medium=social&utm_campaign=event_share'),
    'twitter' => 'https://twitter.com/intent/tweet?url=' . urlencode($event_url . '?utm_source=twitter&utm_medium=social&utm_campaign=event_share') . '&text=' . urlencode($event_title),
    'linkedin' => 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($event_url . '?utm_source=linkedin&utm_medium=social&utm_campaign=event_share') . '&title=' . urlencode($event_title),
    'email' => 'mailto:?subject=' . urlencode($event_title) . '&body=' . urlencode($event_title . "\n\n" . $event_url . '?utm_source=email&utm_medium=social&utm_campaign=event_share')
];

// Get event categories and tags for metadata
$categories = get_the_terms($event_id, 'event_category');
$tags = get_the_terms($event_id, 'event_tag');
?>

<div class="event-action-section">
    <h3><?php _e('Share and Add to Calendar', 'ai-calendar'); ?></h3>
    
    <!-- Share buttons -->
    <div class="event-share">
        <h4><?php _e('Share This Event', 'ai-calendar'); ?></h4>
        <div class="event-action-buttons">
            <!-- Facebook -->
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($event_url); ?>" 
               class="event-action-button facebook" 
               target="_blank" 
               rel="noopener noreferrer">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/>
                    </svg>
                </span>
                <span class="text">Facebook</span>
            </a>
            
            <!-- Twitter -->
            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($event_title); ?>&url=<?php echo urlencode($event_url); ?>" 
               class="event-action-button twitter" 
               target="_blank" 
               rel="noopener noreferrer">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.44-.67.2-1.37.2-2.06.08.58 1.8 2.26 3.12 4.25 3.16C5.78 18.1 3.37 18.74 1 18.46c2 1.3 4.4 2.04 6.97 2.04 8.35 0 12.92-6.92 12.92-12.93 0-.2 0-.4-.02-.6.9-.63 1.96-1.22 2.56-2.14z"/>
                    </svg>
                </span>
                <span class="text">Twitter</span>
            </a>
            
            <!-- LinkedIn -->
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($event_url); ?>&title=<?php echo urlencode($event_title); ?>" 
               class="event-action-button linkedin" 
               target="_blank" 
               rel="noopener noreferrer">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.5 21.5h-5v-13h5v13zM4 6.5C2.5 6.5 1.5 5.3 1.5 4s1-2.4 2.5-2.4c1.6 0 2.5 1 2.6 2.5 0 1.4-1 2.5-2.6 2.5zm11.5 6c-1 0-2 1-2 2v7h-5v-13h5V10s1.6-1.5 4-1.5c3 0 5 2.2 5 6.3v6.7h-5v-7c0-1-1-2-2-2z"/>
                    </svg>
                </span>
                <span class="text">LinkedIn</span>
            </a>
            
            <!-- Email -->
            <a href="mailto:?subject=<?php echo urlencode(sprintf(__('Check out this event: %s', 'ai-calendar'), $event_title)); ?>&body=<?php 
                echo urlencode(sprintf(
                    __("I thought you might be interested in this event:\n\n%s\n\nDate: %s\n\nLocation: %s\n\nMore info: %s", 'ai-calendar'),
                    $event_title,
                    $start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '',
                    $location ?: __('Online', 'ai-calendar'),
                    $event_url
                )); 
            ?>" 
               class="event-action-button email">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </span>
                <span class="text">Email</span>
            </a>
        </div>
    </div>
    
    <!-- Add to Calendar -->
    <div class="event-calendar">
        <h4><?php _e('Add to Calendar', 'ai-calendar'); ?></h4>
        <div class="event-action-buttons">
            <!-- Google Calendar -->
            <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=<?php echo urlencode($event_title); ?>&dates=<?php 
                if ($start_date) {
                    $start_formatted = date('Ymd', strtotime($start_date));
                    if ($start_time) {
                        $start_formatted .= 'T' . date('His', strtotime($start_time));
                    }
                    
                    echo $start_formatted;
                    
                    if ($end_date) {
                        $end_formatted = date('Ymd', strtotime($end_date));
                        if ($end_time) {
                            $end_formatted .= 'T' . date('His', strtotime($end_time));
                        }
                        echo '/' . $end_formatted;
                    } else if ($end_time) {
                        echo '/' . $start_formatted . 'T' . date('His', strtotime($end_time));
                    }
                }
            ?>&location=<?php echo urlencode($location ?: ''); ?>&details=<?php echo urlencode($event_url); ?>" 
               class="event-action-button google-calendar" 
               target="_blank" 
               rel="noopener noreferrer">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </span>
                <span class="text">Google Calendar</span>
            </a>
            
            <!-- Apple Calendar (iCal) -->
            <a href="#" 
               class="event-action-button apple-calendar" 
               id="download-ical"
               data-event-title="<?php echo esc_attr($event_title); ?>"
               data-event-start="<?php echo esc_attr($start_date . ($start_time ? ' ' . $start_time : '')); ?>"
               data-event-end="<?php echo esc_attr($end_date . ($end_time ? ' ' . $end_time : '')); ?>"
               data-event-location="<?php echo esc_attr($location ?: ''); ?>"
               data-event-url="<?php echo esc_attr($event_url); ?>">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z"></path>
                        <path d="M12 8v8"></path>
                        <path d="M8 12h8"></path>
                    </svg>
                </span>
                <span class="text">Apple Calendar</span>
            </a>
        </div>
    </div>
</div>

<style>
.event-action-section {
    padding: 10px 0;
}

.event-action-section h3 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #2d3748;
    font-size: 1.3rem;
}

.event-share, .event-calendar {
    margin-bottom: 1.5rem;
}

.event-share h4, .event-calendar h4 {
    font-size: 1rem;
    color: #4a5568;
    margin-bottom: 1rem;
    text-align: center;
}

.event-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    margin: 0 auto;
}

.event-action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #4a5568;
    font-weight: 500;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.event-action-button:hover {
    background: #f1f5f9;
    color: #3182ce;
    border-color: #cbd5e0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.event-action-button.facebook:hover {
    color: #4267B2;
}

.event-action-button.twitter:hover {
    color: #1DA1F2;
}

.event-action-button.linkedin:hover {
    color: #0077B5;
}

.event-action-button.email:hover {
    color: #D44638;
}

.event-action-button.google-calendar:hover {
    color: #4285F4;
}

.event-action-button.apple-calendar:hover {
    color: #555;
}

@media (max-width: 768px) {
    .event-action-button {
        flex: 1 1 calc(50% - 1rem);
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .event-action-button {
        flex: 1 1 100%;
    }
}
</style>

<script>
(function() {
    // Handle iCal download
    document.getElementById('download-ical').addEventListener('click', function(e) {
        e.preventDefault();
        
        const title = this.getAttribute('data-event-title');
        const start = this.getAttribute('data-event-start');
        const end = this.getAttribute('data-event-end');
        const location = this.getAttribute('data-event-location');
        const url = this.getAttribute('data-event-url');
        
        // Create iCal file content
        const now = new Date().toISOString().replace(/[-:.]/g, '');
        const startDate = new Date(start).toISOString().replace(/[-:.]/g, '');
        const endDate = end ? new Date(end).toISOString().replace(/[-:.]/g, '') : startDate;
        
        const icsContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AI Calendar//Event Calendar//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'DTSTAMP:' + now,
            'DTSTART:' + startDate,
            'DTEND:' + endDate,
            'SUMMARY:' + title,
            location ? 'LOCATION:' + location : '',
            url ? 'URL:' + url : '',
            'END:VEVENT',
            'END:VCALENDAR'
        ].join('\r\n');
        
        // Create and trigger download
        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.ics';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
})();
</script> 