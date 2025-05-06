<?php

namespace AiCalendar\Ajax;

class EventHandler {
    public function __construct() {
        add_action('wp_ajax_fetch_calendar_events', [$this, 'fetch_calendar_events']);
        add_action('wp_ajax_nopriv_fetch_calendar_events', [$this, 'fetch_calendar_events']);
        
        // Add the event details AJAX handler
        add_action('wp_ajax_get_event_details', [$this, 'get_event_details']);
        add_action('wp_ajax_nopriv_get_event_details', [$this, 'get_event_details']);
        
        // Add hooks for saving events - only for standard post saves
        add_action('save_post', [$this, 'save_post_meta'], 10, 1);
        
        // Remove REST API handler to avoid conflicts with EventPostType.php
        // add_action('rest_after_insert_ai_calendar_event', [$this, 'save_post_meta'], 10, 1);
    }

    public function fetch_calendar_events() {
        // Verify nonce
        if (!check_ajax_referer('ai_calendar_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token.', 'ai-calendar')]);
            return;
        }
        
        // Get year and month from request
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        
        // Log request for debugging
        error_log("Fetch calendar events for $year-$month");
        
        // Get events for the month
        $events = $this->get_events_for_month($year, $month);
        
        // Group events by date for calendar display
        $grouped_events = $this->group_events_by_date($events);
        
        // Log the data being sent to the frontend
        error_log("Sending events to frontend: " . json_encode(array_keys($grouped_events)));
        
        // Force cache busting by adding a timestamp
        $response = [
            'events' => $grouped_events,
            'timestamp' => time(),
            'debug' => [
                'eventCount' => count($events),
                'groupedCount' => count($grouped_events)
            ]
        ];
        
        // Send the response
        wp_send_json_success($response);
    }

    private function get_events_for_month($year, $month) {
        // Create start and end dates for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
            
        // Query events for this month
        $args = array(
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    // Event starts in this month
                    array(
                        'key' => '_event_start_date',
                        'value' => array($start_date, $end_date),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE'
                    ),
                    // Event ends in this month
                    array(
                        'key' => '_event_end_date',
                        'value' => array($start_date, $end_date),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE'
                    ),
                    // Event spans this month
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => '_event_start_date',
                            'value' => $start_date,
                            'compare' => '<=',
                            'type' => 'DATE'
                        ),
                        array(
                            'key' => '_event_end_date',
                            'value' => $end_date,
                            'compare' => '>=',
                            'type' => 'DATE'
                        )
                    )
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC'
        );
            
        $query = new \WP_Query($args);
        $events = array();
            
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get event meta data
                $start_date = get_post_meta($post_id, '_event_start_date', true);
                $end_date = get_post_meta($post_id, '_event_end_date', true) ?: $start_date;
                
                // Determine if this is a full day event 
                $is_full_day = get_post_meta($post_id, '_event_is_full_day', true) === '1';
                
                // Get raw time values directly from database without cleaning
                $start_time = get_post_meta($post_id, '_event_start_time', true);
                $end_time = get_post_meta($post_id, '_event_end_time', true);
                
                error_log("Event ID $post_id ({$query->post->post_title}) time values from DB - start='$start_time', end='$end_time'");
                
                // Get other event details
                $location = get_post_meta($post_id, '_event_location', true);
                $url = get_post_meta($post_id, '_event_url', true) ?: get_permalink($post_id);
                $color = get_post_meta($post_id, '_event_color', true) ?: '#3788d8';
                
                // Get featured image if exists
                $featured_image = get_the_post_thumbnail_url($post_id, 'medium');
                
                // Format description
                $description = get_the_excerpt();
                
                // Create event object with all the data
                $event = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'is_full_day' => $is_full_day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'location' => $location,
                    'url' => $url,
                    'color' => $color,
                    'description' => $description,
                    'featured_image' => $featured_image,
                    'is_multi_day' => $start_date !== $end_date
                );
                
                // Apply the filter to ensure proper time values
                $event = apply_filters('ai_calendar_event_data', $event);
                
                $events[] = $event;
            }
            
            wp_reset_postdata();
        }
        
        return $events;
    }

    /**
     * Clean and normalize a time value
     * 
     * @param string $time The time value to clean
     * @return string|null Cleaned time value or null if invalid/empty
     */
    private function clean_time_value($time) {
        // If empty or null, return null
        if (empty($time)) {
            return null;
        }
        
        // Trim whitespace
        $time = trim($time);
        
        // Check for common "empty" time values
        if ($time === '00:00:00' || $time === '0:00:00' || 
            $time === '00:00' || $time === '0:00' || 
            $time === '0') {
            return '00:00';
        }
        
        // Handle 12-hour format with AM/PM
        if (preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM|am|pm)/i', $time, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $ampm = strtoupper($matches[3]);
            
            // Convert to 24-hour format
            if ($ampm === 'PM' && $hours < 12) {
                $hours += 12;
            } else if ($ampm === 'AM' && $hours === 12) {
                $hours = 0;
            }
            
            return sprintf('%02d:%02d', $hours, $minutes);
        }
        
        // Parse HH:MM:SS format
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $time, $matches)) {
            return sprintf('%02d:%02d', (int)$matches[1], (int)$matches[2]);
        }
        
        // Parse HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return sprintf('%02d:%02d', (int)$matches[1], (int)$matches[2]);
        }
        
        // Handle single digit hour (e.g., "9")
        if (preg_match('/^(\d{1,2})$/', $time)) {
            return sprintf('%02d:00', (int)$time);
        }
        
        // If we can't parse it, return as is
        return $time;
    }

    private function group_events_by_date($events) {
        $grouped = array();
        
        foreach ($events as $event) {
            $start_date = new \DateTime($event['start_date']);
            $end_date = new \DateTime($event['end_date']);
            
            // For each day the event spans
            $current_date = clone $start_date;
            
            while ($current_date <= $end_date) {
                $date_key = $current_date->format('Y-m-d');
                
                if (!isset($grouped[$date_key])) {
                    $grouped[$date_key] = array();
                }
                
                // Clone the event data to avoid reference issues
                $day_event = $event;
                
                // Mark if this is the start or end date
                $day_event['is_start'] = $current_date->format('Y-m-d') === $start_date->format('Y-m-d');
                $day_event['is_end'] = $current_date->format('Y-m-d') === $end_date->format('Y-m-d');
                
                // Ensure we preserve the raw time values
                if (isset($event['start_time'])) {
                    $day_event['start_time'] = $event['start_time'];
                }
                if (isset($event['end_time'])) {
                    $day_event['end_time'] = $event['end_time'];
                }
                
                // Apply the filter to ensure proper time display values
                $day_event = apply_filters('ai_calendar_event_data', $day_event);
                
                $grouped[$date_key][] = $day_event;
                
                // Move to next day
                $current_date->modify('+1 day');
            }
        }
        
        return $grouped;
    }

    /**
     * Format a time string for display (24h to 12h)
     */
    private function format_time($time) {
        if (empty($time)) {
            return '';
        }
        
        // Trim the time value
        $time = trim($time);
        
        // If empty after trim, return empty string
        if ($time === '' || $time === '00:00' || $time === '0:00') {
            return '';
        }
        
        // If already in 12h format, return as is
        if (preg_match('/\d{1,2}:\d{2}(?::\d{2})?\s?(?:AM|PM|am|pm)/i', $time)) {
            return $time;
        }
        
        // Parse time components
        $parts = explode(':', $time);
        
        if (count($parts) < 2) {
            // Handle single number (hours only)
            if (is_numeric($time)) {
                $hours = (int)$time;
                $minutes = 0;
            } else {
                return $time; // Can't parse, return as is
            }
        } else {
            $hours = (int)$parts[0];
            $minutes = (int)$parts[1];
        }
        
        // Convert to 12h format
        $ampm = $hours >= 12 ? 'PM' : 'AM';
        $hours12 = $hours % 12;
        $hours12 = $hours12 ? $hours12 : 12; // Convert 0 to 12
        
        return sprintf('%d:%02d %s', $hours12, $minutes, $ampm);
    }

    public function get_month_events() {
        if (!check_ajax_referer('ai_calendar_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'ai-calendar')]);
        }

        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');

        // Get start and end dates for the month
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));

        $args = [
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    // Events that start within this month
                    'key' => '_event_start_date',
                    'value' => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                [
                    // Events that end within this month
                    'key' => '_event_end_date',
                    'value' => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                [
                    // Events that span over this month
                    'relation' => 'AND',
                    [
                        'key' => '_event_start_date',
                        'value' => $start_date,
                        'compare' => '<=',
                        'type' => 'DATE'
                    ],
                    [
                        'key' => '_event_end_date',
                        'value' => $end_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ]
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC'
        ];

        $query = new \WP_Query($args);
        $events = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                $start_date = get_post_meta($event_id, '_event_start_date', true);
                $end_date = get_post_meta($event_id, '_event_end_date', true);
                
                // Get time values directly from database
                $start_time = trim(get_post_meta($event_id, '_event_start_time', true));
                $end_time = trim(get_post_meta($event_id, '_event_end_time', true));
                
                // Log times for debugging
                error_log("Month Events - Event ID $event_id ({$query->post->post_title}) - Raw times: start='$start_time', end='$end_time'");
                
                // Generate array of dates between start and end
                $period = new \DatePeriod(
                    new \DateTime($start_date),
                    new \DateInterval('P1D'),
                    (new \DateTime($end_date))->modify('+1 day')
                );

                foreach ($period as $date) {
                    $date_str = $date->format('Y-m-d');
                    
                    // Only include dates within the requested month
                    if ($date->format('Y-m') === "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                        if (!isset($events[$date_str])) {
                            $events[$date_str] = [];
                        }
                        
                        $events[$date_str][] = [
                            'id' => $event_id,
                            'title' => get_the_title(),
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'location' => get_post_meta($event_id, '_event_location', true),
                            'url' => get_permalink(),
                            'featured_image' => get_the_post_thumbnail_url($event_id, 'medium'),
                            'is_start' => $date_str === $start_date,
                            'is_end' => $date_str === $end_date,
                            'is_multi_day' => $start_date !== $end_date,
                            'is_full_day' => get_post_meta($event_id, '_event_is_full_day', true) === '1',
                            '_formatted_start_time' => get_post_meta($event_id, '_formatted_start_time', true),
                            '_formatted_end_time' => get_post_meta($event_id, '_formatted_end_time', true),
                            '_time_display' => get_post_meta($event_id, '_time_display', true)
                        ];
                    }
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success(['events' => $events]);
    }

    public function get_events() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_calendar_nonce')) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }

        $args = [
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_event_start',
            'order' => 'ASC'
        ];

        $posts = get_posts($args);
        $events = [];
        
        foreach ($posts as $post) {
            // Get basic event data
            $start_date = get_post_meta($post->ID, '_event_start', true);
            $end_date = get_post_meta($post->ID, '_event_end', true);
            $location = get_post_meta($post->ID, '_event_location', true);
            $color = get_post_meta($post->ID, '_event_color', true);
            
            // Get time values - check both regular meta fields and block editor fields
            $start_time = get_post_meta($post->ID, '_event_start_time', true);
            if (empty($start_time)) {
                $start_time = get_post_meta($post->ID, 'startTime', true);
            }
            
            $end_time = get_post_meta($post->ID, '_event_end_time', true);
            if (empty($end_time)) {
                $end_time = get_post_meta($post->ID, 'endTime', true);
            }
            
            // Check for full day status
            $is_full_day = get_post_meta($post->ID, '_event_is_full_day', true) === '1';
            
            // Try alternative field name (for block editor)
            if (!$is_full_day) {
                $is_full_day = get_post_meta($post->ID, 'all_day', true) === '1';
            }
            
            // Enhanced debug logging
            error_log("Event {$post->ID} ({$post->post_title}) - Time values:");
            error_log("  - Start time: " . ($start_time ?: 'empty'));
            error_log("  - End time: " . ($end_time ?: 'empty'));
            error_log("  - Is full day: " . ($is_full_day ? 'true' : 'false'));
            
            // Clean time values
            $start_time = $this->clean_time_value($start_time);
            $end_time = $this->clean_time_value($end_time);
            
            // If is_full_day is not explicitly set but times indicate full day, set it as full day
            if (!$is_full_day && 
                (empty($start_time) || $start_time === '00:00') && 
                (empty($end_time) || $end_time === '00:00')) {
                $is_full_day = true;
                error_log("  - Setting to full day based on time values");
            }
            
            // Set times to null for full day events to ensure proper handling in the frontend
            if ($is_full_day) {
                $start_time = null;
                $end_time = null;
                error_log("  - Full day event: setting time values to null");
                
                // Save this back to post meta if it wasn't already set
                update_post_meta($post->ID, '_event_is_full_day', '1');
                update_post_meta($post->ID, '_time_display', 'Full day');
            }
            
            // Default color if not set
            if (empty($color)) {
                $color = '#3788d8';
            }

            // Get pre-formatted time values (preferred approach)
            $formatted_start = get_post_meta($post->ID, '_formatted_start_time', true);
            $formatted_end = get_post_meta($post->ID, '_formatted_end_time', true);
            $time_display = get_post_meta($post->ID, '_time_display', true);
            
            // If not available, format them here and save for future use
            if (empty($time_display)) {
                if ($is_full_day) {
                    $time_display = 'Full day';
                    update_post_meta($post->ID, '_time_display', $time_display);
                } else if ($start_time && $end_time) {
                    $formatted_start = $formatted_start ?: $this->format_time($start_time);
                    $formatted_end = $formatted_end ?: $this->format_time($end_time);
                    $time_display = "$formatted_start - $formatted_end";
                    
                    update_post_meta($post->ID, '_formatted_start_time', $formatted_start);
                    update_post_meta($post->ID, '_formatted_end_time', $formatted_end);
                    update_post_meta($post->ID, '_time_display', $time_display);
                } else if ($start_time) {
                    $formatted_start = $formatted_start ?: $this->format_time($start_time);
                    $time_display = $formatted_start;
                    
                    update_post_meta($post->ID, '_formatted_start_time', $formatted_start);
                    update_post_meta($post->ID, '_time_display', $time_display);
                } else if ($end_time) {
                    $formatted_end = $formatted_end ?: $this->format_time($end_time);
                    $time_display = $formatted_end;
                    
                    update_post_meta($post->ID, '_formatted_end_time', $formatted_end);
                    update_post_meta($post->ID, '_time_display', $time_display);
                } else {
                    $time_display = 'Time not specified';
                    update_post_meta($post->ID, '_time_display', $time_display);
                }
            }
            
            error_log("  - Final time display: " . $time_display);

            $events[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'start' => $start_date,
                'end' => $end_date,
                'location' => $location,
                'color' => $color,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_full_day' => $is_full_day,
                'description' => $post->post_content,
                '_formatted_start_time' => $formatted_start ?: '',
                '_formatted_end_time' => $formatted_end ?: '',
                '_time_display' => $time_display ?: ''
            ];
        }

        wp_send_json_success(['events' => $events]);
    }

    /**
     * Save the full day status and time values when an event is saved
     * This handles the block editor save which may use different field names
     */
    public function save_post_meta($post_id) {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        
        // Check if this is an event post type
        if (get_post_type($post_id) !== 'ai_calendar_event') return;
        
        try {
            error_log("EventHandler: Saving post meta for event #$post_id");
            
            // Detect if this is a REST API request
            $is_rest_request = defined('REST_REQUEST') && REST_REQUEST;
            if ($is_rest_request) {
                error_log("This is a REST API request");
                
                // Get the request data
                $request_data = json_decode(file_get_contents('php://input'), true);
                error_log("REST request data: " . print_r($request_data, true));
                
                // Extract values from the request data
                $is_full_day = false;
                $is_full_day_explicitly_set = false;
                $start_time = '';
                $end_time = '';
                
                // Check if we have meta data in the request
                if (!empty($request_data) && isset($request_data['meta'])) {
                    // Check for full day status
                    if (isset($request_data['meta']['_event_is_full_day'])) {
                        $is_full_day = (bool)$request_data['meta']['_event_is_full_day'];
                        $is_full_day_explicitly_set = true;
                        error_log("REST: Found _event_is_full_day: " . ($is_full_day ? 'true' : 'false'));
                    } 
                    else if (isset($request_data['meta']['all_day'])) {
                        $is_full_day = (bool)$request_data['meta']['all_day'];
                        $is_full_day_explicitly_set = true;
                        error_log("REST: Found all_day: " . ($is_full_day ? 'true' : 'false'));
                    }
                    
                    // Get time values
                    if (isset($request_data['meta']['_event_start_time'])) {
                        $start_time = sanitize_text_field(trim($request_data['meta']['_event_start_time']));
                        error_log("REST: Found _event_start_time: $start_time");
                    } 
                    else if (isset($request_data['meta']['startTime'])) {
                        $start_time = sanitize_text_field(trim($request_data['meta']['startTime']));
                        error_log("REST: Found startTime: $start_time");
                    }
                    
                    if (isset($request_data['meta']['_event_end_time'])) {
                        $end_time = sanitize_text_field(trim($request_data['meta']['_event_end_time']));
                        error_log("REST: Found _event_end_time: $end_time");
                    } 
                    else if (isset($request_data['meta']['endTime'])) {
                        $end_time = sanitize_text_field(trim($request_data['meta']['endTime']));
                        error_log("REST: Found endTime: $end_time");
                    }
                }
                
                // If full day status wasn't explicitly set, check existing value
                if (!$is_full_day_explicitly_set) {
                    $current_full_day = get_post_meta($post_id, '_event_is_full_day', true);
                    $is_full_day = $current_full_day === '1';
                    error_log("REST: Using existing full day value: " . ($is_full_day ? 'true' : 'false'));
                }
                
                // If this is not a full day event and no times were provided, set default times
                if (!$is_full_day && empty($start_time) && empty($end_time)) {
                    $start_time = '09:00';
                    $end_time = '17:00';
                    error_log("REST: Setting default times for non-full day event");
                }
                
                // Save the values
                update_post_meta($post_id, '_event_is_full_day', $is_full_day ? '1' : '0');
                
                if ($is_full_day) {
                    // For full day events, clear time values
                    update_post_meta($post_id, '_event_start_time', '');
                    update_post_meta($post_id, '_event_end_time', '');
                    update_post_meta($post_id, '_formatted_start_time', '');
                    update_post_meta($post_id, '_formatted_end_time', '');
                    update_post_meta($post_id, '_time_display', 'Full day');
                    update_post_meta($post_id, 'startTime', '');
                    update_post_meta($post_id, 'endTime', '');
                    update_post_meta($post_id, 'all_day', '1');
                    
                    error_log("REST: Saved as full day event");
                } else {
                    // For events with specific times
                    update_post_meta($post_id, '_event_start_time', $start_time);
                    update_post_meta($post_id, '_event_end_time', $end_time);
                    update_post_meta($post_id, 'startTime', $start_time);
                    update_post_meta($post_id, 'endTime', $end_time);
                    update_post_meta($post_id, 'all_day', '0');
                    
                    // Format times for display
                    $formatted_start = $this->format_time($start_time);
                    $formatted_end = $this->format_time($end_time);
                    
                    update_post_meta($post_id, '_formatted_start_time', $formatted_start);
                    update_post_meta($post_id, '_formatted_end_time', $formatted_end);
                    
                    // Create time display string
                    if (!empty($formatted_start) && !empty($formatted_end)) {
                        $time_display = "$formatted_start - $formatted_end";
                    } else if (!empty($formatted_start)) {
                        $time_display = $formatted_start;
                    } else if (!empty($formatted_end)) {
                        $time_display = $formatted_end;
                    } else {
                        $time_display = 'Time not specified';
                    }
                    
                    update_post_meta($post_id, '_time_display', $time_display);
                    error_log("REST: Saved with specific times: $time_display");
                }
                
                return; // Skip the rest of the function for REST API requests
            }
            
            // Continue with regular (non-REST) post handling
            
            // Check for full day event status from various sources
            $is_full_day = false;
            $is_full_day_explicitly_set = false;
            
            // Check standard _event_is_full_day field
            if (isset($_POST['_event_is_full_day'])) {
                $is_full_day = ($_POST['_event_is_full_day'] === '1' || $_POST['_event_is_full_day'] === 'on' || $_POST['_event_is_full_day'] === true);
                $is_full_day_explicitly_set = true;
                error_log("Found _event_is_full_day in POST: " . print_r($_POST['_event_is_full_day'], true));
            }
            
            // REST API requests don't use $_POST, so check for the request data differently
            if (defined('REST_REQUEST') && REST_REQUEST) {
                $request = json_decode(file_get_contents('php://input'), true);
                if (!empty($request) && isset($request['meta'])) {
                    if (isset($request['meta']['_event_is_full_day'])) {
                        $is_full_day = ($request['meta']['_event_is_full_day'] === '1' || $request['meta']['_event_is_full_day'] === true);
                        $is_full_day_explicitly_set = true;
                    } else if (isset($request['meta']['all_day'])) {
                        $is_full_day = ($request['meta']['all_day'] === '1' || $request['meta']['all_day'] === true);
                        $is_full_day_explicitly_set = true;
                    }
                }
            }
            
            // Check for block editor fields that might indicate full day
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                if (isset($_POST['meta']['_event_is_full_day'])) {
                    $is_full_day = ($_POST['meta']['_event_is_full_day'] === '1' || $_POST['meta']['_event_is_full_day'] === 'on' || $_POST['meta']['_event_is_full_day'] === true);
                    $is_full_day_explicitly_set = true;
                    error_log("Found _event_is_full_day in meta: " . print_r($_POST['meta']['_event_is_full_day'], true));
                }
                
                // Also check for all_day which might be used by block editor
                if (isset($_POST['meta']['all_day'])) {
                    $is_full_day = ($_POST['meta']['all_day'] === '1' || $_POST['meta']['all_day'] === 'on' || $_POST['meta']['all_day'] === true);
                    $is_full_day_explicitly_set = true;
                    error_log("Found all_day in meta: " . print_r($_POST['meta']['all_day'], true));
                }
            }
            
            // Also check for all_day directly in POST
            if (isset($_POST['all_day'])) {
                $is_full_day = ($_POST['all_day'] === '1' || $_POST['all_day'] === 'on' || $_POST['all_day'] === true);
                $is_full_day_explicitly_set = true;
                error_log("Found all_day in POST: " . print_r($_POST['all_day'], true));
            }
            
            // If still not set, get the current value from post meta
            if (!$is_full_day_explicitly_set) {
                $current_full_day = get_post_meta($post_id, '_event_is_full_day', true);
                $is_full_day = $current_full_day === '1';
                error_log("Using existing full day value: " . ($is_full_day ? 'true' : 'false'));
            }
            
            // Check if both start and end times are empty, which might indicate full day
            $start_time = '';
            $end_time = '';
            $time_values_provided = false;
            
            // Check standard time fields
            if (isset($_POST['_event_start_time'])) {
                $start_time = sanitize_text_field(trim($_POST['_event_start_time']));
                $time_values_provided = true;
            }
            
            if (isset($_POST['_event_end_time'])) {
                $end_time = sanitize_text_field(trim($_POST['_event_end_time']));
                $time_values_provided = true;
            }
            
            // Check REST API data
            if (defined('REST_REQUEST') && REST_REQUEST) {
                $request = json_decode(file_get_contents('php://input'), true);
                if (!empty($request) && isset($request['meta'])) {
                    if (isset($request['meta']['_event_start_time'])) {
                        $start_time = sanitize_text_field(trim($request['meta']['_event_start_time']));
                        $time_values_provided = true;
                    } else if (isset($request['meta']['startTime'])) {
                        $start_time = sanitize_text_field(trim($request['meta']['startTime']));
                        $time_values_provided = true;
                    }
                    
                    if (isset($request['meta']['_event_end_time'])) {
                        $end_time = sanitize_text_field(trim($request['meta']['_event_end_time']));
                        $time_values_provided = true;
                    } else if (isset($request['meta']['endTime'])) {
                        $end_time = sanitize_text_field(trim($request['meta']['endTime']));
                        $time_values_provided = true;
                    }
                }
            }
            
            // Check block editor meta fields
            if (isset($_POST['meta']) && is_array($_POST['meta'])) {
                if (isset($_POST['meta']['_event_start_time'])) {
                    $start_time = sanitize_text_field(trim($_POST['meta']['_event_start_time']));
                    $time_values_provided = true;
                }
                
                if (isset($_POST['meta']['_event_end_time'])) {
                    $end_time = sanitize_text_field(trim($_POST['meta']['_event_end_time']));
                    $time_values_provided = true;
                }
                
                // Also check alternative field names
                if (isset($_POST['meta']['startTime'])) {
                    $start_time = sanitize_text_field(trim($_POST['meta']['startTime']));
                    $time_values_provided = true;
                }
                
                if (isset($_POST['meta']['endTime'])) {
                    $end_time = sanitize_text_field(trim($_POST['meta']['endTime']));
                    $time_values_provided = true;
                }
            }
            
            // Direct block editor fields
            if (isset($_POST['startTime'])) {
                $start_time = sanitize_text_field(trim($_POST['startTime']));
                $time_values_provided = true;
            }
            
            if (isset($_POST['endTime'])) {
                $end_time = sanitize_text_field(trim($_POST['endTime']));
                $time_values_provided = true;
            }
            
            // Log the discovered time values
            error_log("Found time values: start_time='$start_time', end_time='$end_time', time_values_provided=" . ($time_values_provided ? 'true' : 'false'));
            
            // If times are empty and full day wasn't explicitly set, only set as full day if we're sure
            // This is a critical fix: only mark as full day if explicitly set or if time values were actually provided but are empty
            if (!$is_full_day_explicitly_set && $time_values_provided && empty($start_time) && empty($end_time)) {
                $is_full_day = true;
                error_log("Setting is_full_day=true because both times are empty and time values were provided");
            } else if (!$time_values_provided && !$is_full_day_explicitly_set) {
                // If no time values were provided at all and full day wasn't set, check existing values
                $existing_start = get_post_meta($post_id, '_event_start_time', true);
                $existing_end = get_post_meta($post_id, '_event_end_time', true);
                
                // If there are existing non-empty and non-00:00 time values, it's not a full day event
                if ((!empty($existing_start) && $existing_start !== '00:00' && $existing_start !== '0:00') || 
                    (!empty($existing_end) && $existing_end !== '00:00' && $existing_end !== '0:00')) {
                    $is_full_day = false;
                    $start_time = $existing_start;
                    $end_time = $existing_end;
                    error_log("Keeping existing time values: start='$existing_start', end='$existing_end'");
                }
            }
            
            // Save the full day status
            update_post_meta($post_id, '_event_is_full_day', $is_full_day ? '1' : '0');
            error_log("Saved _event_is_full_day=" . ($is_full_day ? '1' : '0'));
            
            // Handle time values based on full day status
            if ($is_full_day) {
                // For full day events, clear any existing time values
                update_post_meta($post_id, '_event_start_time', '');
                update_post_meta($post_id, '_event_end_time', '');
                
                // Also clear the block editor time fields
                update_post_meta($post_id, 'startTime', '');
                update_post_meta($post_id, 'endTime', '');
                
                // Set a formatted time display for full day events
                update_post_meta($post_id, '_time_display', 'Full day');
                update_post_meta($post_id, '_formatted_start_time', '');
                update_post_meta($post_id, '_formatted_end_time', '');
                error_log("Set full day time display: 'Full day'");
            } else {
                // For events with specific times, save the times to all potential fields
                if (empty($start_time) && empty($end_time) && !$time_values_provided) {
                    // If no time values were provided, set default times for regular events
                    $start_time = '09:00';
                    $end_time = '17:00';
                    error_log("Setting default times for regular event: start='$start_time', end='$end_time'");
                }
                
                if (!empty($start_time)) {
                    update_post_meta($post_id, '_event_start_time', $start_time);
                    update_post_meta($post_id, 'startTime', $start_time);
                    
                    // Format for display
                    $formatted_start = $this->format_time($start_time);
                    update_post_meta($post_id, '_formatted_start_time', $formatted_start);
                    error_log("Saved start time: $start_time → $formatted_start");
                } else {
                    // Clear the field if empty
                    update_post_meta($post_id, '_event_start_time', '');
                    update_post_meta($post_id, 'startTime', '');
                    update_post_meta($post_id, '_formatted_start_time', '');
                }
                
                if (!empty($end_time)) {
                    update_post_meta($post_id, '_event_end_time', $end_time);
                    update_post_meta($post_id, 'endTime', $end_time);
                    
                    // Format for display
                    $formatted_end = $this->format_time($end_time);
                    update_post_meta($post_id, '_formatted_end_time', $formatted_end);
                    error_log("Saved end time: $end_time → $formatted_end");
                } else {
                    // Clear the field if empty
                    update_post_meta($post_id, '_event_end_time', '');
                    update_post_meta($post_id, 'endTime', '');
                    update_post_meta($post_id, '_formatted_end_time', '');
                }
                
                // Create a formatted time display
                if (!empty($start_time) && !empty($end_time)) {
                    $formatted_start = $this->format_time($start_time);
                    $formatted_end = $this->format_time($end_time);
                    $time_display = "$formatted_start - $formatted_end";
                    update_post_meta($post_id, '_time_display', $time_display);
                    error_log("Set time display: '$time_display'");
                } else if (!empty($start_time)) {
                    $formatted_start = $this->format_time($start_time);
                    update_post_meta($post_id, '_time_display', $formatted_start);
                    error_log("Set time display: '$formatted_start'");
                } else if (!empty($end_time)) {
                    $formatted_end = $this->format_time($end_time);
                    update_post_meta($post_id, '_time_display', $formatted_end);
                    error_log("Set time display: '$formatted_end'");
                } else {
                    update_post_meta($post_id, '_time_display', 'Time not specified');
                    error_log("Set time display: 'Time not specified'");
                }
            }
        } catch (Exception $e) {
            // Log any errors that occur during saving
            error_log("Error saving event meta: " . $e->getMessage());
        }
    }

    /**
     * AJAX handler to get detailed event information with fresh time values
     */
    public function get_event_details() {
        // Verify nonce
        if (!check_ajax_referer('ai_calendar_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token.', 'ai-calendar')]);
            return;
        }
        
        // Get event IDs from request
        $event_ids = isset($_POST['event_ids']) ? (array)$_POST['event_ids'] : [];
        
        // If no event IDs provided, return error
        if (empty($event_ids)) {
            wp_send_json_error(['message' => __('No event IDs provided.', 'ai-calendar')]);
            return;
        }
        
        // Sanitize event IDs (ensure they're integers)
        $event_ids = array_map('intval', $event_ids);
        
        // Log request for debugging
        error_log("Getting detailed data for events: " . implode(', ', $event_ids));
        
        // Fetch events
        $events = $this->get_events_by_ids($event_ids);
        
        // Return the events
        wp_send_json_success([
            'events' => $events,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get events by IDs with fresh time values
     * 
     * @param array $event_ids Array of event IDs
     * @return array Array of event data
     */
    private function get_events_by_ids($event_ids) {
        if (empty($event_ids)) {
            return [];
        }
        
        $events = [];
        
        // Query the events
        $args = [
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'post__in' => $event_ids,
            'orderby' => 'post__in', // Preserve the order of the requested IDs
        ];
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get all possible time-related metadata
                $all_meta = get_post_meta($post_id);
                
                // Extract key metadata
                $start_date = get_post_meta($post_id, '_event_start_date', true);
                $end_date = get_post_meta($post_id, '_event_end_date', true) ?: $start_date;
                $is_full_day = get_post_meta($post_id, '_event_is_full_day', true) === '1';
                $start_time = get_post_meta($post_id, '_event_start_time', true);
                $end_time = get_post_meta($post_id, '_event_end_time', true);
                $time_display = get_post_meta($post_id, '_time_display', true);
                $formatted_start_time = get_post_meta($post_id, '_formatted_start_time', true);
                $formatted_end_time = get_post_meta($post_id, '_formatted_end_time', true);
                
                // Also check alternative field names
                if (empty($start_time) || $start_time === '00:00') {
                    $start_time = isset($all_meta['startTime']) ? $all_meta['startTime'][0] : '';
                }
                
                if (empty($end_time) || $end_time === '00:00') {
                    $end_time = isset($all_meta['endTime']) ? $all_meta['endTime'][0] : '';
                }
                
                // Debug log
                error_log("Event ID $post_id ({$query->post->post_title}) - Fresh time values: start='$start_time', end='$end_time', display='$time_display'");
                
                // Basic event data
                $event = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'is_full_day' => $is_full_day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    '_time_display' => $time_display,
                    '_formatted_start_time' => $formatted_start_time,
                    '_formatted_end_time' => $formatted_end_time,
                    'location' => get_post_meta($post_id, '_event_location', true),
                    'url' => get_post_meta($post_id, '_event_url', true) ?: get_permalink($post_id),
                    'color' => get_post_meta($post_id, '_event_color', true) ?: '#3788d8',
                    'description' => get_the_excerpt(),
                    'featured_image' => get_the_post_thumbnail_url($post_id, 'medium'),
                    'is_multi_day' => $start_date !== $end_date
                ];
                
                // Apply the filter to ensure proper time values
                $event = apply_filters('ai_calendar_event_data', $event);
                
                // Debug log after filter
                error_log("Event ID $post_id after filter - Time display: {$event['_time_display']}");
                
                $events[] = $event;
            }
            wp_reset_postdata();
        }
        
        return $events;
    }
} 