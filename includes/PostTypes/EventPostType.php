<?php

namespace AiCalendar\PostTypes;

class EventPostType {
    public function __construct() {
        // Register the custom post type immediately
        $this->register_post_type();
        
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        
        // Save post meta
        add_action('save_post_ai_calendar_event', [$this, 'save_post'], 10, 2);
        
        // Handle REST API saving
        add_action('rest_after_insert_ai_calendar_event', [$this, 'save_meta_from_rest'], 10, 2);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'ai_calendar_event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('ai_calendar_event_save', 'ai_calendar_event_nonce');

        // Get current values
        $start_date = get_post_meta($post->ID, '_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_event_end_date', true);
        $start_time = get_post_meta($post->ID, '_event_start_time', true);
        $end_time = get_post_meta($post->ID, '_event_end_time', true);
        $is_full_day = get_post_meta($post->ID, '_event_is_full_day', true) === '1';
        $location = get_post_meta($post->ID, '_event_location', true);
        $url = get_post_meta($post->ID, '_event_url', true);
        $color = get_post_meta($post->ID, '_event_color', true) ?: '#3788d8';
        
        // Debug info
        error_log("Loading event form for ID: $post->ID");
        error_log("Current time values: start_time='$start_time', end_time='$end_time', is_full_day=" . ($is_full_day ? 'true' : 'false'));
        
        // Set default values if empty
        if (empty($start_date)) {
            $start_date = date('Y-m-d');
        }
        
        if (empty($end_date)) {
            $end_date = $start_date;
        }
        
        // Get formatted times for display
        $start_time_display = '';
        $end_time_display = '';
        
        if (!$is_full_day) {
            if (!empty($start_time) && $start_time !== '00:00') {
                $start_time_display = $start_time;
            }
            
            if (!empty($end_time) && $end_time !== '00:00') {
                $end_time_display = $end_time;
            }
        }
        ?>
        
        <div class="ai-calendar-meta-box">
            <style>
                .ai-calendar-meta-box {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                }
                
                .ai-calendar-meta-box fieldset {
                    border: 1px solid #ddd;
                    padding: 15px;
                    margin-bottom: 15px;
                    border-radius: 4px;
                }
                
                .ai-calendar-meta-box legend {
                    font-weight: bold;
                    padding: 0 5px;
                }
                
                .ai-calendar-meta-box label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                }
                
                .ai-calendar-meta-box input[type="text"],
                .ai-calendar-meta-box input[type="url"],
                .ai-calendar-meta-box input[type="date"],
                .ai-calendar-meta-box input[type="time"] {
                    width: 100%;
                    margin-bottom: 15px;
                }
                
                .ai-calendar-meta-box input[type="checkbox"] {
                    width: auto;
                    margin-right: 8px;
                    vertical-align: middle;
                }
                
                .ai-calendar-meta-box .field-group {
                    margin-bottom: 15px;
                }
                
                .ai-calendar-meta-box .checkbox-group {
                    margin: 15px 0;
                    padding: 10px;
                    background-color: #f8f8f8;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                
                .ai-calendar-meta-box .checkbox-group label {
                    display: inline;
                    margin-left: 5px;
                    font-weight: 600;
                    vertical-align: middle;
                }
                
                .ai-calendar-meta-box .checkbox-group .description {
                    margin-top: 5px;
                    color: #666;
                    font-size: 12px;
                }
                
                .ai-calendar-meta-box input[disabled] {
                    background-color: #f5f5f5;
                    cursor: not-allowed;
                }
                
                .ai-calendar-meta-box .time-group {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 15px;
                }
                
                .ai-calendar-meta-box .time-group label {
                    font-weight: normal;
                    flex: 0 0 50px;
                }
                
                .ai-calendar-meta-box .time-group input {
                    flex: 1;
                    margin-bottom: 0;
                }
            </style>
            
            <fieldset>
                <legend><?php _e('Date & Time', 'ai-calendar'); ?></legend>
                
                <div class="field-group">
                    <label for="_event_start_date"><?php _e('Start Date', 'ai-calendar'); ?></label>
                    <input 
                        type="date" 
                        id="_event_start_date" 
                        name="_event_start_date" 
                        value="<?php echo esc_attr($start_date); ?>"
                        required
                    >
            </div>

                <div class="field-group">
                    <label for="_event_end_date"><?php _e('End Date', 'ai-calendar'); ?></label>
                    <input 
                        type="date" 
                        id="_event_end_date" 
                        name="_event_end_date" 
                        value="<?php echo esc_attr($end_date); ?>"
                    >
            </div>

                <div class="checkbox-group" style="margin-bottom: 15px; display: flex; align-items: flex-start; background-color: #f9f9f9; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    <input 
                        type="checkbox" 
                        id="_event_is_full_day" 
                        name="_event_is_full_day" 
                        <?php checked($is_full_day, true); ?>
                        onchange="toggleTimeFields(this.checked)"
                        style="margin-top: 4px; margin-right: 10px;"
                    >
                    <div>
                        <label for="_event_is_full_day" style="font-weight: bold; display: block; margin-bottom: 5px;">
                            <?php _e('Full Day Event', 'ai-calendar'); ?>
                        </label>
                        <p class="description" style="margin-top: 0; font-style: italic; color: #666;">
                            <?php _e('Check this box if the event lasts all day. Time fields will be hidden.', 'ai-calendar'); ?>
                        </p>
                    </div>
                </div>

                <div id="time-fields" class="<?php echo $is_full_day ? 'hidden' : ''; ?>">
                    <div class="time-group">
                        <label for="_event_start_time"><?php _e('Start Time', 'ai-calendar'); ?></label>
                        <input 
                            type="time" 
                            id="_event_start_time" 
                            name="_event_start_time" 
                            value="<?php echo esc_attr($start_time_display); ?>"
                            <?php echo $is_full_day ? 'disabled' : ''; ?>
                        >
                </div>

                    <div class="time-group">
                        <label for="_event_end_time"><?php _e('End Time', 'ai-calendar'); ?></label>
                        <input 
                            type="time" 
                            id="_event_end_time" 
                            name="_event_end_time" 
                            value="<?php echo esc_attr($end_time_display); ?>"
                            <?php echo $is_full_day ? 'disabled' : ''; ?>
                        >
                    </div>
                </div>
            </fieldset>
            
            <fieldset>
                <legend><?php _e('Additional Details', 'ai-calendar'); ?></legend>
                
                <div class="field-group">
                    <label for="_event_location"><?php _e('Location', 'ai-calendar'); ?></label>
                    <input 
                        type="text" 
                        id="_event_location" 
                        name="_event_location" 
                        value="<?php echo esc_attr($location); ?>"
                        placeholder="<?php _e('Event location', 'ai-calendar'); ?>"
                    >
                </div>

                <div class="field-group">
                    <label for="_event_url"><?php _e('URL', 'ai-calendar'); ?></label>
                    <input 
                        type="url" 
                        id="_event_url" 
                        name="_event_url" 
                        value="<?php echo esc_attr($url); ?>"
                        placeholder="https://"
                    >
            </div>

                <div class="field-group">
                    <label for="_event_color"><?php _e('Event Color', 'ai-calendar'); ?></label>
                    <input 
                        type="color" 
                        id="_event_color" 
                        name="_event_color" 
                        value="<?php echo esc_attr($color); ?>"
                    >
                </div>
            </fieldset>

            <script>
                // Make sure the function is defined in the global scope
                window.toggleTimeFields = function(isFullDay) {
                    const timeFields = document.getElementById('time-fields');
                    const startTimeInput = document.getElementById('_event_start_time');
                    const endTimeInput = document.getElementById('_event_end_time');
                    
                    if (!timeFields || !startTimeInput || !endTimeInput) {
                        console.error('Time fields not found');
                        return;
                    }
                    
                    console.log('Toggle time fields:', isFullDay);
                    
                    if (isFullDay) {
                        timeFields.classList.add('hidden');
                        startTimeInput.disabled = true;
                        endTimeInput.disabled = true;
                        startTimeInput.value = '';
                        endTimeInput.value = '';
                    } else {
                        timeFields.classList.remove('hidden');
                        startTimeInput.disabled = false;
                        endTimeInput.disabled = false;
                    }
                };
                
                // Initialize time fields on page load with a delay to ensure DOM is ready
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded');
                    setTimeout(function() {
                        const fullDayCheckbox = document.getElementById('_event_is_full_day');
                        if (fullDayCheckbox) {
                            console.log('Found checkbox, initial state:', fullDayCheckbox.checked);
                            window.toggleTimeFields(fullDayCheckbox.checked);
                            
                            // Add event listener to ensure the checkbox works properly
                            fullDayCheckbox.addEventListener('change', function() {
                                console.log('Checkbox changed:', this.checked);
                                window.toggleTimeFields(this.checked);
                            });
                        } else {
                            console.error('Full day checkbox not found');
                        }
                    }, 300);
            });
            </script>

            <style>
                .hidden {
                    display: none !important;
            }
            </style>
        </div>
        <?php
    }

    public function save_post($post_id) {
        // Skip autosave, ajax and bulk actions
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || 
            (defined('DOING_AJAX') && DOING_AJAX) || 
            isset($_REQUEST['bulk_edit'])) {
            return;
        }

        // Check if it's our post type
        if (get_post_type($post_id) !== 'ai_calendar_event') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['ai_calendar_event_nonce']) || 
            !wp_verify_nonce($_POST['ai_calendar_event_nonce'], 'ai_calendar_event_save')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Debug log
        error_log("SAVING EVENT - Post ID: $post_id");
        error_log("POST Data: " . print_r($_POST, true));

        // Sanitize and save start date
        if (isset($_POST['_event_start_date'])) {
            $start_date = sanitize_text_field($_POST['_event_start_date']);
            update_post_meta($post_id, '_event_start_date', $start_date);
            error_log("Saved start date: $start_date");
        }

        // Sanitize and save end date
        if (isset($_POST['_event_end_date'])) {
            $end_date = sanitize_text_field($_POST['_event_end_date']);
            if (empty($end_date)) {
                $end_date = isset($_POST['_event_start_date']) ? $_POST['_event_start_date'] : '';
            }
            update_post_meta($post_id, '_event_end_date', $end_date);
            error_log("Saved end date: $end_date");
        }

        // Get is_full_day flag - explicitly using isset to check if the checkbox was checked
        $is_full_day = isset($_POST['_event_is_full_day']) ? true : false;
        error_log("Is full day checkbox present in form data: " . ($is_full_day ? 'Yes' : 'No'));
        
        // Make sure we update the meta with a string value ('1' or '0')
        update_post_meta($post_id, '_event_is_full_day', $is_full_day ? '1' : '0');
        error_log("Saved _event_is_full_day as: " . ($is_full_day ? '1' : '0'));

        // Handle time values based on full day status
        if ($is_full_day) {
            // For full day events, clear any existing time values
            delete_post_meta($post_id, '_event_start_time');
            delete_post_meta($post_id, '_event_end_time');
            
            // Set times to null/empty string to indicate full day
            update_post_meta($post_id, '_event_start_time', '');
            update_post_meta($post_id, '_event_end_time', '');
            error_log("Set empty time values for full day event");
        } else {
            // For events with specific times, save the times
            if (isset($_POST['_event_start_time'])) {
                $start_time = sanitize_text_field($_POST['_event_start_time']);
                error_log("Raw start time from form: '$start_time'");
                
                if (!empty($start_time)) {
                    update_post_meta($post_id, '_event_start_time', $start_time);
                    error_log("Saved start time: $start_time");
                } else {
                    delete_post_meta($post_id, '_event_start_time');
                    error_log("No start time provided - removed");
                }
            }
            
            if (isset($_POST['_event_end_time'])) {
                $end_time = sanitize_text_field($_POST['_event_end_time']);
                error_log("Raw end time from form: '$end_time'");
                
                if (!empty($end_time)) {
                    update_post_meta($post_id, '_event_end_time', $end_time);
                    error_log("Saved end time: $end_time");
                } else {
                    delete_post_meta($post_id, '_event_end_time');
                    error_log("No end time provided - removed");
                }
            }
        }

        // Save formatted time values for frontend display
        $this->save_formatted_time_values($post_id);

        // Additional meta fields
        if (isset($_POST['_event_location'])) {
            $location = sanitize_text_field($_POST['_event_location']);
            update_post_meta($post_id, '_event_location', $location);
        }
        
        if (isset($_POST['_event_url'])) {
            $url = esc_url_raw($_POST['_event_url']);
            update_post_meta($post_id, '_event_url', $url);
        }
        
        if (isset($_POST['_event_color'])) {
            $color = sanitize_hex_color($_POST['_event_color']);
            update_post_meta($post_id, '_event_color', $color);
        }
    }

    /**
     * Save pre-formatted time values for frontend display
     */
    private function save_formatted_time_values($post_id) {
        // Get current values
        $is_full_day = get_post_meta($post_id, '_event_is_full_day', true) === '1';
        $start_time = get_post_meta($post_id, '_event_start_time', true);
        $end_time = get_post_meta($post_id, '_event_end_time', true);
        
        error_log("Preparing formatted time values for ID $post_id");
        error_log("Current stored values: is_full_day=$is_full_day, start_time='$start_time', end_time='$end_time'");
        
        // Clear existing formatted values
        delete_post_meta($post_id, '_formatted_start_time');
        delete_post_meta($post_id, '_formatted_end_time');
        delete_post_meta($post_id, '_time_display');
        
        // If it's a full day event, set time display to "Full day"
        if ($is_full_day) {
            update_post_meta($post_id, '_time_display', 'Full day');
            error_log("Full day event - set _time_display to 'Full day'");
            return;
        }

        // Format start time if present
        if (!empty($start_time) && $start_time !== '00:00') {
            $formatted_start = $this->format_time_for_display($start_time);
            update_post_meta($post_id, '_formatted_start_time', $formatted_start);
            error_log("Saved formatted start time: $formatted_start");
        }
        
        // Format end time if present
        if (!empty($end_time) && $end_time !== '00:00') {
            $formatted_end = $this->format_time_for_display($end_time);
            update_post_meta($post_id, '_formatted_end_time', $formatted_end);
            error_log("Saved formatted end time: $formatted_end");
        }
        
        // Create combined time display
        if (!empty($start_time) && !empty($end_time) && 
            $start_time !== '00:00' && $end_time !== '00:00') {
            $formatted_start = $this->format_time_for_display($start_time);
            $formatted_end = $this->format_time_for_display($end_time);
            $time_display = "$formatted_start - $formatted_end";
            update_post_meta($post_id, '_time_display', $time_display);
            error_log("Saved combined time display: $time_display");
        } else if (!empty($start_time) && $start_time !== '00:00') {
            $formatted_start = $this->format_time_for_display($start_time);
            update_post_meta($post_id, '_time_display', $formatted_start);
            error_log("Saved start-only time display: $formatted_start");
        } else if (!empty($end_time) && $end_time !== '00:00') {
            $formatted_end = $this->format_time_for_display($end_time);
            update_post_meta($post_id, '_time_display', $formatted_end);
            error_log("Saved end-only time display: $formatted_end");
        } else {
            // If no valid times are present, set to "Time not specified"
            update_post_meta($post_id, '_time_display', 'Time not specified');
            error_log("No valid times - set _time_display to 'Time not specified'");
        }
    }

    /**
     * Format time for display
     * 
     * @param string $time The time in 24-hour format (HH:MM)
     * @return string The formatted time for display
     */
    private function format_time_for_display($time) {
        if (empty($time)) {
            return '';
        }

        // Parse the time components
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return $time; // Return as-is if not in expected format
        }

        $hour = (int)$parts[0];
        $minute = (int)$parts[1];
        
        // Format based on WordPress time format
        $date = new \DateTime();
        $date->setTime($hour, $minute);
        
        // Use WordPress time format or fall back to default
        $format = get_option('time_format', 'g:i a');
        
        return wp_date($format, $date->getTimestamp());
    }

    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Events', 'Post type general name', 'ai-calendar'),
            'singular_name'         => _x('Event', 'Post type singular name', 'ai-calendar'),
            'menu_name'             => _x('Events', 'Admin Menu text', 'ai-calendar'),
            'name_admin_bar'        => _x('Event', 'Add New on Toolbar', 'ai-calendar'),
            'add_new'               => __('Add New', 'ai-calendar'),
            'add_new_item'          => __('Add New Event', 'ai-calendar'),
            'new_item'              => __('New Event', 'ai-calendar'),
            'edit_item'             => __('Edit Event', 'ai-calendar'),
            'view_item'             => __('View Event', 'ai-calendar'),
            'all_items'             => __('All Events', 'ai-calendar'),
            'search_items'          => __('Search Events', 'ai-calendar'),
            'parent_item_colon'     => __('Parent Events:', 'ai-calendar'),
            'not_found'             => __('No events found.', 'ai-calendar'),
            'not_found_in_trash'    => __('No events found in Trash.', 'ai-calendar'),
            'featured_image'        => _x('Event Cover Image', 'Overrides the "Featured Image" phrase', 'ai-calendar'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'ai-calendar'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'ai-calendar'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'ai-calendar'),
            'archives'              => _x('Event archives', 'The post type archive label', 'ai-calendar'),
            'insert_into_item'      => _x('Insert into event', 'Overrides the "Insert into post" phrase', 'ai-calendar'),
            'uploaded_to_this_item' => _x('Uploaded to this event', 'Overrides the "Uploaded to this post" phrase', 'ai-calendar'),
            'filter_items_list'     => _x('Filter events list', 'Screen reader text for filter links', 'ai-calendar'),
            'items_list_navigation' => _x('Events list navigation', 'Screen reader text for pagination', 'ai-calendar'),
            'items_list'            => _x('Events list', 'Screen reader text for list', 'ai-calendar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'ai-calendar',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'events'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon'          => 'dashicons-calendar-alt',
            'show_in_rest'       => true,
            'register_meta_box_cb' => array($this, 'add_meta_boxes'),
        );

        // Add a debug log to track post type registration
        error_log('Registering AI Calendar Event post type');
        
        register_post_type('ai_calendar_event', $args);
        
        // Register meta fields for REST API
        $this->register_meta_fields();
    }

    /**
     * Register meta fields for REST API
     */
    public function register_meta_fields() {
        register_post_meta('ai_calendar_event', '_event_start_date', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_event_end_date', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_event_start_time', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_event_end_time', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_event_is_full_day', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_event_location', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        // Optional: Register alternative field names used by the block editor
        register_post_meta('ai_calendar_event', 'startTime', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', 'endTime', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', 'all_day', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        // Formatted time values for display
        register_post_meta('ai_calendar_event', '_formatted_start_time', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_formatted_end_time', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
        
        register_post_meta('ai_calendar_event', '_time_display', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_posts'); 
            }
        ]);
    }

    /**
     * Handle meta saving from REST API requests
     *
     * @param \WP_Post $post The post object
     * @param \WP_REST_Request $request The request object
     */
    public function save_meta_from_rest($post, $request) {
        try {
            error_log("EventPostType: Processing REST API save for event #{$post->ID}");
            
            // Get the request data
            $params = $request->get_params();
            error_log("REST request params: " . print_r($params, true));
            
            if (!isset($params['meta'])) {
                error_log("No meta data in REST request");
                return;
            }
            
            $meta = $params['meta'];
            
            // Process full day status
            $is_full_day = false;
            
            if (isset($meta['_event_is_full_day'])) {
                $is_full_day = ($meta['_event_is_full_day'] === '1' || $meta['_event_is_full_day'] === true);
            } else if (isset($meta['all_day'])) {
                $is_full_day = ($meta['all_day'] === '1' || $meta['all_day'] === true);
            }
            
            // Save full day status
            update_post_meta($post->ID, '_event_is_full_day', $is_full_day ? '1' : '0');
            error_log("Saved full day status: " . ($is_full_day ? 'true' : 'false'));
            
            // Process start and end dates
            if (isset($meta['_event_start_date'])) {
                update_post_meta($post->ID, '_event_start_date', sanitize_text_field($meta['_event_start_date']));
            }
            
            if (isset($meta['_event_end_date'])) {
                update_post_meta($post->ID, '_event_end_date', sanitize_text_field($meta['_event_end_date']));
            }
            
            // Process start and end times
            if ($is_full_day) {
                // For full day events, set default times or empty
                update_post_meta($post->ID, '_event_start_time', '00:00');
                update_post_meta($post->ID, '_event_end_time', '23:59');
                error_log("Full day event - setting default times");
            } else {
                // For timed events, process the specified times
                $start_time = '';
                $end_time = '';
                
                if (isset($meta['_event_start_time'])) {
                    $start_time = sanitize_text_field(trim($meta['_event_start_time']));
                } else if (isset($meta['startTime'])) {
                    $start_time = sanitize_text_field(trim($meta['startTime']));
                }
                
                if (isset($meta['_event_end_time'])) {
                    $end_time = sanitize_text_field(trim($meta['_event_end_time']));
                } else if (isset($meta['endTime'])) {
                    $end_time = sanitize_text_field(trim($meta['endTime']));
                }
                
                // If we don't have times, but it's not a full day event, set defaults
                if (empty($start_time)) {
                    $start_time = '09:00';
                }
                
                if (empty($end_time)) {
                    $end_time = '17:00';
                }
                
                // Save the times
                update_post_meta($post->ID, '_event_start_time', $start_time);
                update_post_meta($post->ID, '_event_end_time', $end_time);
                
                error_log("Timed event - saved start time: $start_time, end time: $end_time");
            }
            
            // Process location and other fields - use safer sanitization
            if (isset($meta['_event_location'])) {
                update_post_meta($post->ID, '_event_location', sanitize_text_field($meta['_event_location']));
            }
            
            if (isset($meta['_event_url'])) {
                update_post_meta($post->ID, '_event_url', esc_url_raw($meta['_event_url']));
            }
            
            // Safe color handling - check if the function exists
            if (isset($meta['_event_color'])) {
                if (function_exists('sanitize_hex_color')) {
                    $color = sanitize_hex_color($meta['_event_color']);
                } else {
                    // Simple fallback sanitization for hex colors
                    $color = preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $meta['_event_color']) ? $meta['_event_color'] : '';
                }
                update_post_meta($post->ID, '_event_color', $color);
            }
            
            // Save formatted time values for frontend display
            $this->save_formatted_time_values($post->ID);
            
            error_log("Successfully saved all event meta data from REST API for post #{$post->ID}");
            
        } catch (\Exception $e) {
            // Detailed error logging
            error_log("Error saving event meta from REST API: " . $e->getMessage());
            error_log("Error details: " . $e->getTraceAsString());
            
            // Don't let the error bubble up and cause a 500 response
            // Just log it and continue
        } catch (\Error $e) {
            // PHP 7+ fatal error handling
            error_log("Fatal error saving event meta from REST API: " . $e->getMessage());
            error_log("Error details: " . $e->getTraceAsString());
        }
    }
} 