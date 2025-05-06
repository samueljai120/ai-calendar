<?php
// Add nonce for security
wp_nonce_field('ai_calendar_event_meta_box', 'ai_calendar_event_meta_box_nonce');

// Get existing values
$start_date = get_post_meta($post->ID, '_event_start', true);
$end_date = get_post_meta($post->ID, '_event_end', true);
$event_type = get_post_meta($post->ID, '_event_type', true);
$location = get_post_meta($post->ID, '_event_location', true);
$event_url = get_post_meta($post->ID, '_event_url', true);
$registration_email = get_post_meta($post->ID, '_registration_email', true);
$registration_deadline = get_post_meta($post->ID, '_registration_deadline', true);
$organizer_info = get_post_meta($post->ID, '_organizer_info', true);
$dress_code = get_post_meta($post->ID, '_dress_code', true);
$age_restrictions = get_post_meta($post->ID, '_age_restrictions', true);
$dietary_preferences = get_post_meta($post->ID, '_dietary_preferences', true) ?: array();
$color = get_post_meta($post->ID, '_event_color', true) ?: '#3788d8';

// Recurring event fields
$is_recurring = get_post_meta($post->ID, '_event_recurring', true);
$recurrence_type = get_post_meta($post->ID, '_event_recurrence_type', true);
$recurrence_interval = get_post_meta($post->ID, '_event_recurrence_interval', true) ?: 1;
$recurrence_end_date = get_post_meta($post->ID, '_event_recurrence_end_date', true);
?>

<div class="ai-calendar-event-details">
    <!-- Basic Event Information -->
    <div class="event-section">
        <h4><?php _e('Event Timing', 'ai-calendar'); ?></h4>
        <div class="event-date-time-group">
            <p>
                <label for="_event_start"><?php _e('Start Date/Time:', 'ai-calendar'); ?></label><br>
                <input type="datetime-local" 
                       id="_event_start" 
                       name="_event_start" 
                       value="<?php echo esc_attr($start_date); ?>" 
                       class="widefat" 
                       required>
            </p>
            <p>
                <label for="_event_end"><?php _e('End Date/Time:', 'ai-calendar'); ?></label><br>
                <input type="datetime-local" 
                       id="_event_end" 
                       name="_event_end" 
                       value="<?php echo esc_attr($end_date); ?>" 
                       class="widefat" 
                       required>
            </p>
        </div>
    </div>

    <div class="event-section">
        <h4><?php _e('Event Type & Location', 'ai-calendar'); ?></h4>
        <p>
            <label for="_event_type"><?php _e('Event Type:', 'ai-calendar'); ?></label><br>
            <select id="_event_type" name="_event_type" class="widefat">
                <option value="in-person" <?php selected($event_type, 'in-person'); ?>><?php _e('In-Person', 'ai-calendar'); ?></option>
                <option value="virtual" <?php selected($event_type, 'virtual'); ?>><?php _e('Virtual', 'ai-calendar'); ?></option>
                <option value="hybrid" <?php selected($event_type, 'hybrid'); ?>><?php _e('Hybrid', 'ai-calendar'); ?></option>
            </select>
        </p>
        <p>
            <label for="_event_location"><?php _e('Location:', 'ai-calendar'); ?></label><br>
            <input type="text" 
                   id="_event_location" 
                   name="_event_location" 
                   value="<?php echo esc_attr($location); ?>" 
                   class="widefat location-input" 
                   placeholder="<?php esc_attr_e('Enter event location', 'ai-calendar'); ?>">
            <div id="map-preview" style="height: 200px; margin-top: 10px; display: none;"></div>
        </p>
    </div>

    <!-- Registration Details -->
    <div class="event-section">
        <h4><?php _e('Registration Information', 'ai-calendar'); ?></h4>
        <p>
            <label for="_event_url"><?php _e('Event URL:', 'ai-calendar'); ?></label><br>
            <input type="url" 
                   id="_event_url" 
                   name="_event_url" 
                   value="<?php echo esc_attr($event_url); ?>" 
                   class="widefat" 
                   placeholder="<?php esc_attr_e('https://', 'ai-calendar'); ?>">
        </p>
        <p>
            <label for="_registration_email"><?php _e('Registration Email:', 'ai-calendar'); ?></label><br>
            <input type="email" 
                   id="_registration_email" 
                   name="_registration_email" 
                   value="<?php echo esc_attr($registration_email); ?>" 
                   class="widefat">
        </p>
        <p>
            <label for="_registration_deadline"><?php _e('Registration Deadline:', 'ai-calendar'); ?></label><br>
            <input type="datetime-local" 
                   id="_registration_deadline" 
                   name="_registration_deadline" 
                   value="<?php echo esc_attr($registration_deadline); ?>" 
                   class="widefat">
        </p>
    </div>

    <!-- Additional Information -->
    <div class="event-section">
        <h4><?php _e('Additional Information', 'ai-calendar'); ?></h4>
        <p>
            <label for="_organizer_info"><?php _e('Organizer Information:', 'ai-calendar'); ?></label><br>
            <input type="text" 
                   id="_organizer_info" 
                   name="_organizer_info" 
                   value="<?php echo esc_attr($organizer_info); ?>" 
                   class="widefat">
        </p>
        <p>
            <label for="_dress_code"><?php _e('Dress Code:', 'ai-calendar'); ?></label><br>
            <input type="text" 
                   id="_dress_code" 
                   name="_dress_code" 
                   value="<?php echo esc_attr($dress_code); ?>" 
                   class="widefat">
        </p>
        <p>
            <label for="_age_restrictions"><?php _e('Age Restrictions:', 'ai-calendar'); ?></label><br>
            <input type="number" 
                   id="_age_restrictions" 
                   name="_age_restrictions" 
                   value="<?php echo esc_attr($age_restrictions); ?>" 
                   min="0" 
                   class="widefat">
        </p>
        <p>
            <label><?php _e('Dietary Preferences:', 'ai-calendar'); ?></label><br>
            <?php
            $dietary_options = array(
                'vegetarian' => __('Vegetarian', 'ai-calendar'),
                'vegan' => __('Vegan', 'ai-calendar'),
                'gluten-free' => __('Gluten-Free', 'ai-calendar'),
                'dairy-free' => __('Dairy-Free', 'ai-calendar'),
                'nut-free' => __('Nut-Free', 'ai-calendar')
            );
            foreach ($dietary_options as $value => $label) :
                ?>
                <label class="checkbox-label">
                    <input type="checkbox" 
                           name="_dietary_preferences[]" 
                           value="<?php echo esc_attr($value); ?>"
                           <?php checked(in_array($value, (array)$dietary_preferences)); ?>>
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </p>
    </div>

    <!-- Event Color -->
    <div class="event-section">
        <h4><?php _e('Event Color', 'ai-calendar'); ?></h4>
        <p>
            <input type="color" 
                   id="_event_color" 
                   name="_event_color" 
                   value="<?php echo esc_attr($color); ?>" 
                   class="widefat">
        </p>
    </div>

    <!-- Recurring Event Options -->
    <div class="event-section">
        <h4><?php _e('Recurring Event', 'ai-calendar'); ?></h4>
        <p>
            <label>
                <input type="checkbox" 
                       id="_event_recurring" 
                       name="_event_recurring" 
                       value="1" 
                       <?php checked($is_recurring, '1'); ?>>
                <?php _e('This is a recurring event', 'ai-calendar'); ?>
            </label>
        </p>

        <div class="recurring-options" style="display: <?php echo $is_recurring ? 'block' : 'none'; ?>;">
            <p>
                <label for="_event_recurrence_type"><?php _e('Repeat Every:', 'ai-calendar'); ?></label><br>
                <select id="_event_recurrence_type" name="_event_recurrence_type" class="widefat">
                    <option value="daily" <?php selected($recurrence_type, 'daily'); ?>><?php _e('Day', 'ai-calendar'); ?></option>
                    <option value="weekly" <?php selected($recurrence_type, 'weekly'); ?>><?php _e('Week', 'ai-calendar'); ?></option>
                    <option value="monthly" <?php selected($recurrence_type, 'monthly'); ?>><?php _e('Month', 'ai-calendar'); ?></option>
                    <option value="yearly" <?php selected($recurrence_type, 'yearly'); ?>><?php _e('Year', 'ai-calendar'); ?></option>
                </select>
            </p>

            <p>
                <label for="_event_recurrence_interval"><?php _e('Interval:', 'ai-calendar'); ?></label><br>
                <input type="number" 
                       id="_event_recurrence_interval" 
                       name="_event_recurrence_interval" 
                       value="<?php echo esc_attr($recurrence_interval); ?>" 
                       min="1" 
                       max="365" 
                       class="small-text">
                <span class="description"><?php _e('How often the event should repeat', 'ai-calendar'); ?></span>
            </p>

            <p>
                <label for="_event_recurrence_end_date"><?php _e('End Repeat:', 'ai-calendar'); ?></label><br>
                <input type="date" 
                       id="_event_recurrence_end_date" 
                       name="_event_recurrence_end_date" 
                       value="<?php echo esc_attr($recurrence_end_date); ?>" 
                       class="widefat">
            </p>
        </div>
    </div>
</div>

<style>
.ai-calendar-event-details {
    padding: 12px;
}

.event-section {
    margin-bottom: 24px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
}

.event-section h4 {
    margin: 0 0 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e4e7;
    color: #1e1e1e;
    font-size: 14px;
    font-weight: 600;
}

.event-date-time-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.ai-calendar-event-details input[type="datetime-local"],
.ai-calendar-event-details input[type="date"],
.ai-calendar-event-details input[type="email"],
.ai-calendar-event-details input[type="url"],
.ai-calendar-event-details input[type="number"],
.ai-calendar-event-details select {
    width: 100%;
    max-width: 100%;
}

.ai-calendar-event-details input[type="color"] {
    width: 100px;
    height: 40px;
    padding: 2px;
}

.recurring-options {
    margin-left: 20px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.description {
    color: #666;
    font-style: italic;
    margin-left: 10px;
}

.checkbox-label {
    display: block;
    margin-bottom: 8px;
}

.location-input {
    margin-bottom: 8px;
}

#map-preview {
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize color picker
    if ($.fn.wpColorPicker) {
        $('#_event_color').wpColorPicker();
    }

    // Toggle recurring options
    $('#_event_recurring').on('change', function() {
        $('.recurring-options').toggle(this.checked);
    });

    // Initialize Google Maps if available
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        var map;
        var marker;
        var geocoder = new google.maps.Geocoder();
        
        function initMap() {
            map = new google.maps.Map(document.getElementById('map-preview'), {
                zoom: 13,
                center: {lat: -34.397, lng: 150.644}
            });
            
            marker = new google.maps.Marker({
                map: map,
                draggable: true
            });
        }

        function updateMap(address) {
            geocoder.geocode({'address': address}, function(results, status) {
                if (status === 'OK') {
                    $('#map-preview').show();
                    map.setCenter(results[0].geometry.location);
                    marker.setPosition(results[0].geometry.location);
                }
            });
        }

        // Initialize map and handle location input
        initMap();
        
        var locationInput = $('#_event_location');
        var existingLocation = locationInput.val();
        if (existingLocation) {
            updateMap(existingLocation);
        }

        locationInput.on('change', function() {
            updateMap($(this).val());
        });
    }
});
</script> 