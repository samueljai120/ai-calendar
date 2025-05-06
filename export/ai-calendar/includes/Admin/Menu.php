<?php

namespace AiCalendar\Admin;

class Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_menu_pages() {
        // Add main menu
        add_menu_page(
            __('AI Calendar', 'ai-calendar'),
            __('AI Calendar', 'ai-calendar'),
            'manage_options',
            'ai-calendar',
            [$this, 'render_dashboard'],
            'dashicons-calendar',
            30
        );

        // Add submenu pages
        add_submenu_page(
            'ai-calendar',
            __('Dashboard', 'ai-calendar'),
            __('Dashboard', 'ai-calendar'),
            'manage_options',
            'ai-calendar',
            [$this, 'render_dashboard']
        );

        // Calendar Theme menu removed for public version
        
        add_submenu_page(
            'ai-calendar',
            __('Event Page', 'ai-calendar'),
            __('Event Page', 'ai-calendar'),
            'manage_options',
            'ai-calendar-event-settings',
            [$this, 'render_event_settings']
        );

        // Add New Event submenu
        add_submenu_page(
            'ai-calendar',
            __('Add New Event', 'ai-calendar'),
            __('Add New', 'ai-calendar'),
            'manage_options',
            'post-new.php?post_type=ai_calendar_event'
        );

        // Add Instructions page
        add_submenu_page(
            'ai-calendar',
            __('Instructions', 'ai-calendar'),
            __('Instructions', 'ai-calendar'),
            'manage_options',
            'ai-calendar-instructions',
            [$this, 'render_instructions']
        );
    }

    public function enqueue_admin_assets($hook) {
        // Only load on our settings pages
        if (!strpos($hook, 'ai-calendar')) {
            return;
        }

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue our admin styles
        wp_enqueue_style(
            'ai-calendar-admin',
            plugins_url('/assets/css/admin.css', dirname(dirname(__FILE__))),
            [],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0'
        );
        
        // Enqueue our admin scripts
        wp_enqueue_script(
            'ai-calendar-admin',
            plugins_url('/assets/js/admin.js', dirname(dirname(__FILE__))),
            ['jquery', 'wp-color-picker'],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
            true
        );

        // Localize script with necessary data
        wp_localize_script('ai-calendar-admin', 'aiCalendarAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_calendar_admin'),
            'i18n' => [
                'saveSuccess' => __('Settings saved successfully.', 'ai-calendar'),
                'saveError' => __('Error saving settings.', 'ai-calendar')
            ]
        ]);
    }

    public function render_dashboard() {
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/dashboard.php';
    }

    public function render_theme_settings() {
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/theme-settings.php';
    }

    public function render_event_settings() {
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/event-page-settings.php';
    }

    public function render_instructions() {
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/instructions.php';
    }
} 