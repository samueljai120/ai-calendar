<?php
namespace AiCalendar\Admin;

use \WP_Query;

class Admin {
    public function __construct() {
        try {
            error_log('AI Calendar: Constructing Admin class');
            
            // Register admin menu
            add_action('admin_menu', [$this, 'add_menu_pages']);
            
            // Other admin hooks
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            
            // Add meta boxes for event details
            add_action('add_meta_boxes', [$this, 'add_event_meta_boxes']);
            add_action('save_post_ai_calendar_event', [$this, 'save_event_meta']);
            
            error_log('AI Calendar: Admin class constructed successfully');
        } catch (Exception $e) {
            error_log('AI Calendar Error: ' . $e->getMessage());
        }
    }

    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function add_menu_pages() {
        add_menu_page(
            __('AI Calendar', 'ai-calendar'),
            __('AI Calendar', 'ai-calendar'),
            'manage_options',
            'ai-calendar',
            [$this, 'render_settings_page'],
            'dashicons-calendar-alt',
            30
        );
    }

    public function register_settings() {
        register_setting('ai_calendar_settings', 'ai_calendar_settings');

        add_settings_section(
            'ai_calendar_general',
            __('General Settings', 'ai-calendar'),
            [$this, 'render_general_section'],
            'ai_calendar_settings'
        );

        add_settings_field(
            'calendar_theme',
            __('Calendar Theme', 'ai-calendar'),
            [$this, 'render_theme_field'],
            'ai_calendar_settings',
            'ai_calendar_general'
        );
    }

    public function enqueue_scripts($hook) {
        if ('toplevel_page_ai-calendar' !== $hook && !in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        wp_enqueue_style(
            'ai-calendar-admin',
            AI_CALENDAR_URL . 'assets/css/admin.css',
            [],
            AI_CALENDAR_VERSION
        );

        $screen = get_current_screen();
        
        if (!$screen) {
            return;
        }
        
        // Debug information
        error_log("Admin scripts - Current screen: " . $screen->id);
        
        // Always load the main admin script
        wp_enqueue_script(
            'ai-calendar-admin',
            AI_CALENDAR_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_CALENDAR_VERSION,
            true
        );
        
        // Load the block editor extension for event pages
        if ($screen->base === 'post' && $screen->post_type === 'ai_calendar_event' ||
            $hook === 'post.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'ai_calendar_event' ||
            $hook === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'ai_calendar_event' ||
            // Also check for existing post being edited
            ($hook === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'ai_calendar_event')) {
            
            error_log("Loading block editor extension for event page");
            
            // Load our direct injection script first - use higher priority
            wp_enqueue_script(
                'ai-calendar-block-editor-direct-inject',
                AI_CALENDAR_URL . 'assets/js/block-editor-direct-inject.js',
                array('jquery', 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch'),
                AI_CALENDAR_VERSION . '.' . time(), // Add timestamp to prevent caching
                true
            );
            
            // Original script as backup
            wp_enqueue_script(
                'ai-calendar-block-editor-extension',
                AI_CALENDAR_URL . 'assets/js/block-editor-extension.js',
                array('jquery', 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor'),
                AI_CALENDAR_VERSION,
                true
            );
        }
        
        // Load the event time fixer script for event edit screens
        if ($screen->id === 'ai_calendar_event' || 
            $screen->base === 'post' && $screen->post_type === 'ai_calendar_event' ||
            $hook === 'post.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'ai_calendar_event' ||
            $hook === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'ai_calendar_event' ||
            // Also check for existing post being edited
            ($hook === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'ai_calendar_event')) {
            
            error_log("Loading event time fixer script for screen: {$screen->id}, hook: {$hook}");
            
            wp_enqueue_script(
                'ai-calendar-event-time-fixer',
                AI_CALENDAR_URL . 'assets/js/event-time-fixer.js',
                array('jquery'),
                AI_CALENDAR_VERSION,
                true
            );
            
            // Add inline script to set current values
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
            
            if ($post_id) {
                $start_time = get_post_meta($post_id, '_event_start_time', true);
                $end_time = get_post_meta($post_id, '_event_end_time', true);
                $is_full_day = get_post_meta($post_id, '_event_is_full_day', true) === '1';
                
                $inline_script = "
                    window.aiCalendarEventData = {
                        id: {$post_id},
                        startTime: " . json_encode($start_time) . ",
                        endTime: " . json_encode($end_time) . ",
                        isFullDay: " . ($is_full_day ? 'true' : 'false') . "
                    };
                    console.log('Event data loaded:', window.aiCalendarEventData);
                ";
                
                wp_add_inline_script('ai-calendar-event-time-fixer', $inline_script, 'before');
            }
        }
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('ai_calendar_settings');
                do_settings_sections('ai_calendar_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_general_section() {
        echo '<p>' . __('Configure general settings for the calendar.', 'ai-calendar') . '</p>';
    }

    public function render_theme_field() {
        $options = get_option('ai_calendar_settings');
        $theme = isset($options['theme']) ? $options['theme'] : 'light';
        ?>
        <select name="ai_calendar_settings[theme]" id="calendar_theme">
            <option value="light" <?php selected($theme, 'light'); ?>><?php _e('Light', 'ai-calendar'); ?></option>
            <option value="dark" <?php selected($theme, 'dark'); ?>><?php _e('Dark', 'ai-calendar'); ?></option>
        </select>
        <?php
    }

    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-calendar-dashboard">
                <div class="dashboard-grid">
                    <div class="dashboard-main">
                        <div class="dashboard-section stats-section">
                            <h2><?php _e('Calendar Overview', 'ai-calendar'); ?></h2>
                            <div class="stats-grid">
                                <?php
                                $total_events = wp_count_posts('ai_calendar_event');
                                $upcoming_events = new WP_Query([
                                    'post_type' => 'ai_calendar_event',
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => '_event_start',
                                            'value' => current_time('Y-m-d H:i:s'),
                                            'compare' => '>=',
                                            'type' => 'DATETIME'
                                        ]
                                    ]
                                ]);
                                ?>
                                <div class="stat-card">
                                    <h3><?php _e('Total Events', 'ai-calendar'); ?></h3>
                                    <div class="stat-value"><?php echo esc_html($total_events->publish); ?></div>
                                </div>
                                <div class="stat-card">
                                    <h3><?php _e('Upcoming Events', 'ai-calendar'); ?></h3>
                                    <div class="stat-value"><?php echo esc_html($upcoming_events->post_count); ?></div>
                                </div>
                                <div class="stat-card">
                                    <h3><?php _e('Draft Events', 'ai-calendar'); ?></h3>
                                    <div class="stat-value"><?php echo esc_html($total_events->draft); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-section">
                            <h2><?php _e('Quick Actions', 'ai-calendar'); ?></h2>
                            <div class="quick-actions-grid">
                                <a href="<?php echo admin_url('post-new.php?post_type=ai_calendar_event'); ?>" class="quick-action-card">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <h3><?php _e('Add New Event', 'ai-calendar'); ?></h3>
                                    <p><?php _e('Create a new event with title, date, location and more.', 'ai-calendar'); ?></p>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=ai_calendar_event'); ?>" class="quick-action-card">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <h3><?php _e('Manage Events', 'ai-calendar'); ?></h3>
                                    <p><?php _e('View, edit or delete existing events.', 'ai-calendar'); ?></p>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=ai-calendar-settings'); ?>" class="quick-action-card">
                                    <span class="dashicons dashicons-admin-appearance"></span>
                                    <h3><?php _e('Customize Calendar', 'ai-calendar'); ?></h3>
                                    <p><?php _e('Adjust colors, layout and styling options.', 'ai-calendar'); ?></p>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-side">
                        <div class="dashboard-section preview-section">
                            <h2><?php _e('Calendar Preview', 'ai-calendar'); ?></h2>
                            <div class="calendar-preview">
                                <?php echo do_shortcode('[ai_calendar]'); ?>
                            </div>
                        </div>
                </div>
                </div>
            </div>

            <style>
            .ai-calendar-dashboard {
                margin-top: 20px;
            }

            .dashboard-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
            }

            .dashboard-section {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                padding: 20px;
                margin-bottom: 20px;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-top: 15px;
            }

            .stat-card {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 15px;
                text-align: center;
            }

            .stat-card h3 {
                margin: 0 0 10px;
                color: #666;
                font-size: 14px;
            }

            .stat-value {
                font-size: 24px;
                font-weight: bold;
                color: #2271b1;
            }

            .quick-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }

            .quick-action-card {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 20px;
                text-align: center;
                text-decoration: none;
                color: #333;
                transition: all 0.2s ease;
            }

            .quick-action-card:hover {
                background: #fff;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }

            .quick-action-card .dashicons {
                font-size: 30px;
                width: 30px;
                height: 30px;
                color: #2271b1;
            }

            .quick-action-card h3 {
                margin: 10px 0;
                font-size: 16px;
            }

            .quick-action-card p {
                margin: 0;
                color: #666;
                font-size: 13px;
            }

            .preview-section {
                position: sticky;
                top: 32px;
            }

            .calendar-preview {
                margin-top: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
            }

            @media screen and (max-width: 1200px) {
                .dashboard-grid {
                    grid-template-columns: 1fr;
                }
                
                .preview-section {
                    position: relative;
                    top: 0;
                }
            }

            @media screen and (max-width: 782px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }
            }
            </style>
        </div>
        <?php
    }

    public function render_instructions_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Calendar Instructions', 'ai-calendar'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Basic Usage', 'ai-calendar'); ?></h2>
                <p><?php _e('To display the calendar on any page or post, simply add this shortcode:', 'ai-calendar'); ?></p>
                <code>[ai_calendar]</code>
            </div>

            <div class="card">
                <h2><?php _e('Managing Events', 'ai-calendar'); ?></h2>
                <ol>
                    <li><?php _e('Go to "All Events" to view and manage existing events', 'ai-calendar'); ?></li>
                    <li><?php _e('Click "Add New Event" to create a new event', 'ai-calendar'); ?></li>
                    <li><?php _e('For each event, you can set:', 'ai-calendar'); ?>
                        <ul>
                            <li><?php _e('Title', 'ai-calendar'); ?></li>
                            <li><?php _e('Description', 'ai-calendar'); ?></li>
                            <li><?php _e('Start date and time', 'ai-calendar'); ?></li>
                            <li><?php _e('End date and time', 'ai-calendar'); ?></li>
                            <li><?php _e('Location', 'ai-calendar'); ?></li>
                            <li><?php _e('Event color', 'ai-calendar'); ?></li>
                            <li><?php _e('Featured image', 'ai-calendar'); ?></li>
                        </ul>
                    </li>
                </ol>
            </div>

            <div class="card">
                <h2><?php _e('Frontend Features', 'ai-calendar'); ?></h2>
                <ul>
                    <li><?php _e('View calendar in month format', 'ai-calendar'); ?></li>
                    <li><?php _e('Navigate between months', 'ai-calendar'); ?></li>
                    <li><?php _e('Click on a day to view events for that day', 'ai-calendar'); ?></li>
                    <li><?php _e('Click on an event to view its details', 'ai-calendar'); ?></li>
                    <li><?php _e('Events are color-coded for easy identification', 'ai-calendar'); ?></li>
                </ul>
            </div>
        </div>

        <style>
        .card {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }

        .card h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .card code {
            display: inline-block;
            background: #f5f5f5;
            padding: 10px 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .card ul, .card ol {
            margin-left: 20px;
        }
        </style>
        <?php
    }

    public function add_event_meta_boxes() {
        add_meta_box(
            'ai_calendar_event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_event_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );
    }

    public function render_event_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('ai_calendar_event_meta_box', 'ai_calendar_event_meta_box_nonce');

        // Get existing values
        $start_date = get_post_meta($post->ID, '_event_start', true);
        $end_date = get_post_meta($post->ID, '_event_end', true);
        $location = get_post_meta($post->ID, '_event_location', true);
        $color = get_post_meta($post->ID, '_event_color', true);
        
        // Get time values
        $start_time = get_post_meta($post->ID, '_event_start_time', true);
        $end_time = get_post_meta($post->ID, '_event_end_time', true);
        $is_full_day = get_post_meta($post->ID, '_event_is_full_day', true) === '1';
        
        // Default to full day if both times are empty or 00:00
        if (empty($is_full_day)) {
            if ((empty($start_time) || $start_time === '00:00') && 
                (empty($end_time) || $end_time === '00:00')) {
                $is_full_day = true;
            }
        }
        
        // Output direct HTML for the Event Time Settings UI
        ?>
        <div id="ai-calendar-time-settings" style="
            background-color: #f0f7fb;
            border: 1px solid #3498db;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        ">
            <h3 style="margin-top: 0; margin-bottom: 15px; color: #2980b9;">
                <?php _e('Event Time Settings', 'ai-calendar'); ?>
            </h3>
            
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; font-weight: bold; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        id="_event_is_full_day" 
                        name="_event_is_full_day" 
                        value="1"
                        <?php checked($is_full_day, true); ?>
                        style="margin-right: 10px; width: auto;"
                        onchange="toggleTimeFields(this.checked)"
                    >
                    <span><?php _e('This is a full day event', 'ai-calendar'); ?></span>
                </label>
                <p style="color: #666; margin: 5px 0 0 25px; font-style: italic;">
                    <?php _e('Check this box if the event lasts all day without specific start/end times.', 'ai-calendar'); ?>
                </p>
            </div>
            
            <div id="event-time-fields" style="display: <?php echo $is_full_day ? 'none' : 'grid'; ?>; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div>
                    <label for="_event_start_time" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('Start Time', 'ai-calendar'); ?>
                    </label>
                    <input 
                        type="time" 
                        id="_event_start_time" 
                        name="_event_start_time" 
                        value="<?php echo esc_attr($start_time); ?>"
                        style="width: 100%;"
                        <?php echo $is_full_day ? 'disabled' : ''; ?>
                    >
                </div>
                <div>
                    <label for="_event_end_time" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php _e('End Time', 'ai-calendar'); ?>
                    </label>
                    <input 
                        type="time" 
                        id="_event_end_time" 
                        name="_event_end_time" 
                        value="<?php echo esc_attr($end_time); ?>"
                        style="width: 100%;"
                        <?php echo $is_full_day ? 'disabled' : ''; ?>
                    >
                </div>
            </div>
            
            <div style="margin-top: 15px; padding: 10px; background-color: #e7f4ff; border-left: 4px solid #2271b1; font-size: 13px;">
                <strong><?php _e('Note:', 'ai-calendar'); ?></strong>
                <?php _e('For events with specific times, uncheck "Full day event" and set the start and end times above. These times will appear in the calendar and event preview.', 'ai-calendar'); ?>
            </div>
        </div>
        
        <script>
        function toggleTimeFields(isFullDay) {
            var timeFields = document.getElementById('event-time-fields');
            var startTimeInput = document.getElementById('_event_start_time');
            var endTimeInput = document.getElementById('_event_end_time');
            
            if (isFullDay) {
                timeFields.style.display = 'none';
                startTimeInput.disabled = true;
                endTimeInput.disabled = true;
                startTimeInput.value = '';
                endTimeInput.value = '';
            } else {
                timeFields.style.display = 'grid';
                startTimeInput.disabled = false;
                endTimeInput.disabled = false;
            }
        }
        </script>
        <?php
        
        // Include the meta box template
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/admin/event-meta-box.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            _e('Error: Template file not found', 'ai-calendar');
            error_log('AI Calendar: Template file not found at ' . $template_path);
        }
    }

    public function save_event_meta($post_id) {
        // Check if our nonce is set.
        if (!isset($_POST['ai_calendar_event_meta_box_nonce'])) {
            return $post_id;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['ai_calendar_event_meta_box_nonce'], 'ai_calendar_event_meta_box')) {
            return $post_id;
        }

        // Check the user's permissions.
        if (isset($_POST['post_type']) && 'ai_calendar_event' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }

        // Save full day event status
        $is_full_day = isset($_POST['_event_is_full_day']) ? '1' : '0';
        update_post_meta($post_id, '_event_is_full_day', $is_full_day);
        
        // Save start and end time separately if not a full day event
        $start_time = isset($_POST['_event_start_time']) ? sanitize_text_field($_POST['_event_start_time']) : '';
        $end_time = isset($_POST['_event_end_time']) ? sanitize_text_field($_POST['_event_end_time']) : '';
        
        // For full day events, clear the time values
        if ($is_full_day === '1') {
            $start_time = '';
            $end_time = '';
        }
        
        update_post_meta($post_id, '_event_start_time', $start_time);
        update_post_meta($post_id, '_event_end_time', $end_time);
        
        // Debug logging
        error_log("Saving event $post_id - Is full day: $is_full_day");
        error_log("Saving event $post_id - Start time: $start_time");
        error_log("Saving event $post_id - End time: $end_time");

        // Save other meta fields
        $fields = [
            // Basic Event Information
            '_event_start',
            '_event_end',
            '_event_type',
            '_event_location',
            '_event_url',
            '_registration_email',
            '_registration_deadline',
            
            // Additional Information
            '_organizer_info',
            '_dress_code',
            '_age_restrictions',
            '_dietary_preferences',
            '_event_color',
            
            // Recurring Event Fields
            '_event_recurring',
            '_event_recurrence_type',
            '_event_recurrence_interval',
            '_event_recurrence_end_date'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                // Sanitize based on field type
                switch ($field) {
                    case '_event_start':
                    case '_event_end':
                    case '_registration_deadline':
                    case '_event_recurrence_end_date':
                        $value = sanitize_text_field($value);
                        break;
                        
                    case '_event_location':
                    case '_organizer_info':
                    case '_dress_code':
                        $value = sanitize_text_field($value);
                        break;
                        
                    case '_event_url':
                        $value = esc_url_raw($value);
                        break;
                        
                    case '_registration_email':
                        $value = sanitize_email($value);
                        break;
                        
                    case '_event_type':
                        $value = sanitize_text_field($value);
                        if (!in_array($value, ['in-person', 'virtual', 'hybrid'])) {
                            $value = 'in-person';
                        }
                        break;
                        
                    case '_age_restrictions':
                        $value = absint($value);
                        break;
                        
                    case '_dietary_preferences':
                        $allowed_values = ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'];
                        $value = array_filter((array)$value, function($v) use ($allowed_values) {
                            return in_array($v, $allowed_values);
                        });
                        break;
                        
                    case '_event_color':
                        $value = sanitize_hex_color($value);
                        break;
                        
                    case '_event_recurring':
                        $value = (bool) $value;
                        break;
                        
                    case '_event_recurrence_type':
                        $value = sanitize_text_field($value);
                        if (!in_array($value, ['daily', 'weekly', 'monthly', 'yearly'])) {
                            $value = 'daily';
                        }
                        break;
                        
                    case '_event_recurrence_interval':
                        $value = absint($value);
                        if ($value < 1) $value = 1;
                        if ($value > 365) $value = 365;
                        break;
                }
                
                update_post_meta($post_id, $field, $value);
            } else if ($field === '_event_recurring' || $field === '_dietary_preferences') {
                // If checkbox or checkbox group is unchecked, it won't be in $_POST
                delete_post_meta($post_id, $field);
            }
        }
        
        // Handle recurring events
        if (isset($_POST['_event_recurring']) && $_POST['_event_recurring']) {
            $this->generate_recurring_events($post_id);
        }
    }

    /**
     * Generate recurring event instances
     */
    private function generate_recurring_events($post_id) {
        $start_date = get_post_meta($post_id, '_event_start', true);
        $end_date = get_post_meta($post_id, '_event_end', true);
        $recurrence_type = get_post_meta($post_id, '_event_recurrence_type', true);
        $recurrence_interval = get_post_meta($post_id, '_event_recurrence_interval', true);
        $recurrence_end_date = get_post_meta($post_id, '_event_recurrence_end_date', true);

        if (!$start_date || !$end_date || !$recurrence_type || !$recurrence_interval || !$recurrence_end_date) {
            return;
        }

        try {
            $start = new \DateTime($start_date);
            $end = new \DateTime($end_date);
            $until = new \DateTime($recurrence_end_date);
            $interval = new \DateInterval($this->get_date_interval($recurrence_type, $recurrence_interval));
            
            // Store recurring instances
            $instances = [];
            $current_start = clone $start;
            $current_end = clone $end;
            
            while ($current_start <= $until) {
                $instances[] = [
                    'start' => $current_start->format('Y-m-d H:i:s'),
                    'end' => $current_end->format('Y-m-d H:i:s')
                ];
                
                $current_start->add($interval);
                $current_end->add($interval);
            }
            
            // Store the recurring instances
            update_post_meta($post_id, '_event_instances', $instances);
            
        } catch (\Exception $e) {
            error_log('AI Calendar Error: Failed to generate recurring events - ' . $e->getMessage());
        }
    }

    /**
     * Get DateInterval format based on recurrence type
     */
    private function get_date_interval($type, $interval) {
        switch ($type) {
            case 'daily':
                return "P{$interval}D";
            case 'weekly':
                return "P{$interval}W";
            case 'monthly':
                return "P{$interval}M";
            case 'yearly':
                return "P{$interval}Y";
            default:
                return "P1D";
        }
    }

    /**
     * Initialize default theme settings if not already set
     */
    public function initialize_theme_settings() {
        $theme_settings = get_option('ai_calendar_theme_settings', false);
        
        if ($theme_settings === false) {
            $default_settings = [
                'enable_theme' => true,
                'theme' => 'modern',
                'colors' => [
                    'primary' => '#3182ce',
                    'secondary' => '#4a5568',
                    'background' => '#ffffff',
                    'text' => '#2d3748'
                ]
            ];
            
            update_option('ai_calendar_theme_settings', $default_settings);
        }
    }
} 