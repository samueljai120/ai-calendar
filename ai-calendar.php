<?php
/**
 * Plugin Name: AI Calendar
 * Plugin URI: https://wordpress.org/plugins/ai-calendar/
 * Description: A beautiful and responsive calendar plugin for WordPress that automatically displays events with adjustable styling.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Samuel So
 * Author URI: mailto:samuelso0105@gmail.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-calendar
 * Domain Path: /languages
 * Copyright: © 2023 Samuel So - samuelso0105@gmail.com
 */

use AiCalendar\Admin\TemplatePreviewGenerator;

// Define plugin version
define('AI_CALENDAR_VERSION', '1.0.0');

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version and paths
define('AI_CALENDAR_FILE', __FILE__);
define('AI_CALENDAR_PATH', plugin_dir_path(__FILE__));
define('AI_CALENDAR_URL', plugin_dir_url(__FILE__));
define('AI_CALENDAR_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'AiCalendar\\';
    $base_dir = AI_CALENDAR_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
function ai_calendar_init() {
    // Initialize Frontend
    new AiCalendar\Frontend();
    
    // Initialize Admin
    new AiCalendar\Admin\Menu();
    
    // Initialize Settings
    new AiCalendar\Settings\ThemeSettings();
    new AiCalendar\Settings\EventPageSettings();
    
    // Initialize AJAX handlers
    new AiCalendar\Ajax\EventHandler();
    
    // Initialize post types
    new AiCalendar\PostTypes\EventPostType();
}
add_action('init', 'ai_calendar_init');

// Activation hook
function ai_calendar_activate() {
    // Register the post type first
    ai_calendar_register_post_type();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set up option to indicate that rewrite rules should be flushed
    update_option('ai_calendar_flush_rewrite', true);
    
    // Initialize default theme settings if they don't exist
    if (!get_option('ai_calendar_theme_settings')) {
        update_option('ai_calendar_theme_settings', [
            'enable_theme' => true,
            'theme' => 'modern',
            'colors' => [
                'primary' => '#3182ce',
                'secondary' => '#4a5568',
                'background' => '#ffffff',
                'text' => '#2d3748'
            ]
        ]);
    }
}
register_activation_hook(__FILE__, 'ai_calendar_activate');

// Deactivation hook
function ai_calendar_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ai_calendar_deactivate');

// Add settings link on plugin page
function ai_calendar_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=ai-calendar-settings">' . __('Settings', 'ai-calendar') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ai_calendar_add_settings_link');

// Register post type helper
function ai_calendar_register_post_type() {
    try {
        $labels = array(
            'name'                  => _x('Events', 'Post type general name', 'ai-calendar'),
            'singular_name'         => _x('Event', 'Post type singular name', 'ai-calendar'),
            'menu_name'            => _x('Events', 'Admin Menu text', 'ai-calendar'),
            'name_admin_bar'       => _x('Event', 'Add New on Toolbar', 'ai-calendar'),
            'add_new'              => __('Add New', 'ai-calendar'),
            'add_new_item'         => __('Add New Event', 'ai-calendar'),
            'new_item'             => __('New Event', 'ai-calendar'),
            'edit_item'            => __('Edit Event', 'ai-calendar'),
            'view_item'            => __('View Event', 'ai-calendar'),
            'all_items'            => __('All Events', 'ai-calendar'),
            'search_items'         => __('Search Events', 'ai-calendar'),
            'not_found'            => __('No events found.', 'ai-calendar'),
            'not_found_in_trash'   => __('No events found in Trash.', 'ai-calendar'),
            'featured_image'       => __('Event Image', 'ai-calendar'),
            'set_featured_image'   => __('Set event image', 'ai-calendar'),
            'remove_featured_image' => __('Remove event image', 'ai-calendar'),
            'use_featured_image'   => __('Use as event image', 'ai-calendar'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'event'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'        => true,
        );

        register_post_type('ai_calendar_event', $args);

        // Register custom meta fields
        $meta_fields = array(
            '_event_start_date' => array(
                'type' => 'string',
                'description' => 'Event start date and time',
                'single' => true,
                'show_in_rest' => true
            ),
            '_event_end_date' => array(
                'type' => 'string',
                'description' => 'Event end date and time',
                'single' => true,
                'show_in_rest' => true
            ),
            '_event_location' => array(
                'type' => 'string',
                'description' => 'Event location',
                'single' => true,
                'show_in_rest' => true
            ),
            '_event_organizer' => array(
                'type' => 'string',
                'description' => 'Event organizer',
                'single' => true,
                'show_in_rest' => true
            )
        );

        foreach ($meta_fields as $meta_key => $args) {
            register_post_meta('ai_calendar_event', $meta_key, $args);
        }

        // Flush rewrite rules only on plugin activation
        if (get_option('ai_calendar_flush_rewrite')) {
            flush_rewrite_rules();
            delete_option('ai_calendar_flush_rewrite');
        }
    } catch (Exception $e) {
        // Handle exception silently in production
    }
}

// Register the shortcode
function ai_calendar_shortcode($atts) {
    // Extract attributes
    $atts = shortcode_atts(array(
        'view' => 'month',
        'category' => '',
        'limit' => 10
    ), $atts, 'ai_calendar');
    
    // Initialize Frontend
    $frontend = new AiCalendar\Frontend();
    
    // Return the calendar HTML
    return $frontend->render_calendar($atts);
}
add_shortcode('ai_calendar', 'ai_calendar_shortcode');

// Register assets
function enqueue_frontend_assets() {
    // Use hardcoded default theme settings instead of getting from options
    $theme_settings = [
        'enable_theme' => true,
        'theme' => 'modern',
        'colors' => [
            'primary' => '#3182ce',
            'secondary' => '#4a5568',
            'background' => '#ffffff',
            'text' => '#2d3748'
        ]
    ];
    
    // Enqueue styles
    wp_enqueue_style(
        'ai-calendar-frontend',
        AI_CALENDAR_URL . 'assets/css/frontend.css',
        array(),
        AI_CALENDAR_VERSION
    );
    
    // Enqueue script
    wp_enqueue_script(
        'ai-calendar-frontend',
        AI_CALENDAR_URL . 'assets/js/frontend.js',
        array('jquery'),
        AI_CALENDAR_VERSION,
        true
    );
    
    // Pass data to script
    wp_localize_script('ai-calendar-frontend', 'aiCalendar', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_calendar_nonce'),
        'pluginUrl' => AI_CALENDAR_URL,
        'themeSettings' => $theme_settings
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_frontend_assets');

// Helper function to preserve time values
function ai_calendar_preserve_time_values($event) {
        global $wpdb;
    
    // Get the correct start and end time values
    $start_time = get_post_meta($event->id, '_event_start_time', true);
    $end_time = get_post_meta($event->id, '_event_end_time', true);
    
    // Try alternate fields if the primary ones are empty
    if (empty($start_time)) {
        $alt_start_time = get_post_meta($event->id, '_event_time_start', true);
        if (!empty($alt_start_time)) {
            $start_time = $alt_start_time;
        }
    }
    
    if (empty($end_time)) {
        $alt_end_time = get_post_meta($event->id, '_event_time_end', true);
        if (!empty($alt_end_time)) {
            $end_time = $alt_end_time;
        }
    }
    
    // If we still don't have times, try to extract them from date fields
    if (empty($start_time) || empty($end_time)) {
        // Get raw date values that might include time
        $raw_start_date = get_post_meta($event->id, '_event_start_date', true);
        $raw_end_date = get_post_meta($event->id, '_event_end_date', true);
        
        // Extract time from date if available
        if (!empty($raw_start_date) && strpos($raw_start_date, ' ') !== false) {
            $date_parts = explode(' ', $raw_start_date);
            if (isset($date_parts[1])) {
                $raw_start_time = $date_parts[1];
                if (empty($start_time)) {
                    $start_time = $raw_start_time;
                }
            }
        }
        
        if (!empty($raw_end_date) && strpos($raw_end_date, ' ') !== false) {
            $date_parts = explode(' ', $raw_end_date);
            if (isset($date_parts[1])) {
                $raw_end_time = $date_parts[1];
                if (empty($end_time)) {
                    $end_time = $raw_end_time;
                }
            }
        }
    }
    
    // Set the times on the event object if we found them
    if (!empty($start_time)) {
        $event->start_time = $start_time;
    }
    
    if (!empty($end_time)) {
        $event->end_time = $end_time;
    }
    
    return $event;
} 