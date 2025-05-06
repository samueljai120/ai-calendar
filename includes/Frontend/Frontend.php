<?php
namespace AiCalendar\Frontend;

use \DateTime;
use \WP_Query;
use \Exception;

// Reference the plugin constants from root namespace
use const AI_CALENDAR_FILE;
use const AI_CALENDAR_VERSION;

class Frontend {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('ai_calendar', [$this, 'render_calendar']);
        add_action('wp_ajax_get_month_events', [$this, 'get_month_events']);
        add_action('wp_ajax_nopriv_get_month_events', [$this, 'get_month_events']);
        add_filter('single_template', [$this, 'load_single_event_template']);
        add_filter('template_include', [$this, 'load_archive_template']);
    }

    public function enqueue_scripts() {
        // Enqueue main calendar styles
        wp_enqueue_style(
            'ai-calendar',
            plugins_url('/assets/css/calendar.css', dirname(dirname(__FILE__))),
            [],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0'
        );

        // Enqueue calendar script
        wp_enqueue_script(
            'ai-calendar',
            plugins_url('/assets/js/frontend.js', dirname(dirname(__FILE__))),
            ['jquery'],
            defined('AI_CALENDAR_VERSION') ? AI_CALENDAR_VERSION : '1.0.0',
            true
        );

        // Get theme settings
        $theme_settings = new \AiCalendar\Settings\ThemeSettings();
        $current_theme = $theme_settings->get_current_theme();
        
        // Localize script with all necessary data
        wp_localize_script('ai-calendar', 'aiCalendar', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_calendar_nonce'),
            'theme' => $current_theme,
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
                'weekdaysShort' => [
                    __('Sun', 'ai-calendar'),
                    __('Mon', 'ai-calendar'),
                    __('Tue', 'ai-calendar'),
                    __('Wed', 'ai-calendar'),
                    __('Thu', 'ai-calendar'),
                    __('Fri', 'ai-calendar'),
                    __('Sat', 'ai-calendar')
                ]
            ]
        ]);
    }

    public function render_calendar($atts = []) {
        $atts = shortcode_atts([
            'view' => 'month',
            'category' => '',
            'tags' => ''
        ], $atts);

        ob_start();
        include dirname(dirname(dirname(__FILE__))) . '/templates/calendar.php';
        return ob_get_clean();
    }

    public function get_events($args = []) {
        $defaults = [
            'post_type' => 'ai_calendar_event',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC'
        ];

        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }

    public function get_month_events() {
        check_ajax_referer('ai_calendar_nonce', 'nonce');

        try {
            $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
            $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');

            $start_date = new DateTime("$year-$month-01");
            $end_date = clone $start_date;
            $end_date->modify('last day of this month');

            $args = [
                'post_type' => 'ai_calendar_event',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_event_start_date',
                        'value' => [
                            $start_date->format('Y-m-d'),
                            $end_date->format('Y-m-d')
                        ],
                        'compare' => 'BETWEEN',
                        'type' => 'DATE'
                    ]
                ],
                'orderby' => 'meta_value',
                'meta_key' => '_event_start_date',
                'order' => 'ASC'
            ];

            $events = [];
            $query = new WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $events[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'start' => get_post_meta(get_the_ID(), '_event_start_date', true),
                        'end' => get_post_meta(get_the_ID(), '_event_end_date', true),
                        'location' => get_post_meta(get_the_ID(), '_event_location', true),
                        'description' => get_the_excerpt(),
                        'url' => get_permalink()
                    ];
                }
                wp_reset_postdata();
            }

            wp_send_json_success($events);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function load_single_event_template($template) {
        global $post;

        if ($post->post_type === 'ai_calendar_event') {
            $custom_template = dirname(dirname(dirname(__FILE__))) . '/templates/single-event.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    public function load_archive_template($template) {
        if (is_post_type_archive('ai_calendar_event')) {
            $archive_template = dirname(dirname(dirname(__FILE__))) . '/templates/archive-event.php';
            
            if (file_exists($archive_template)) {
                return $archive_template;
            }
        }

        return $template;
    }
} 