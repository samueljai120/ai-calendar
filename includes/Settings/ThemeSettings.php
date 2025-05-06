<?php
namespace AiCalendar\Settings;

class ThemeSettings {
    private $option_name = 'ai_calendar_theme_settings';
    private $themes = [
        'modern' => [
            'name' => 'Modern',
            'description' => 'A modern and clean design with vibrant colors',
            'colors' => [
                'primary' => '#3182ce',
                'secondary' => '#4a5568',
                'background' => '#ffffff',
                'text' => '#2d3748',
                'border' => '#e2e8f0',
                'slot_background' => '#ffffff',
                'slot_text' => '#2d3748',
                'header_background' => '#f8fafc',
                'header_text' => '#2d3748',
                'event_background' => '#e3efff',
                'event_text' => '#0066cc',
                'event_hover_background' => '#cce3ff',
                'multi_day_event_background' => '#ffd700',
                'multi_day_event_text' => '#333333',
                'more_events_background' => '#f0f0f0',
                'more_events_text' => '#666666'
            ]
        ],
        'minimal' => [
            'name' => 'Minimal',
            'description' => 'A minimalist design focusing on content',
            'colors' => [
                'primary' => '#2d3748',
                'secondary' => '#4a5568',
                'background' => '#ffffff',
                'text' => '#1a202c',
                'border' => '#edf2f7',
                'slot_background' => '#f7fafc',
                'slot_text' => '#2d3748',
                'header_background' => '#f7fafc',
                'header_text' => '#2d3748',
                'event_background' => '#f7fafc',
                'event_text' => '#2d3748',
                'event_hover_background' => '#edf2f7',
                'multi_day_event_background' => '#e2e8f0',
                'multi_day_event_text' => '#2d3748',
                'more_events_background' => '#f7fafc',
                'more_events_text' => '#4a5568'
            ]
        ],
        'dark' => [
            'name' => 'Dark',
            'description' => 'A dark theme for better contrast',
            'colors' => [
                'primary' => '#90cdf4',
                'secondary' => '#63b3ed',
                'background' => '#1a202c',
                'text' => '#f7fafc',
                'border' => '#2d3748',
                'slot_background' => '#2d3748',
                'slot_text' => '#f7fafc',
                'header_background' => '#2d3748',
                'header_text' => '#f7fafc',
                'event_background' => '#2d3748',
                'event_text' => '#f7fafc',
                'event_hover_background' => '#4a5568',
                'multi_day_event_background' => '#4a5568',
                'multi_day_event_text' => '#f7fafc',
                'more_events_background' => '#2d3748',
                'more_events_text' => '#e2e8f0'
            ]
        ]
    ];

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_save_calendar_theme', [$this, 'ajax_save_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Debug logging for theme settings
        add_action('admin_init', function() {
            error_log('Current theme settings: ' . print_r($this->get_current_theme(), true));
        });
    }

    public function get_themes() {
        return $this->themes;
    }

    public function get_current_theme() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        
        // If theme is enabled but no theme is set, use modern theme
        if ($settings['enable_theme'] && empty($settings['theme'])) {
            $settings['theme'] = 'modern';
        }
        
        // If custom colors are empty but theme is set, use theme colors
        if (empty($settings['custom_colors']) && !empty($settings['theme'])) {
            $settings['custom_colors'] = $this->themes[$settings['theme']]['colors'];
        }
        
        return wp_parse_args($settings, $this->get_default_settings());
    }

    public function get_color_options() {
        return [
            // Calendar Base Colors
            'background' => [
                'label' => __('Calendar Background', 'ai-calendar'),
                'default' => '#ffffff',
                'options' => ['#ffffff', '#f7fafc', '#edf2f7', '#e2e8f0']
            ],
            'text' => [
                'label' => __('Calendar Text', 'ai-calendar'),
                'default' => '#2d3748',
                'options' => ['#2d3748', '#1a202c', '#4a5568', '#718096']
            ],
            'border' => [
                'label' => __('Calendar Border', 'ai-calendar'),
                'default' => '#e2e8f0',
                'options' => ['#e2e8f0', '#edf2f7', '#cbd5e0', '#a0aec0']
            ],
            
            // Header Colors
            'header_background' => [
                'label' => __('Header Background', 'ai-calendar'),
                'default' => '#f8fafc',
                'options' => ['#f8fafc', '#f7fafc', '#edf2f7', '#e2e8f0']
            ],
            'header_text' => [
                'label' => __('Header Text', 'ai-calendar'),
                'default' => '#2d3748',
                'options' => ['#2d3748', '#1a202c', '#4a5568', '#718096']
            ],
            
            // Day Slot Colors
            'slot_background' => [
                'label' => __('Day Cell Background', 'ai-calendar'),
                'default' => '#ffffff',
                'options' => ['#ffffff', '#f7fafc', '#edf2f7', '#e2e8f0']
            ],
            'slot_text' => [
                'label' => __('Day Cell Text', 'ai-calendar'),
                'default' => '#2d3748',
                'options' => ['#2d3748', '#1a202c', '#4a5568', '#718096']
            ],
            
            // Single Event Colors
            'event_background' => [
                'label' => __('Single Event Background', 'ai-calendar'),
                'default' => '#e3efff',
                'options' => ['#e3efff', '#ebf8ff', '#bee3f8', '#90cdf4']
            ],
            'event_text' => [
                'label' => __('Single Event Text', 'ai-calendar'),
                'default' => '#0066cc',
                'options' => ['#0066cc', '#2c5282', '#2b6cb0', '#2a4365']
            ],
            
            // Multi-day Event Colors
            'multi_day_event_background' => [
                'label' => __('Multi-day Event Background', 'ai-calendar'),
                'default' => '#ffd700',
                'options' => ['#ffd700', '#f6e05e', '#ecc94b', '#d69e2e']
            ],
            'multi_day_event_text' => [
                'label' => __('Multi-day Event Text', 'ai-calendar'),
                'default' => '#333333',
                'options' => ['#333333', '#1a202c', '#2d3748', '#4a5568']
            ],
            
            // More Events Colors
            'more_background' => [
                'label' => __('"+X more" Background', 'ai-calendar'),
                'default' => '#f0f0f0',
                'options' => ['#f0f0f0', '#e2e8f0', '#edf2f7', '#cbd5e0']
            ],
            'more_text' => [
                'label' => __('"+X more" Text', 'ai-calendar'),
                'default' => '#666666',
                'options' => ['#666666', '#4a5568', '#718096', '#a0aec0']
            ],
            
            // Navigation Colors
            'primary' => [
                'label' => __('Navigation Button Background', 'ai-calendar'),
                'default' => '#3182ce',
                'options' => ['#3182ce', '#2c5282', '#2b6cb0', '#4299e1']
            ],
            'secondary' => [
                'label' => __('Navigation Button Hover', 'ai-calendar'),
                'default' => '#4299e1',
                'options' => ['#4299e1', '#3182ce', '#63b3ed', '#90cdf4']
            ]
        ];
    }

    public function register_settings() {
        register_setting(
            'ai_calendar_theme_settings',
            $this->option_name,
            [
                'type' => 'array',
                'description' => 'AI Calendar theme settings',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings()
            ]
        );
    }

    public function sanitize_settings($settings) {
        if (!is_array($settings)) {
            return $this->get_default_settings();
        }
        
        $sanitized = [];
        
        // Sanitize enable_theme
        $sanitized['enable_theme'] = isset($settings['enable_theme']) && $settings['enable_theme'];
        
        // Sanitize theme
        $sanitized['theme'] = isset($settings['theme']) && array_key_exists($settings['theme'], $this->themes) 
            ? $settings['theme'] 
            : 'modern';
        
        // Sanitize custom colors
        $sanitized['custom_colors'] = [];
        if (isset($settings['custom_colors']) && is_array($settings['custom_colors'])) {
            foreach ($this->themes[$sanitized['theme']]['colors'] as $key => $default) {
                $color = isset($settings['custom_colors'][$key]) ? $settings['custom_colors'][$key] : $default;
                if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                    $sanitized['custom_colors'][$key] = $color;
                } else {
                    $sanitized['custom_colors'][$key] = $default;
                }
            }
        } else {
            // If no custom colors provided, use theme defaults
            $sanitized['custom_colors'] = $this->themes[$sanitized['theme']]['colors'];
        }
        
        return $sanitized;
    }

    public function ajax_save_settings() {
        // Debug logging
        error_log('Received theme settings save request');
        error_log('POST data: ' . print_r($_POST, true));
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-calendar')]);
        }

        if (!check_ajax_referer('ai_calendar_theme_settings', 'nonce', false)) {
            error_log('Nonce verification failed');
            wp_send_json_error(['message' => __('Invalid nonce.', 'ai-calendar')]);
        }

        if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
            error_log('Invalid settings data received');
            wp_send_json_error(['message' => __('Invalid settings data.', 'ai-calendar')]);
        }

        $settings = $this->sanitize_settings($_POST['settings']);
        error_log('Sanitized settings: ' . print_r($settings, true));
        
        // Save settings
        if (update_option($this->option_name, $settings)) {
            error_log('Settings saved successfully');
            wp_send_json_success([
                'message' => __('Settings saved successfully.', 'ai-calendar'),
                'settings' => $settings
            ]);
        } else {
            error_log('Failed to save settings');
            wp_send_json_error(['message' => __('Failed to save settings.', 'ai-calendar')]);
        }
    }

    private function get_default_settings() {
        return [
            'theme' => 'modern',
            'custom_colors' => [
                'primary' => '#3182ce',
                'secondary' => '#4299e1',
                'background' => '#ffffff',
                'text' => '#2d3748',
                'border' => '#e2e8f0',
                'header_background' => '#f8fafc',
                'header_text' => '#2d3748',
                'event_background' => '#e3efff',
                'event_text' => '#0066cc',
                'slot_background' => '#ffffff',
                'slot_text' => '#2d3748',
                'more_background' => '#f0f0f0',
                'more_text' => '#666666'
            ]
        ];
    }

    public function enqueue_assets($hook) {
        // Only load on our settings page
        if (strpos($hook, 'ai-calendar-theme-settings') === false) {
            return;
        }

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue our theme settings script
        wp_enqueue_script(
            'ai-calendar-theme-settings',
            plugins_url('/assets/js/theme-settings.js', dirname(dirname(__FILE__))),
            ['jquery', 'wp-color-picker'],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
            true
        );

        // Localize script with themes data
        wp_localize_script('ai-calendar-theme-settings', 'aiCalendarThemes', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_calendar_theme_settings'),
            'themes' => $this->themes
        ]);
    }
} 