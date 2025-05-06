<?php
/**
 * Template for event details meta box
 * 
 * @var string $start_date Event start date
 * @var string $end_date Event end date
 * @var string $location Event location
 * @var string $color Event color
 */
?>
<div class="ai-calendar-event-details">
    <p>
        <label for="event_start"><?php _e('Start Date/Time:', 'ai-calendar'); ?></label><br>
        <input type="datetime-local" id="event_start" name="event_start" value="<?php echo esc_attr($start_date); ?>" class="widefat">
    </p>

    <p>
        <label for="event_end"><?php _e('End Date/Time:', 'ai-calendar'); ?></label><br>
        <input type="datetime-local" id="event_end" name="event_end" value="<?php echo esc_attr($end_date); ?>" class="widefat">
    </p>

    <p>
        <label for="event_location"><?php _e('Location:', 'ai-calendar'); ?></label><br>
        <input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($location); ?>" class="widefat" placeholder="<?php esc_attr_e('Event location (optional)', 'ai-calendar'); ?>">
    </p>

    <p>
        <label for="event_color"><?php _e('Event Color:', 'ai-calendar'); ?></label><br>
        <input type="color" id="event_color" name="event_color" value="<?php echo esc_attr($color); ?>" class="widefat">
    </p>
</div>

<style>
.ai-calendar-event-details input[type="datetime-local"] {
    padding: 5px;
    width: 100%;
    max-width: 300px;
}

.ai-calendar-event-details input[type="color"] {
    padding: 0;
    width: 100px;
    height: 40px;
}

.ai-calendar-event-details label {
    font-weight: 600;
    margin-bottom: 5px;
    display: inline-block;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize color picker if wp-color-picker is available
    if ($.fn.wpColorPicker) {
        $('#event_color').wpColorPicker();
    }
});
</script> 