<?php
namespace AiCalendar\Admin;

class Help {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_help_pages']);
    }

    public function add_help_pages() {
        add_submenu_page(
            'edit.php?post_type=ai_calendar_event',
            'Instructions',
            'Instructions',
            'manage_options',
            'ai-calendar-instructions',
            [$this, 'render_instructions_page']
        );

        add_submenu_page(
            'edit.php?post_type=ai_calendar_event',
            'Debug Guide',
            'Debug Guide',
            'manage_options',
            'ai-calendar-debug',
            [$this, 'render_debug_page']
        );
    }

    public function render_instructions_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Calendar Instructions', 'ai-calendar'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Quick Start Guide', 'ai-calendar'); ?></h2>
                <ol>
                    <li><?php _e('Add the calendar to any page using the shortcode: [ai_calendar]', 'ai-calendar'); ?></li>
                    <li><?php _e('Create events from the Events menu', 'ai-calendar'); ?></li>
                    <li><?php _e('Customize the calendar appearance from Theme Settings', 'ai-calendar'); ?></li>
                </ol>
            </div>

            <div class="card">
                <h2><?php _e('Theme Customization', 'ai-calendar'); ?></h2>
                <ul>
                    <li><?php _e('Enable/disable custom theme', 'ai-calendar'); ?></li>
                    <li><?php _e('Choose from pre-built themes: Modern, Minimal, Dark', 'ai-calendar'); ?></li>
                    <li><?php _e('Customize colors for various calendar elements', 'ai-calendar'); ?></li>
                    <li><?php _e('Preview changes in real-time', 'ai-calendar'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php _e('Managing Events', 'ai-calendar'); ?></h2>
                <ul>
                    <li><?php _e('Create single or multi-day events', 'ai-calendar'); ?></li>
                    <li><?php _e('Add event details: title, description, location', 'ai-calendar'); ?></li>
                    <li><?php _e('Set event times and dates', 'ai-calendar'); ?></li>
                    <li><?php _e('Add featured images to events', 'ai-calendar'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php _e('Advanced Features', 'ai-calendar'); ?></h2>
                <ul>
                    <li><?php _e('Filter events by category', 'ai-calendar'); ?></li>
                    <li><?php _e('Export events to iCal', 'ai-calendar'); ?></li>
                    <li><?php _e('Responsive design for all devices', 'ai-calendar'); ?></li>
                    <li><?php _e('Shortcode parameters for customization', 'ai-calendar'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function render_debug_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Debug Guide', 'ai-calendar'); ?></h1>

            <div class="card">
                <h2><?php _e('Common Issues', 'ai-calendar'); ?></h2>
                
                <h3><?php _e('Calendar Not Displaying', 'ai-calendar'); ?></h3>
                <ul>
                    <li><?php _e('Verify the shortcode is correctly placed: [ai_calendar]', 'ai-calendar'); ?></li>
                    <li><?php _e('Check if your theme conflicts with the calendar styles', 'ai-calendar'); ?></li>
                    <li><?php _e('Try disabling other plugins to check for conflicts', 'ai-calendar'); ?></li>
                </ul>

                <h3><?php _e('Theme Settings Not Saving', 'ai-calendar'); ?></h3>
                <ul>
                    <li><?php _e('Clear your browser cache and reload the page', 'ai-calendar'); ?></li>
                    <li><?php _e('Ensure you have proper permissions (admin role)', 'ai-calendar'); ?></li>
                    <li><?php _e('Check if your server has write permissions', 'ai-calendar'); ?></li>
                </ul>

                <h3><?php _e('Events Not Showing', 'ai-calendar'); ?></h3>
                <ul>
                    <li><?php _e('Verify events are published and not in draft status', 'ai-calendar'); ?></li>
                    <li><?php _e('Check if event dates are set correctly', 'ai-calendar'); ?></li>
                    <li><?php _e('Clear the calendar cache if enabled', 'ai-calendar'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php _e('Troubleshooting Steps', 'ai-calendar'); ?></h2>
                <ol>
                    <li><?php _e('Enable WordPress debug mode', 'ai-calendar'); ?></li>
                    <li><?php _e('Check server error logs', 'ai-calendar'); ?></li>
                    <li><?php _e('Test with a default WordPress theme', 'ai-calendar'); ?></li>
                    <li><?php _e('Verify jQuery is loaded properly', 'ai-calendar'); ?></li>
                    <li><?php _e('Check browser console for JavaScript errors', 'ai-calendar'); ?></li>
                </ol>
            </div>

            <div class="card">
                <h2><?php _e('Support Resources', 'ai-calendar'); ?></h2>
                <ul>
                    <li><a href="#"><?php _e('Documentation', 'ai-calendar'); ?></a></li>
                    <li><a href="#"><?php _e('Support Forum', 'ai-calendar'); ?></a></li>
                    <li><a href="#"><?php _e('Bug Report', 'ai-calendar'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
    }
} 