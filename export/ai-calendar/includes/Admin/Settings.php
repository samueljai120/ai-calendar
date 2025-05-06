<?php
namespace AiCalendar\Admin;

use AiCalendar\Frontend\Frontend;

class Settings {
    private $frontend;

    public function __construct() {
        $this->frontend = new Frontend();
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'ai-calendar',
            'Settings',
            'Settings',
            'manage_options',
            'ai-calendar-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Theme Settings Section
        add_settings_section(
            'ai_calendar_theme_settings',
            'Theme Settings',
            [$this, 'render_theme_settings_section'],
            'ai-calendar-settings'
        );

        // Calendar Layout Section
        add_settings_section(
            'ai_calendar_layout_settings',
            'Calendar Layout',
            [$this, 'render_layout_settings_section'],
            'ai-calendar-settings'
        );

        // Event Styles Section
        add_settings_section(
            'ai_calendar_event_styles',
            'Event Styles',
            [$this, 'render_event_styles_section'],
            'ai-calendar-settings'
        );

        // Register settings fields
        register_setting('ai-calendar-settings', 'ai_calendar_settings');
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('ai_calendar_settings', []);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-calendar-settings-container">
                <div class="ai-calendar-settings-form">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('ai-calendar-settings');
                        do_settings_sections('ai-calendar-settings');
                        submit_button('Save Settings');
                        ?>
                    </form>
                </div>
                
                <div class="ai-calendar-preview">
                    <h2>Live Preview</h2>
                    <div class="preview-container">
                        <?php echo $this->frontend->render_calendar(); ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .ai-calendar-settings-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
                margin-top: 2rem;
            }
            .ai-calendar-settings-form {
                background: #fff;
                padding: 2rem;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .ai-calendar-preview {
                background: #fff;
                padding: 2rem;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                position: sticky;
                top: 32px;
            }
            .preview-container {
                max-width: 100%;
                overflow: auto;
            }
        </style>
        <?php
    }

    public function render_theme_settings_section() {
        echo '<p>Customize the overall appearance of the calendar.</p>';
    }

    public function render_layout_settings_section() {
        echo '<p>Adjust the calendar layout and dimensions.</p>';
    }

    public function render_event_styles_section() {
        echo '<p>Configure the appearance of events in the calendar.</p>';
    }

    public function enqueue_admin_assets($hook) {
        if ('ai-calendar_page_ai-calendar-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();
        
        wp_enqueue_script(
            'ai-calendar-admin',
            plugins_url('assets/js/admin.js', dirname(dirname(__FILE__))),
            ['jquery', 'wp-color-picker'],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
            true
        );

        wp_localize_script('ai-calendar-admin', 'aiCalendar', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_calendar_admin')
        ]);
    }
} 