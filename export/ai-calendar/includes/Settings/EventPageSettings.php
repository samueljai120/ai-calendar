<?php

namespace AiCalendar\Settings;

class EventPageSettings {
    private $option_group = 'ai_calendar_event_settings_group';
    private $option_name = 'ai_calendar_event_settings';

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_save_event_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_save_event_page_settings', [$this, 'ajax_save_event_page_settings']);
        add_action('wp_ajax_get_event_preview', [$this, 'ajax_get_preview']);
        add_filter('template_include', [$this, 'load_event_template']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            [
                'type' => 'array',
                'description' => 'AI Calendar event page settings',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings()
            ]
        );
    }

    private function get_default_settings() {
        return [
            'template' => 'none',
            'show_featured_image' => true,
            'show_date' => true,
            'show_time' => true,
            'show_location' => true,
            'show_description' => true,
            'show_map' => false,
            'show_related_events' => false
        ];
    }

    public function get_templates() {
        return [
            'none' => [
                'name' => __('No Template', 'ai-calendar'),
                'description' => __('Use your theme\'s default single post template', 'ai-calendar'),
                'file' => '',
                'features' => [
                    __('Falls back to your theme\'s post template', 'ai-calendar'),
                    __('No modifications to your theme', 'ai-calendar'),
                    __('Native theme styling', 'ai-calendar')
                ]
            ],
            'template-1' => [
                'name' => __('Modern Layout', 'ai-calendar'),
                'description' => __('Full-width design with featured image banner', 'ai-calendar'),
                'file' => 'single-event-template-1.php',
                'preview_image' => 'template-1-preview.png',
                'features' => [
                    __('Full-width featured image banner', 'ai-calendar'),
                    __('Clean and modern layout', 'ai-calendar'),
                    __('Optimized for visual impact', 'ai-calendar'),
                    __('Mobile-responsive design', 'ai-calendar'),
                    __('Focused event details', 'ai-calendar')
                ]
            ],
            'template-2' => [
                'name' => __('Sidebar Layout', 'ai-calendar'),
                'description' => __('Two-column layout with event details in sidebar', 'ai-calendar'),
                'file' => 'single-event-template-2.php',
                'preview_image' => 'template-2-preview.png',
                'features' => [
                    __('Sidebar with event details', 'ai-calendar'),
                    __('Organized content layout', 'ai-calendar'),
                    __('Perfect for detailed events', 'ai-calendar'),
                    __('Enhanced readability', 'ai-calendar'),
                    __('Interactive calendar integration', 'ai-calendar')
                ]
            ]
        ];
    }

    public function get_current_settings() {
        $defaults = $this->get_default_settings();
        $settings = get_option($this->option_name, []);
        
        // Ensure boolean values for display options
        $display_options = [
            'show_featured_image',
            'show_date',
            'show_time',
            'show_location',
            'show_description',
            'show_map',
            'show_related_events'
        ];
        
        foreach ($display_options as $option) {
            if (isset($settings[$option])) {
                $settings[$option] = (bool) $settings[$option];
            }
        }
        
        return wp_parse_args($settings, $defaults);
    }

    public function sanitize_settings($input) {
        if (!is_array($input)) {
            return $this->get_default_settings();
        }

        $sanitized = [];
        $templates = array_keys($this->get_templates());

        // Sanitize template
        $sanitized['template'] = in_array($input['template'], $templates) ? $input['template'] : 'none';

        // Sanitize display options
        $display_options = [
            'show_featured_image',
            'show_date',
            'show_time',
            'show_location',
            'show_description',
            'show_map',
            'show_related_events'
        ];

        foreach ($display_options as $option) {
            $sanitized[$option] = isset($input[$option]) ? (bool) $input[$option] : false;
        }

        return $sanitized;
    }

    public function ajax_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-calendar')]);
            return;
        }

        if (!check_ajax_referer('ai_calendar_event_settings', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'ai-calendar')]);
            return;
        }
        
        // Log the received data for debugging
        error_log('AI Calendar: Received event settings data: ' . print_r($_POST, true));

        if (empty($_POST['settings'])) {
            wp_send_json_error(['message' => __('No settings data received.', 'ai-calendar')]);
            return;
        }

        $settings = [];
        
        // Process template selection
        if (isset($_POST['settings']['template'])) {
            $settings['template'] = sanitize_text_field($_POST['settings']['template']);
        } else {
            $settings['template'] = 'none';
        }
        
        // Process display options (checkboxes)
        $display_options = [
            'show_featured_image',
            'show_date',
            'show_time',
            'show_location',
            'show_description',
            'show_map',
            'show_related_events'
        ];

        foreach ($display_options as $option) {
            $settings[$option] = isset($_POST['settings'][$option]) && 
                ($_POST['settings'][$option] === 'true' || $_POST['settings'][$option] === '1' || $_POST['settings'][$option] === true);
        }

        $sanitized_settings = $this->sanitize_settings($settings);
        
        // Log the sanitized settings
        error_log('AI Calendar: Sanitized event settings: ' . print_r($sanitized_settings, true));
        
        // Update the option in the database
        $updated = update_option($this->option_name, $sanitized_settings);
        
        error_log('AI Calendar: Event settings updated: ' . ($updated ? 'Yes' : 'No'));

        if ($updated) {
            wp_send_json_success([
                'message' => __('Settings saved successfully.', 'ai-calendar'),
                'settings' => $sanitized_settings
            ]);
        } else {
            wp_send_json_success([
                'message' => __('No changes were made to settings.', 'ai-calendar'),
                'settings' => $sanitized_settings
            ]);
        }
    }

    public function ajax_get_preview() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-calendar')]);
        }

        if (!check_ajax_referer('ai_calendar_event_settings', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'ai-calendar')]);
        }

        $date = sanitize_text_field($_POST['date']);
        $template = sanitize_text_field($_POST['template']);
        $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : [];

        // Get events for the selected date
        $events = $this->get_events_for_date($date);

        if (empty($events)) {
            wp_send_json_error(['message' => __('No events found for this date.', 'ai-calendar')]);
            return;
        }

        // Format events for list display
        $formatted_events = array_map(function($event) {
            $start_date = get_post_meta($event->ID, '_event_start_date', true);
            $end_date = get_post_meta($event->ID, '_event_end_date', true);
            $start_time = get_post_meta($event->ID, '_event_start_time', true);
            $end_time = get_post_meta($event->ID, '_event_end_time', true);
            $location = get_post_meta($event->ID, '_event_location', true);

            return [
                'id' => $event->ID,
                'title' => $event->post_title,
                'date' => $start_date === $end_date ? 
                    date_i18n(get_option('date_format'), strtotime($start_date)) :
                    sprintf(
                        __('From %s to %s', 'ai-calendar'),
                        date_i18n(get_option('date_format'), strtotime($start_date)),
                        date_i18n(get_option('date_format'), strtotime($end_date))
                    ),
                'time' => $start_time ? 
                    ($end_time ? 
                        sprintf(
                            __('%s - %s', 'ai-calendar'),
                            date_i18n(get_option('time_format'), strtotime($start_time)),
                            date_i18n(get_option('time_format'), strtotime($end_time))
                        ) : 
                        date_i18n(get_option('time_format'), strtotime($start_time))
                    ) : '',
                'location' => $location
            ];
        }, $events);

        // Get template content for first event
        ob_start();
        $this->render_preview_template($template, $events[0], $settings);
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'events' => $formatted_events
        ]);
    }

    private function get_events_for_date($date) {
        $args = [
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    // Event starts on this date
                    'key' => '_event_start_date',
                    'value' => $date,
                    'compare' => '='
                ],
                [
                    // Event ends on this date
                    'key' => '_event_end_date',
                    'value' => $date,
                    'compare' => '='
                ],
                [
                    // Event spans over this date
                    'relation' => 'AND',
                    [
                        'key' => '_event_start_date',
                        'value' => $date,
                        'compare' => '<='
                    ],
                    [
                        'key' => '_event_end_date',
                        'value' => $date,
                        'compare' => '>='
                    ]
                ]
            ]
        ];

        $query = new \WP_Query($args);
        return $query->posts;
    }

    private function render_preview_template($template_id, $event, $settings) {
        $templates = $this->get_templates();
        
        if (!isset($templates[$template_id]) || $template_id === 'none') {
            _e('Please select a template to preview.', 'ai-calendar');
            return;
        }

        $template_file = $templates[$template_id]['file'];
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/' . $template_file;

        if (!file_exists($template_path)) {
            _e('Template file not found.', 'ai-calendar');
            return;
        }

        // Set up preview data
        global $post;
        $post = $event;
        setup_postdata($post);

        // Include template
        include $template_path;

        wp_reset_postdata();
    }

    public function load_event_template($template) {
        if (is_singular('ai_calendar_event')) {
            $settings = $this->get_current_settings();
            $templates = $this->get_templates();
            
            if ($settings['template'] !== 'none' && isset($templates[$settings['template']])) {
                $template_file = $templates[$settings['template']]['file'];
                $custom_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/' . $template_file;
                
                if (file_exists($custom_template)) {
                    // Set display options as global variables for the template
                    global $ai_calendar_display_options;
                    $ai_calendar_display_options = $settings;
                    
                    return $custom_template;
                }
            }
        }
        
        return $template;
    }

    public function enqueue_scripts($hook) {
        // Only load on our settings page
        if (strpos($hook, 'ai-calendar') === false) {
            return;
        }
        
        // Add necessary scripts for the admin page
        wp_enqueue_script(
            'ai-calendar-event-settings',
            plugins_url('/assets/js/event-settings.js', dirname(dirname(__FILE__))),
            ['jquery'],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
            true
        );
        
        // Pass necessary data to the script
        wp_localize_script(
            'ai-calendar-event-settings',
            'aiCalendarEventSettings',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_calendar_event_settings'),
                'currentSettings' => $this->get_current_settings(),
                'templates' => $this->get_templates()
            ]
        );
    }

    // Add this new method for handling the new AJAX action
    public function ajax_save_event_page_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ai-calendar')]);
            return;
        }

        check_ajax_referer('ai_calendar_event_settings', 'event_settings_nonce');
        
        // Log the received data for debugging
        error_log('AI Calendar: Received event page settings: ' . print_r($_POST, true));
        
        $settings = [];
        
        // Get template from form data
        if (isset($_POST['settings']['template'])) {
            $settings['template'] = sanitize_text_field($_POST['settings']['template']);
        } else {
            $settings['template'] = 'none';
        }
        
        // Process checkboxes for display options
        $display_options = [
            'show_featured_image',
            'show_date',
            'show_time',
            'show_location',
            'show_description',
            'show_map',
            'show_related_events'
        ];
        
        foreach ($display_options as $option) {
            $settings[$option] = isset($_POST['settings'][$option]);
        }
        
        $sanitized_settings = $this->sanitize_settings($settings);
        
        // Log sanitized settings
        error_log('AI Calendar: Sanitized event page settings: ' . print_r($sanitized_settings, true));
        
        // Save the settings
        $updated = update_option($this->option_name, $sanitized_settings);
        
        if ($updated) {
            wp_send_json_success(['message' => __('Settings saved successfully.', 'ai-calendar')]);
        } else {
            wp_send_json_success(['message' => __('No changes were made to settings.', 'ai-calendar')]);
        }
    }
} 