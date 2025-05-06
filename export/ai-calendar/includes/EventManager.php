<?php
namespace AiCalendar;

class EventManager {
    public function __construct() {
        add_action('init', [$this, 'register_event_post_type']);
        add_action('init', [$this, 'register_event_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_event_meta_boxes']);
        add_action('save_post_ai_calendar_event', [$this, 'save_event_meta'], 10, 3);
        add_action('wp_ajax_create_event', [$this, 'ajax_create_event']);
        add_action('wp_ajax_update_event', [$this, 'ajax_update_event']);
        add_action('init', [$this, 'handle_ical_export']);
        
        // Added: Hooks for frontend assets and content
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('the_content', [$this, 'append_event_actions']);
    }

    public function register_event_post_type() {
        $labels = [
            'name'               => __('Events', 'ai-calendar'),
            'singular_name'      => __('Event', 'ai-calendar'),
            'menu_name'          => __('Events', 'ai-calendar'),
            'add_new'            => __('Add New Event', 'ai-calendar'),
            'add_new_item'       => __('Add New Event', 'ai-calendar'),
            'edit_item'          => __('Edit Event', 'ai-calendar'),
            'new_item'           => __('New Event', 'ai-calendar'),
            'view_item'          => __('View Event', 'ai-calendar'),
            'search_items'       => __('Search Events', 'ai-calendar'),
            'not_found'          => __('No events found', 'ai-calendar'),
            'not_found_in_trash' => __('No events found in Trash', 'ai-calendar'),
            'featured_image'     => __('Event Image', 'ai-calendar'),
            'set_featured_image' => __('Set event image', 'ai-calendar'),
            'remove_featured_image' => __('Remove event image', 'ai-calendar'),
            'use_featured_image' => __('Use as event image', 'ai-calendar'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'events'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'],
            'show_in_rest'       => true,
            'rest_base'          => 'events',
            'map_meta_cap'       => true,
        ];

        register_post_type('ai_calendar_event', $args);

        // Register meta fields
        $meta_fields = [
            '_event_start' => [
                'type' => 'string',
                'description' => 'Event start date and time',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_end' => [
                'type' => 'string',
                'description' => 'Event end date and time',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_location' => [
                'type' => 'string',
                'description' => 'Event location',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_color' => [
                'type' => 'string',
                'description' => 'Event color',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_recurring' => [
                'type' => 'boolean',
                'description' => 'Whether the event is recurring',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_recurrence_type' => [
                'type' => 'string',
                'description' => 'Recurrence type (daily, weekly, monthly, yearly)',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_recurrence_interval' => [
                'type' => 'integer',
                'description' => 'Interval between recurring events',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ],
            '_event_recurrence_end_date' => [
                'type' => 'string',
                'description' => 'End date for recurring events',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => '__return_true'
            ]
        ];

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta('ai_calendar_event', $meta_key, $args);
        }
    }

    public function register_event_taxonomies() {
        // Event Categories
        $category_labels = [
            'name'              => __('Event Categories', 'ai-calendar'),
            'singular_name'     => __('Event Category', 'ai-calendar'),
            'search_items'      => __('Search Event Categories', 'ai-calendar'),
            'all_items'         => __('All Event Categories', 'ai-calendar'),
            'edit_item'         => __('Edit Event Category', 'ai-calendar'),
            'update_item'       => __('Update Event Category', 'ai-calendar'),
            'add_new_item'      => __('Add New Event Category', 'ai-calendar'),
            'new_item_name'     => __('New Event Category Name', 'ai-calendar'),
            'menu_name'         => __('Event Categories', 'ai-calendar'),
        ];

        register_taxonomy('event_category', 'ai_calendar_event', [
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'event-category'],
            'show_in_rest'      => true,
        ]);

        // Event Tags
        $tag_labels = [
            'name'              => __('Event Tags', 'ai-calendar'),
            'singular_name'     => __('Event Tag', 'ai-calendar'),
            'search_items'      => __('Search Event Tags', 'ai-calendar'),
            'all_items'         => __('All Event Tags', 'ai-calendar'),
            'edit_item'         => __('Edit Event Tag', 'ai-calendar'),
            'update_item'       => __('Update Event Tag', 'ai-calendar'),
            'add_new_item'      => __('Add New Event Tag', 'ai-calendar'),
            'new_item_name'     => __('New Event Tag Name', 'ai-calendar'),
            'menu_name'         => __('Event Tags', 'ai-calendar'),
        ];

        register_taxonomy('event_tag', 'ai_calendar_event', [
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'event-tag'],
            'show_in_rest'      => true,
        ]);
    }

    public function add_event_meta_boxes() {
        add_meta_box(
            'ai_calendar_event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_event_details_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );

        add_meta_box(
            'event_recurrence',
            __('Event Recurrence', 'ai-calendar'),
            [$this, 'render_recurrence_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );
    }

    public function render_event_details_meta_box($post) {
        wp_nonce_field('ai_calendar_event_details_nonce', 'ai_calendar_event_details_nonce');

        $start_date = get_post_meta($post->ID, '_event_start', true);
        $end_date = get_post_meta($post->ID, '_event_end', true);
        $location = get_post_meta($post->ID, '_event_location', true);
        $color = get_post_meta($post->ID, '_event_color', true) ?: '#3788d8';

        include AI_CALENDAR_PATH . 'templates/admin/event-details-meta-box.php';
    }

    public function render_recurrence_meta_box($post) {
        wp_nonce_field('event_recurrence_meta_box', 'event_recurrence_meta_box_nonce');

        $recurring = get_post_meta($post->ID, '_event_recurring', true);
        $recurrence_type = get_post_meta($post->ID, '_recurrence_type', true);
        $recurrence_interval = get_post_meta($post->ID, '_recurrence_interval', true) ?: 1;
        $recurrence_weekly_days = get_post_meta($post->ID, '_recurrence_weekly_days', true) ?: [];
        $recurrence_monthly_type = get_post_meta($post->ID, '_recurrence_monthly_type', true) ?: 'day_of_month';
        $recurrence_end_type = get_post_meta($post->ID, '_recurrence_end_type', true) ?: 'never';
        $recurrence_count = get_post_meta($post->ID, '_recurrence_count', true) ?: 10;
        $recurrence_end_date = get_post_meta($post->ID, '_recurrence_end_date', true);

        include plugin_dir_path(dirname(__FILE__)) . 'templates/admin/event-recurrence-meta-box.php';
    }

    public function save_event_meta($post_id, $post, $update) {
        if (!isset($_POST['ai_calendar_event_nonce']) || 
            !wp_verify_nonce($_POST['ai_calendar_event_nonce'], 'ai_calendar_event_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'ai_calendar_event') {
            return;
        }

        // Save basic event details
        $meta_fields = [
            '_event_start_date',
            '_event_end_date',
            '_event_location',
            '_event_organizer'
        ];

        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save recurrence settings
        $recurrence_fields = [
            'event_recurring' => 'intval',
            'recurrence_type' => 'sanitize_text_field',
            'recurrence_interval' => 'intval',
            'recurrence_end_type' => 'sanitize_text_field',
            'recurrence_count' => 'intval',
            'recurrence_end_date' => 'sanitize_text_field'
        ];

        foreach ($recurrence_fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, $sanitize_callback($_POST[$field]));
            }
        }

        // Save weekly recurrence days
        if (isset($_POST['recurrence_weekly_days']) && is_array($_POST['recurrence_weekly_days'])) {
            $weekly_days = array_map('sanitize_text_field', $_POST['recurrence_weekly_days']);
            update_post_meta($post_id, '_recurrence_weekly_days', $weekly_days);
        }

        // Save monthly recurrence type
        if (isset($_POST['recurrence_monthly_type'])) {
            update_post_meta($post_id, '_recurrence_monthly_type', sanitize_text_field($_POST['recurrence_monthly_type']));
        }

        // Generate recurring events if enabled
        if (isset($_POST['event_recurring']) && $_POST['event_recurring'] == '1') {
            $this->generate_recurring_events($post_id);
        }
    }

    public function ajax_create_event() {
        check_ajax_referer('ai_calendar_event_nonce', 'nonce');

        // Validate and sanitize input
        $title = sanitize_text_field($_POST['title']);
        $start_date = sanitize_text_field($_POST['start']);
        $end_date = sanitize_text_field($_POST['end']);
        $description = wp_kses_post($_POST['description']);

        // Create event post
        $event_id = wp_insert_post([
            'post_title'    => $title,
            'post_content'  => $description,
            'post_type'     => 'ai_calendar_event',
            'post_status'   => 'publish'
        ]);

        if ($event_id) {
            // Save event metadata
            update_post_meta($event_id, '_event_start', $start_date);
            update_post_meta($event_id, '_event_end', $end_date);

            // Handle featured image
            if (isset($_FILES['featured_image'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('featured_image', $event_id);
                if ($attachment_id) {
                    set_post_thumbnail($event_id, $attachment_id);
                }
            }

            wp_send_json_success([
                'message' => __('Event created successfully', 'ai-calendar'),
                'event_id' => $event_id
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to create event', 'ai-calendar')
            ]);
        }

        wp_die();
    }

    public function ajax_update_event() {
        check_ajax_referer('ai_calendar_event_nonce', 'nonce');

        $event_id = intval($_POST['event_id']);

        // Validate and sanitize input
        $title = sanitize_text_field($_POST['title']);
        $start_date = sanitize_text_field($_POST['start']);
        $end_date = sanitize_text_field($_POST['end']);
        $description = wp_kses_post($_POST['description']);

        // Update event post
        $event_update = wp_update_post([
            'ID'            => $event_id,
            'post_title'    => $title,
            'post_content'  => $description
        ]);

        if ($event_update) {
            // Update event metadata
            update_post_meta($event_id, '_event_start', $start_date);
            update_post_meta($event_id, '_event_end', $end_date);

            // Handle featured image
            if (isset($_FILES['featured_image'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('featured_image', $event_id);
                if ($attachment_id) {
                    set_post_thumbnail($event_id, $attachment_id);
                }
            }

            wp_send_json_success([
                'message' => __('Event updated successfully', 'ai-calendar'),
                'event_id' => $event_id
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to update event', 'ai-calendar')
            ]);
        }

        wp_die();
    }

    /* Fixed: Frontend asset loading */
    public function enqueue_frontend_assets() {
        if (is_singular('ai_calendar_event')) {
            // Enqueue styles
            wp_enqueue_style(
                'ai-calendar-event-single',
                plugins_url('assets/css/event-single.css', dirname(__FILE__)),
                [],
                filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/event-single.css')
            );
            
            // Enqueue scripts
            wp_enqueue_script(
                'ai-calendar-event-single',
                plugins_url('assets/js/event-single.js', dirname(__FILE__)),
                ['jquery'],
                filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/event-single.js'),
                true
            );

            // Localize script
            wp_localize_script('ai-calendar-event-single', 'aiCalendarEvent', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_calendar_event_nonce')
            ]);
        }
    }

    /* Added: Content filter to append event actions */
    public function append_event_actions($content) {
        if (!is_singular('ai_calendar_event') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        ob_start();
        require plugin_dir_path(dirname(__FILE__)) . 'templates/parts/event-actions.php';
        $actions = ob_get_clean();

        return $content . $actions;
    }

    /* Fixed: iCal Export Handler */
    public function handle_ical_export() {
        if (isset($_GET['action']) && $_GET['action'] === 'ai_calendar_ical_export' && isset($_GET['event_id'])) {
            $event_id = intval($_GET['event_id']);
            $event = get_post($event_id);

            if (!$event || $event->post_type !== 'ai_calendar_event') {
                wp_die(__('Event not found', 'ai-calendar'));
            }

            // Get event meta with enhanced details
            $start_date = get_post_meta($event_id, '_event_start_date', true);
            $start_time = get_post_meta($event_id, '_event_start_time', true);
            $end_date = get_post_meta($event_id, '_event_end_date', true) ?: $start_date;
            $end_time = get_post_meta($event_id, '_event_end_time', true) ?: $start_time;
            
            // Combine date and time
            $start_datetime = $start_date . ($start_time ? ' ' . $start_time : ' 00:00:00');
            $end_datetime = $end_date . ($end_time ? ' ' . $end_time : ' 23:59:59');

            // Get other event details
            $location = get_post_meta($event_id, '_event_location', true);
            $organizer = get_post_meta($event_id, '_event_organizer', true);
            $organizer_email = get_post_meta($event_id, '_event_organizer_email', true);
            $description = wp_strip_all_tags($event->post_content);
            $url = get_permalink($event_id);

            // Format dates for iCal with timezone
            $timezone = wp_timezone();
            $start = new DateTime($start_datetime, $timezone);
            $end = new DateTime($end_datetime, $timezone);
            $now = new DateTime('now', $timezone);

            // Generate unique identifier
            $uid = $event_id . '-' . $now->format('Ymd-His') . '@' . $_SERVER['HTTP_HOST'];

            // Build enhanced iCal content
            $ical = "BEGIN:VCALENDAR\r\n";
            $ical .= "VERSION:2.0\r\n";
            $ical .= "PRODID:-//AI Calendar//Event Calendar//EN\r\n";
            $ical .= "CALSCALE:GREGORIAN\r\n";
            $ical .= "METHOD:PUBLISH\r\n";
            $ical .= "X-WR-CALNAME:AI Calendar Events\r\n";
            $ical .= "X-WR-TIMEZONE:" . wp_timezone_string() . "\r\n";
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . $uid . "\r\n";
            $ical .= "DTSTAMP:" . $now->format('Ymd\THis\Z') . "\r\n";
            $ical .= "DTSTART;TZID=" . wp_timezone_string() . ":" . $start->format('Ymd\THis') . "\r\n";
            $ical .= "DTEND;TZID=" . wp_timezone_string() . ":" . $end->format('Ymd\THis') . "\r\n";
            $ical .= "SUMMARY:" . $this->escape_ical_text($event->post_title) . "\r\n";
            if ($description) {
                $ical .= "DESCRIPTION:" . $this->escape_ical_text($description) . "\r\n";
            }
            if ($location) {
                $ical .= "LOCATION:" . $this->escape_ical_text($location) . "\r\n";
            }
            if ($organizer && $organizer_email) {
                $ical .= "ORGANIZER;CN=" . $this->escape_ical_text($organizer) . ":mailto:" . $organizer_email . "\r\n";
            }
            $ical .= "URL:" . $url . "\r\n";
            $ical .= "STATUS:CONFIRMED\r\n";
            $ical .= "SEQUENCE:0\r\n";
            $ical .= "END:VEVENT\r\n";
            $ical .= "END:VCALENDAR\r\n";

            // Send headers with enhanced caching control
            nocache_headers();
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="event-' . $event_id . '.ics"');
            echo $ical;
            exit;
        }
    }

    /* Added: Helper function for iCal text escaping */
    private function escape_ical_text($text) {
        $text = str_replace("\\", "\\\\", $text);
        $text = str_replace(",", "\,", $text);
        $text = str_replace(";", "\;", $text);
        $text = str_replace("\n", "\\n", $text);
        return $text;
    }

    /**
     * Generate recurring events based on recurrence settings
     */
    private function generate_recurring_events($post_id) {
        $start_date = get_post_meta($post_id, '_event_start_date', true);
        $end_date = get_post_meta($post_id, '_event_end_date', true);
        $recurrence_type = get_post_meta($post_id, '_recurrence_type', true);
        $recurrence_interval = get_post_meta($post_id, '_recurrence_interval', true) ?: 1;
        $recurrence_end_type = get_post_meta($post_id, '_recurrence_end_type', true);
        $recurrence_count = get_post_meta($post_id, '_recurrence_count', true);
        $recurrence_end_date = get_post_meta($post_id, '_recurrence_end_date', true);

        if (!$start_date || !$end_date || !$recurrence_type) {
            return;
        }

        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $duration = $start->diff($end);

        $occurrences = [];
        $count = 0;
        $current_date = clone $start;

        while (true) {
            // Check end conditions
            if ($recurrence_end_type === 'after' && $count >= $recurrence_count) {
                break;
            }
            if ($recurrence_end_type === 'on_date' && $current_date > new DateTime($recurrence_end_date)) {
                break;
            }

            // Add occurrence
            $occurrence_end = clone $current_date;
            $occurrence_end->add($duration);
            $occurrences[] = [
                'start' => clone $current_date,
                'end' => $occurrence_end
            ];

            // Increment date based on recurrence type
            switch ($recurrence_type) {
                case 'daily':
                    $current_date->modify("+{$recurrence_interval} days");
                    break;
                case 'weekly':
                    $current_date->modify("+{$recurrence_interval} weeks");
                    break;
                case 'monthly':
                    $current_date->modify("+{$recurrence_interval} months");
                    break;
                case 'yearly':
                    $current_date->modify("+{$recurrence_interval} years");
                    break;
            }

            $count++;
        }

        // Save occurrences
        update_post_meta($post_id, '_event_occurrences', $occurrences);
    }
} 