<?php

class EventAdmin {
    public function __construct() {
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_event_meta_boxes']);
        
        // Register admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Save event meta
        add_action('save_post_ai_calendar_event', [$this, 'save_event_meta']);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post;
        
        // Only load on event edit pages
        if (($hook == 'post-new.php' || $hook == 'post.php') && 
            isset($post) && $post->post_type === 'ai_calendar_event') {
            
            // Register and enqueue the admin styles
            wp_register_style(
                'ai-calendar-admin-css',
                AI_CALENDAR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                AI_CALENDAR_VERSION
            );
            
            wp_enqueue_style('ai-calendar-admin-css');
            
            // Enqueue jQuery UI datepicker if needed
            wp_enqueue_script('jquery-ui-datepicker');
            
            // Enqueue color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Custom admin script
            wp_register_script(
                'ai-calendar-admin-js',
                AI_CALENDAR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-color-picker'),
                AI_CALENDAR_VERSION,
                true
            );
            
            wp_enqueue_script('ai-calendar-admin-js');
        }
    }
    
    /**
     * Save event meta data
     */
    public function save_event_meta($post_id) {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        
        // Verify nonce
        if (!isset($_POST['ai_calendar_event_nonce']) || !wp_verify_nonce($_POST['ai_calendar_event_nonce'], 'ai_calendar_event_save')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save full day status
        $is_full_day = isset($_POST['_event_is_full_day']) ? '1' : '0';
        update_post_meta($post_id, '_event_is_full_day', $is_full_day);
        update_post_meta($post_id, 'all_day', $is_full_day);
        
        // Save event dates
        if (isset($_POST['_event_start_date'])) {
            update_post_meta($post_id, '_event_start_date', sanitize_text_field($_POST['_event_start_date']));
        }
        
        if (isset($_POST['_event_end_date'])) {
            update_post_meta($post_id, '_event_end_date', sanitize_text_field($_POST['_event_end_date']));
        }
        
        // Handle time fields based on full day status
        if ($is_full_day === '1') {
            // Clear time values for full day events
            update_post_meta($post_id, '_event_start_time', '');
            update_post_meta($post_id, '_event_end_time', '');
            update_post_meta($post_id, 'startTime', '');
            update_post_meta($post_id, 'endTime', '');
            update_post_meta($post_id, '_formatted_start_time', '');
            update_post_meta($post_id, '_formatted_end_time', '');
            update_post_meta($post_id, '_time_display', 'Full day');
        } else {
            // Save time values for regular events
            $start_time = isset($_POST['_event_start_time']) ? sanitize_text_field($_POST['_event_start_time']) : '09:00';
            $end_time = isset($_POST['_event_end_time']) ? sanitize_text_field($_POST['_event_end_time']) : '17:00';
            
            // Set default times if empty
            if (empty($start_time)) $start_time = '09:00';
            if (empty($end_time)) $end_time = '17:00';
            
            update_post_meta($post_id, '_event_start_time', $start_time);
            update_post_meta($post_id, '_event_end_time', $end_time);
            update_post_meta($post_id, 'startTime', $start_time);
            update_post_meta($post_id, 'endTime', $end_time);
            
            // Format times for display (call the format_time function from EventHandler)
            $event_handler = new \AiCalendar\Ajax\EventHandler();
            
            // Use reflection to access private method
            $format_method = new \ReflectionMethod($event_handler, 'format_time');
            $format_method->setAccessible(true);
            
            $formatted_start = $format_method->invoke($event_handler, $start_time);
            $formatted_end = $format_method->invoke($event_handler, $end_time);
            
            update_post_meta($post_id, '_formatted_start_time', $formatted_start);
            update_post_meta($post_id, '_formatted_end_time', $formatted_end);
            
            // Create time display
            if (!empty($formatted_start) && !empty($formatted_end)) {
                $time_display = $formatted_start . ' - ' . $formatted_end;
            } else if (!empty($formatted_start)) {
                $time_display = $formatted_start;
            } else if (!empty($formatted_end)) {
                $time_display = $formatted_end;
            } else {
                $time_display = 'Time not specified';
            }
            
            update_post_meta($post_id, '_time_display', $time_display);
        }
        
        // Save other meta fields
        if (isset($_POST['_event_location'])) {
            update_post_meta($post_id, '_event_location', sanitize_text_field($_POST['_event_location']));
        }
        
        if (isset($_POST['_event_url'])) {
            update_post_meta($post_id, '_event_url', esc_url_raw($_POST['_event_url']));
        }
        
        if (isset($_POST['_event_color'])) {
            update_post_meta($post_id, '_event_color', sanitize_hex_color($_POST['_event_color']));
        }
    }
    
    /**
     * Add meta boxes for event details
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'ai_calendar_event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_event_details_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );
    }
    
    /**
     * Render event details meta box
     */
    public function render_event_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('ai_calendar_event_save', 'ai_calendar_event_nonce');
        
        // Get existing meta values
        $start_date = get_post_meta($post->ID, '_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_event_end_date', true);
        $start_time = get_post_meta($post->ID, '_event_start_time', true);
        $end_time = get_post_meta($post->ID, '_event_end_time', true);
        $is_full_day = get_post_meta($post->ID, '_event_is_full_day', true) === '1';
        $location = get_post_meta($post->ID, '_event_location', true);
        $url = get_post_meta($post->ID, '_event_url', true);
        $color = get_post_meta($post->ID, '_event_color', true) ?: '#3788d8';
        
        // Set default dates if new event
        if (empty($start_date)) {
            $start_date = date('Y-m-d');
        }
        
        if (empty($end_date)) {
            $end_date = $start_date;
        }
        
        // Set default times if new event
        if (empty($start_time) && !$is_full_day) {
            $start_time = '09:00';
        }
        
        if (empty($end_time) && !$is_full_day) {
            $end_time = '17:00';
        }
        
        // Output meta box HTML
        ?>
        <div class="ai-calendar-meta-box">
            <div class="form-row">
                <label>
                    <input type="checkbox" name="_event_is_full_day" <?php checked($is_full_day); ?>>
                    <?php _e('Full Day Event', 'ai-calendar'); ?>
                </label>
            </div>
            
            <div class="form-row">
                <label for="event_start_date"><?php _e('Start Date', 'ai-calendar'); ?></label>
                <input type="date" id="event_start_date" name="_event_start_date" value="<?php echo esc_attr($start_date); ?>" required>
            </div>
            
            <div class="form-row time-field" <?php echo $is_full_day ? 'style="display:none;"' : ''; ?>>
                <label for="event_start_time"><?php _e('Start Time', 'ai-calendar'); ?></label>
                <input type="time" id="event_start_time" name="_event_start_time" value="<?php echo esc_attr($start_time); ?>">
            </div>
            
            <div class="form-row">
                <label for="event_end_date"><?php _e('End Date', 'ai-calendar'); ?></label>
                <input type="date" id="event_end_date" name="_event_end_date" value="<?php echo esc_attr($end_date); ?>" required>
            </div>
            
            <div class="form-row time-field" <?php echo $is_full_day ? 'style="display:none;"' : ''; ?>>
                <label for="event_end_time"><?php _e('End Time', 'ai-calendar'); ?></label>
                <input type="time" id="event_end_time" name="_event_end_time" value="<?php echo esc_attr($end_time); ?>">
            </div>
            
            <div class="form-row">
                <label for="event_location"><?php _e('Location', 'ai-calendar'); ?></label>
                <input type="text" id="event_location" name="_event_location" value="<?php echo esc_attr($location); ?>">
            </div>
            
            <div class="form-row">
                <label for="event_url"><?php _e('Event URL', 'ai-calendar'); ?></label>
                <input type="url" id="event_url" name="_event_url" value="<?php echo esc_url($url); ?>">
            </div>
            
            <div class="form-row">
                <label for="event_color"><?php _e('Event Color', 'ai-calendar'); ?></label>
                <input type="text" id="event_color" name="_event_color" value="<?php echo esc_attr($color); ?>" class="color-picker">
            </div>
        </div>
        <?php
    }
} 