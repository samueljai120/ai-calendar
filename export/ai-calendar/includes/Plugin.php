<?php
namespace AiCalendar;

use AiCalendar\Admin\Admin;
use AiCalendar\Frontend\Frontend;

class Plugin {
    private $admin;
    private $frontend;
    private $event_manager;

    public function __construct() {
        $this->event_manager = new EventManager();
        
        // Initialize admin
        if (is_admin()) {
            $this->admin = new Admin\Admin();
            new Admin\Help();
        }
        
        // Initialize frontend
        $this->frontend = new Frontend();
        
        // Register AJAX handlers
        add_action('wp_ajax_ai_calendar_get_events', [$this->frontend, 'get_events']);
        add_action('wp_ajax_nopriv_ai_calendar_get_events', [$this->frontend, 'get_events']);
        
        add_action('wp_ajax_ai_calendar_get_preview', [$this->frontend, 'get_preview']);
        add_action('wp_ajax_ai_calendar_save_settings', [$this->admin, 'save_settings']);
        add_action('wp_ajax_ai_calendar_reset_settings', [$this->admin, 'reset_settings']);
        add_action('wp_ajax_ai_calendar_ical_export', [$this, 'handle_ical_export']);
        add_action('wp_ajax_nopriv_ai_calendar_ical_export', [$this, 'handle_ical_export']);

        // Register blocks
        if (function_exists('register_block_type')) {
            add_action('init', [$this, 'register_blocks']);
        }

        // Register event taxonomies
        add_action('init', [$this, 'register_taxonomies']);
    }

    public function init() {
        // Check if rewrite rules need to be flushed
        if (get_option('ai_calendar_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('ai_calendar_flush_rewrite_rules');
        }

        // Register shortcode
        add_shortcode('ai_calendar', [$this->frontend, 'render_calendar']);
    }

    public function register_blocks() {
        register_block_type('ai-calendar/calendar', [
            'editor_script' => 'ai-calendar-block',
            'editor_style' => 'ai-calendar-block-editor',
            'render_callback' => [$this->frontend, 'render_calendar']
        ]);
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register 'event' post type
        register_post_type('ai_calendar_event', [
            'labels' => [
                'name' => __('Events', 'ai-calendar'),
                'singular_name' => __('Event', 'ai-calendar'),
                // ... existing labels ...
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-calendar',
            'rewrite' => ['slug' => 'events'],
            'show_in_rest' => true,
            'show_in_menu' => false
        ]);

        // Register 'instance' post type
        register_post_type('ai_calendar_instance', [
            'labels' => [
                'name' => __('Instances', 'ai-calendar'),
                'singular_name' => __('Instance', 'ai-calendar'),
                'add_new' => __('Add New Instance', 'ai-calendar'),
                'add_new_item' => __('Add New Event Instance', 'ai-calendar'),
                'edit_item' => __('Edit Event Instance', 'ai-calendar'),
                'new_item' => __('New Event Instance', 'ai-calendar'),
                'view_item' => __('View Event Instance', 'ai-calendar'),
                'search_items' => __('Search Event Instances', 'ai-calendar'),
                'not_found' => __('No event instances found', 'ai-calendar'),
                'not_found_in_trash' => __('No event instances found in trash', 'ai-calendar'),
                'parent_item_colon' => __('Parent Event:', 'ai-calendar'),
                'menu_name' => __('Event Instances', 'ai-calendar'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'hierarchical' => false,
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'event-instance'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow', // Prevent manual creation
            ],
            'map_meta_cap' => true,
        ]);
    }

    /**
     * Register event taxonomies
     */
    public function register_taxonomies() {
        // Register Event Categories
        register_taxonomy('event_category', 'ai_calendar_event', [
            'labels' => [
                'name' => __('Event Categories', 'ai-calendar'),
                'singular_name' => __('Event Category', 'ai-calendar'),
                'search_items' => __('Search Categories', 'ai-calendar'),
                'all_items' => __('All Categories', 'ai-calendar'),
                'parent_item' => __('Parent Category', 'ai-calendar'),
                'parent_item_colon' => __('Parent Category:', 'ai-calendar'),
                'edit_item' => __('Edit Category', 'ai-calendar'),
                'update_item' => __('Update Category', 'ai-calendar'),
                'add_new_item' => __('Add New Category', 'ai-calendar'),
                'new_item_name' => __('New Category Name', 'ai-calendar'),
                'menu_name' => __('Categories', 'ai-calendar'),
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'event-category'],
            'show_in_rest' => true,
        ]);

        // Register Event Tags
        register_taxonomy('event_tag', 'ai_calendar_event', [
            'labels' => [
                'name' => __('Event Tags', 'ai-calendar'),
                'singular_name' => __('Event Tag', 'ai-calendar'),
                'search_items' => __('Search Tags', 'ai-calendar'),
                'all_items' => __('All Tags', 'ai-calendar'),
                'edit_item' => __('Edit Tag', 'ai-calendar'),
                'update_item' => __('Update Tag', 'ai-calendar'),
                'add_new_item' => __('Add New Tag', 'ai-calendar'),
                'new_item_name' => __('New Tag Name', 'ai-calendar'),
                'menu_name' => __('Tags', 'ai-calendar'),
            ],
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'event-tag'],
            'show_in_rest' => true,
        ]);
    }

    /**
     * Handle iCal export
     */
    public function handle_ical_export() {
        if (!isset($_GET['event_id'])) {
            wp_die('Event ID is required.');
        }

        $event_id = intval($_GET['event_id']);
        $event = get_post($event_id);

        if (!$event || $event->post_type !== 'ai_calendar_event') {
            wp_die('Invalid event.');
        }

        // Get event meta
        $start_date = get_post_meta($event_id, '_event_start_date', true);
        $end_date = get_post_meta($event_id, '_event_end_date', true);
        $location = get_post_meta($event_id, '_event_location', true);

        // Create iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//AI Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $event_id . "@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . date('Ymd\THis\Z', strtotime($start_date)) . "\r\n";
        $ical .= "DTEND:" . date('Ymd\THis\Z', strtotime($end_date)) . "\r\n";
        $ical .= "SUMMARY:" . $event->post_title . "\r\n";
        if ($location) {
            $ical .= "LOCATION:" . $location . "\r\n";
        }
        $ical .= "DESCRIPTION:" . wp_strip_all_tags($event->post_content) . "\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR";

        // Set headers for file download
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($event->post_title) . '.ics"');
        echo $ical;
        exit;
    }
} 