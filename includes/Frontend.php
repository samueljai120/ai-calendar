<?php
namespace AiCalendar;

use AiCalendar\Settings\ThemeSettings;

class Frontend {
    private $theme_settings;
    
    public function __construct() {
        $this->theme_settings = new ThemeSettings();
        
        // Register shortcode first
        add_shortcode('ai_calendar', [$this, 'render_calendar']);
        
        // Then add other actions
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_fetch_calendar_events', [$this, 'get_month_events']);
        add_action('wp_ajax_nopriv_fetch_calendar_events', [$this, 'get_month_events']);
    }
    
    public function enqueue_assets() {
        // Enqueue styles
        wp_enqueue_style(
            'ai-calendar',
            plugins_url('/assets/css/frontend.css', dirname(__FILE__)),
            [],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0'
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'ai-calendar',
            plugins_url('/assets/js/frontend.js', dirname(__FILE__)),
            ['jquery'],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
            true
        );

        // Enqueue single event assets if viewing a single event
        if (is_singular('ai_calendar_event')) {
            // Enqueue single event styles
            wp_enqueue_style(
                'ai-calendar-single',
                plugins_url('/assets/css/event-single.css', dirname(__FILE__)),
                ['ai-calendar'],
                defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0'
            );

            // Enqueue single event JavaScript
            wp_enqueue_script(
                'ai-calendar-single',
                plugins_url('/assets/js/event-single.js', dirname(__FILE__)),
                ['jquery', 'ai-calendar'],
                defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
                true
            );

            // Add event data for JavaScript
            $event_id = get_the_ID();
            $event_data = [
                'id' => $event_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'startDate' => get_post_meta($event_id, '_event_start_date', true),
                'endDate' => get_post_meta($event_id, '_event_end_date', true),
                'location' => get_post_meta($event_id, '_event_location', true),
                'description' => wp_strip_all_tags(get_the_content())
            ];

            wp_localize_script('ai-calendar-single', 'aiCalendarEvent', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_calendar_event_nonce'),
                'eventData' => $event_data,
                'shareUrls' => [
                    'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(get_permalink()),
                    'twitter' => 'https://twitter.com/intent/tweet?url=' . urlencode(get_permalink()) . '&text=' . urlencode(get_the_title()),
                    'linkedin' => 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode(get_permalink()) . '&title=' . urlencode(get_the_title())
                ]
            ]);
        }
        
        // Get theme settings
        $theme_settings = $this->theme_settings->get_current_theme();

        // Localize script with data
        wp_localize_script('ai-calendar', 'aiCalendar', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_calendar_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'pluginUrl' => plugins_url('', dirname(__FILE__)),
            'theme' => $theme_settings,
            'i18n' => [
                'months' => [
                    __('January', 'ai-calendar'),
                    __('February', 'ai-calendar'),
                    __('March', 'ai-calendar'),
                    __('April', 'ai-calendar'),
                    __('May', 'ai-calendar'),
                    __('June', 'ai-calendar'),
                    __('July', 'ai-calendar'),
                    __('August', 'ai-calendar'),
                    __('September', 'ai-calendar'),
                    __('October', 'ai-calendar'),
                    __('November', 'ai-calendar'),
                    __('December', 'ai-calendar')
                ],
                'weekdays' => [
                    __('Sunday', 'ai-calendar'),
                    __('Monday', 'ai-calendar'),
                    __('Tuesday', 'ai-calendar'),
                    __('Wednesday', 'ai-calendar'),
                    __('Thursday', 'ai-calendar'),
                    __('Friday', 'ai-calendar'),
                    __('Saturday', 'ai-calendar')
                ],
                'today' => __('Today', 'ai-calendar'),
                'noEvents' => __('No events for this day', 'ai-calendar'),
                'allDay' => __('All Day', 'ai-calendar'),
                'moreEvents' => __('more', 'ai-calendar')
            ]
        ]);
    }
    
    public function render_calendar($atts = []) {
        // Ensure scripts and styles are enqueued
        if (!wp_script_is('ai-calendar', 'enqueued')) {
            $this->enqueue_assets();
        }
        
        // Get current theme settings
        $current_theme = $this->theme_settings->get_current_theme();
        
        // Start output buffering
        ob_start();
        
        // Add theme class if enabled
        $theme_class = $current_theme['enable_theme'] ? ' theme-' . esc_attr($current_theme['theme']) : '';
        
        // Include the calendar template with theme class
        echo '<div class="ai-calendar' . $theme_class . '">';
        $template_path = dirname(dirname(__FILE__)) . '/templates/calendar.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>' . __('Calendar template not found.', 'ai-calendar') . '</p>';
            error_log('AI Calendar: Template file not found at ' . $template_path);
        }
        echo '</div>';
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    public function get_month_events() {
        check_ajax_referer('ai_calendar_nonce', 'nonce');
        
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        
        // Get start and end dates for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Query events that:
        // 1. Start within this month
        // 2. End within this month
        // 3. Span across this month
        $args = [
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                // Events that start within this month
                [
                    'key' => '_event_start_date',
                    'value' => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                // Events that end within this month
                [
                    'key' => '_event_end_date',
                    'value' => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                // Events that span across this month
                [
                    'relation' => 'AND',
                    [
                        'key' => '_event_start_date',
                        'value' => $start_date,
                        'compare' => '<=',
                        'type' => 'DATE'
                    ],
                    [
                        'key' => '_event_end_date',
                        'value' => $end_date,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ]
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC'
        ];
        
        $query = new \WP_Query($args);
        $formatted_events = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                $start_date = get_post_meta($event_id, '_event_start_date', true);
                $end_date = get_post_meta($event_id, '_event_end_date', true);
                
                if (!$start_date) {
                    continue;
                }
                
                // For multi-day events, create an entry for each day
                $current_date = new \DateTime($start_date);
                $end_datetime = $end_date ? new \DateTime($end_date) : $current_date;
                
                // Get event details that will be the same for all days
                $event_details = [
                    'id' => $event_id,
                    'title' => html_entity_decode(get_the_title()),
                    'description' => wp_trim_words(get_the_excerpt(), 20),
                    'location' => get_post_meta($event_id, '_event_location', true),
                    'url' => get_permalink(),
                    'featured_image' => get_the_post_thumbnail_url($event_id, 'thumbnail'),
                    'is_multi_day' => $start_date !== ($end_date ?: $start_date)
                ];
                
                // Add event to each day it occurs on
                while ($current_date <= $end_datetime) {
                    $date_key = $current_date->format('Y-m-d');
                    
                    // Only add events for days within the requested month
                    if ($current_date->format('Y-m') === sprintf('%04d-%02d', $year, $month)) {
                        if (!isset($formatted_events[$date_key])) {
                            $formatted_events[$date_key] = [];
                        }
                        
                        // Add day-specific details
                        $event_day = array_merge($event_details, [
                            'is_start' => $date_key === $start_date,
                            'is_end' => $date_key === ($end_date ?: $start_date),
                            'start_time' => date('H:i', strtotime($start_date)),
                            'end_time' => $end_date ? date('H:i', strtotime($end_date)) : null
                        ]);
                        
                        $formatted_events[$date_key][] = $event_day;
                    }
                    
                    $current_date->modify('+1 day');
                }
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($formatted_events);
    }

    private function get_month_names() {
        return [
            __('January', 'ai-calendar'),
            __('February', 'ai-calendar'),
            __('March', 'ai-calendar'),
            __('April', 'ai-calendar'),
            __('May', 'ai-calendar'),
            __('June', 'ai-calendar'),
            __('July', 'ai-calendar'),
            __('August', 'ai-calendar'),
            __('September', 'ai-calendar'),
            __('October', 'ai-calendar'),
            __('November', 'ai-calendar'),
            __('December', 'ai-calendar')
        ];
    }

    private function get_weekday_names() {
        return [
            __('Sunday', 'ai-calendar'),
            __('Monday', 'ai-calendar'),
            __('Tuesday', 'ai-calendar'),
            __('Wednesday', 'ai-calendar'),
            __('Thursday', 'ai-calendar'),
            __('Friday', 'ai-calendar'),
            __('Saturday', 'ai-calendar')
        ];
    }
} 