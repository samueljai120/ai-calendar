protected function get_event_data($post) {
    $post_id = $post->ID;
    
    // Get event meta
    $start_date = get_post_meta($post_id, '_event_start_date', true);
    $end_date = get_post_meta($post_id, '_event_end_date', true) ?: $start_date;
    $start_time = get_post_meta($post_id, '_event_start_time', true);
    $end_time = get_post_meta($post_id, '_event_end_time', true);
    
    error_log("Calendar::get_event_data - Event ID $post_id - Raw times: start='$start_time', end='$end_time'");
    
    // Get pre-formatted time values (if available)
    $formatted_start_time = get_post_meta($post_id, '_formatted_start_time', true);
    $formatted_end_time = get_post_meta($post_id, '_formatted_end_time', true);
    $time_display = get_post_meta($post_id, '_time_display', true);
    
    // Determine if this is a full day event
    $is_full_day = get_post_meta($post_id, '_event_is_full_day', true) === '1';
    
    // Also check other ways to determine full day
    if (!$is_full_day) {
        $is_full_day = empty($start_time) && empty($end_time);
        $is_full_day = $is_full_day || ($start_time === '00:00' && $end_time === '00:00');
        $is_full_day = $is_full_day || ($start_time === '0:00' && $end_time === '0:00');
        $is_full_day = $is_full_day || (bool)get_post_meta($post_id, '_calculated_full_day', true);
    }
    
    error_log("Calendar::get_event_data - Event ID $post_id - Is full day: " . ($is_full_day ? 'true' : 'false'));
    
    // Build event data
    $event = array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'start_date' => $start_date,
        'end_date' => $end_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'is_full_day' => $is_full_day,
        'is_multi_day' => $start_date !== $end_date,
        'location' => get_post_meta($post_id, '_event_location', true),
        'url' => get_permalink($post_id),
        'description' => get_the_excerpt($post_id),
        'featured_image' => get_the_post_thumbnail_url($post_id, 'medium'),
    );
    
    // Add pre-formatted time values if available
    if (!empty($formatted_start_time)) {
        $event['_formatted_start_time'] = $formatted_start_time;
    }
    
    if (!empty($formatted_end_time)) {
        $event['_formatted_end_time'] = $formatted_end_time;
    }
    
    if (!empty($time_display)) {
        $event['_time_display'] = $time_display;
    } else {
        // Generate a time display if one doesn't exist
        if ($is_full_day) {
            $event['_time_display'] = 'Full day';
        } else if (!empty($start_time) && !empty($end_time)) {
            $start_formatted = date('g:i A', strtotime($start_time));
            $end_formatted = date('g:i A', strtotime($end_time));
            $event['_time_display'] = "$start_formatted - $end_formatted";
            $event['_formatted_start_time'] = $start_formatted;
            $event['_formatted_end_time'] = $end_formatted;
        } else if (!empty($start_time)) {
            $start_formatted = date('g:i A', strtotime($start_time));
            $event['_time_display'] = $start_formatted;
            $event['_formatted_start_time'] = $start_formatted;
        } else if (!empty($end_time)) {
            $end_formatted = date('g:i A', strtotime($end_time));
            $event['_time_display'] = $end_formatted;
            $event['_formatted_end_time'] = $end_formatted;
        }
    }
    
    // Add debugging info
    $event['_debug'] = array(
        'raw_start_time' => $start_time,
        'raw_end_time' => $end_time,
        'formatted_start' => $formatted_start_time,
        'formatted_end' => $formatted_end_time,
        'time_display' => $time_display,
        'is_full_day' => $is_full_day,
    );
    
    return $event;
} 