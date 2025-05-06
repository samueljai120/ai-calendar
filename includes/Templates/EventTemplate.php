<?php
namespace AiCalendar\Templates;

if (!defined('ABSPATH')) exit;

class EventTemplate {
    private $post_type = 'ai_calendar_event';
    private $default_fields = [
        'start_date' => '',
        'end_date'   => '',
        'location'   => '',
        'venue'      => '',
        'organizer'  => '',
        'website'    => '',
        'cost'       => '',
        'capacity'   => '',
        'status'     => 'upcoming',
        'featured'   => false,
    ];

    public function __construct() {
        add_action('init', [$this, 'register_event_post_type']);
        add_action('add_meta_boxes', [$this, 'add_event_meta_boxes']);
        add_action('save_post', [$this, 'save_event_meta']);
        add_filter('single_template', [$this, 'load_event_template']);
        add_filter('the_content', [$this, 'display_event_details']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('rest_api_init', [$this, 'register_rest_fields']);
    }

    public function enqueue_admin_assets($hook) {
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            global $post;
            if ($post && $post->post_type === $this->post_type) {
                wp_enqueue_script(
                    'ai-calendar-admin',
                    plugins_url('assets/js/admin.js', dirname(dirname(__FILE__))),
                    ['jquery', 'wp-util'],
                    '1.0.0',
                    true
                );

                wp_localize_script('ai-calendar-admin', 'aiCalendarAdmin', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ai_calendar_event_nonce'),
                ]);
            }
        }
    }

    public function register_event_post_type() {
        $labels = [
            'name'               => __('Events', 'ai-calendar'),
            'singular_name'      => __('Event', 'ai-calendar'),
            'add_new'           => __('Add New Event', 'ai-calendar'),
            'add_new_item'      => __('Add New Event', 'ai-calendar'),
            'edit_item'         => __('Edit Event', 'ai-calendar'),
            'new_item'          => __('New Event', 'ai-calendar'),
            'view_item'         => __('View Event', 'ai-calendar'),
            'search_items'      => __('Search Events', 'ai-calendar'),
            'not_found'         => __('No events found', 'ai-calendar'),
            'not_found_in_trash'=> __('No events found in Trash', 'ai-calendar'),
            'menu_name'         => __('Events', 'ai-calendar'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-calendar',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'rewrite'            => ['slug' => 'events'],
            'menu_position'      => 5,
            'capability_type'    => 'post',
            'taxonomies'         => ['event_category', 'event_tag'],
        ];

        register_post_type($this->post_type, $args);

        // Register event categories
        register_taxonomy('event_category', $this->post_type, [
            'label'              => __('Event Categories', 'ai-calendar'),
            'hierarchical'       => true,
            'show_in_rest'       => true,
            'show_admin_column'  => true,
            'rewrite'            => ['slug' => 'event-category'],
        ]);

        // Register event tags
        register_taxonomy('event_tag', $this->post_type, [
            'label'              => __('Event Tags', 'ai-calendar'),
            'hierarchical'       => false,
            'show_in_rest'       => true,
            'show_admin_column'  => true,
            'rewrite'            => ['slug' => 'event-tag'],
        ]);
    }

    public function add_event_meta_boxes() {
        add_meta_box(
            'event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_event_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'event_options',
            __('Event Options', 'ai-calendar'),
            [$this, 'render_event_options_meta_box'],
            $this->post_type,
            'side',
            'default'
        );
    }

    public function render_event_meta_box($post) {
        wp_nonce_field('save_event_meta', 'event_meta_nonce');
        $meta = $this->get_event_meta($post->ID);
        ?>
        <div class="event-meta-box">
            <style>
                .event-meta-box { padding: 15px; }
                .event-field { margin-bottom: 20px; }
                .event-field label { display: block; margin-bottom: 5px; font-weight: 600; }
                .event-field input[type="text"],
                .event-field input[type="url"],
                .event-field input[type="number"],
                .event-field input[type="datetime-local"] { width: 100%; }
                .event-field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                .event-field .description { font-size: 12px; color: #666; margin-top: 5px; }
                .event-validation-error { color: #dc3232; font-size: 12px; margin-top: 5px; display: none; }
            </style>

            <div class="event-field-group">
                <div class="event-field">
                    <label for="event_start_date"><?php _e('Start Date & Time', 'ai-calendar'); ?> *</label>
                    <input type="datetime-local" 
                           id="event_start_date" 
                           name="event_meta[start_date]" 
                           value="<?php echo esc_attr($meta['start_date']); ?>"
                           required>
                    <div class="event-validation-error" id="start_date_error"></div>
                </div>

                <div class="event-field">
                    <label for="event_end_date"><?php _e('End Date & Time', 'ai-calendar'); ?> *</label>
                    <input type="datetime-local" 
                           id="event_end_date" 
                           name="event_meta[end_date]" 
                           value="<?php echo esc_attr($meta['end_date']); ?>"
                           required>
                    <div class="event-validation-error" id="end_date_error"></div>
                </div>
            </div>

            <div class="event-field">
                <label for="event_location"><?php _e('Location', 'ai-calendar'); ?></label>
                <input type="text" 
                       id="event_location" 
                       name="event_meta[location]" 
                       value="<?php echo esc_attr($meta['location']); ?>"
                       placeholder="Enter event location">
            </div>

            <div class="event-field">
                <label for="event_venue"><?php _e('Venue', 'ai-calendar'); ?></label>
                <input type="text" 
                       id="event_venue" 
                       name="event_meta[venue]" 
                       value="<?php echo esc_attr($meta['venue']); ?>"
                       placeholder="Enter venue name">
            </div>

            <div class="event-field">
                <label for="event_organizer"><?php _e('Organizer', 'ai-calendar'); ?></label>
                <input type="text" 
                       id="event_organizer" 
                       name="event_meta[organizer]" 
                       value="<?php echo esc_attr($meta['organizer']); ?>"
                       placeholder="Enter organizer name">
            </div>

            <div class="event-field">
                <label for="event_website"><?php _e('Event Website', 'ai-calendar'); ?></label>
                <input type="url" 
                       id="event_website" 
                       name="event_meta[website]" 
                       value="<?php echo esc_attr($meta['website']); ?>"
                       placeholder="https://">
            </div>

            <div class="event-field-group">
                <div class="event-field">
                    <label for="event_cost"><?php _e('Cost', 'ai-calendar'); ?></label>
                    <input type="text" 
                           id="event_cost" 
                           name="event_meta[cost]" 
                           value="<?php echo esc_attr($meta['cost']); ?>"
                           placeholder="Free or enter amount">
                </div>

                <div class="event-field">
                    <label for="event_capacity"><?php _e('Capacity', 'ai-calendar'); ?></label>
                    <input type="number" 
                           id="event_capacity" 
                           name="event_meta[capacity]" 
                           value="<?php echo esc_attr($meta['capacity']); ?>"
                           min="0"
                           placeholder="Leave empty for unlimited">
                </div>
            </div>
        </div>
        <?php
    }

    public function render_event_options_meta_box($post) {
        $meta = $this->get_event_meta($post->ID);
        ?>
        <div class="event-options-box">
            <div class="event-field">
                <label>
                    <input type="checkbox" 
                           name="event_meta[featured]" 
                           value="1" 
                           <?php checked($meta['featured'], '1'); ?>>
                    <?php _e('Featured Event', 'ai-calendar'); ?>
                </label>
            </div>

            <div class="event-field">
                <label for="event_status"><?php _e('Status', 'ai-calendar'); ?></label>
                <select id="event_status" name="event_meta[status]">
                    <option value="upcoming" <?php selected($meta['status'], 'upcoming'); ?>><?php _e('Upcoming', 'ai-calendar'); ?></option>
                    <option value="ongoing" <?php selected($meta['status'], 'ongoing'); ?>><?php _e('Ongoing', 'ai-calendar'); ?></option>
                    <option value="completed" <?php selected($meta['status'], 'completed'); ?>><?php _e('Completed', 'ai-calendar'); ?></option>
                    <option value="cancelled" <?php selected($meta['status'], 'cancelled'); ?>><?php _e('Cancelled', 'ai-calendar'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }

    public function save_event_meta($post_id) {
        if (!isset($_POST['event_meta_nonce']) || 
            !wp_verify_nonce($_POST['event_meta_nonce'], 'save_event_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['event_meta'])) {
            $meta = $_POST['event_meta'];
            
            // Validate required fields
            if (empty($meta['start_date']) || empty($meta['end_date'])) {
                return;
            }

            // Sanitize and save each meta field
            foreach ($this->default_fields as $key => $default) {
                $value = isset($meta[$key]) ? $meta[$key] : $default;
                
                // Special sanitization for different field types
                switch ($key) {
                    case 'start_date':
                    case 'end_date':
                        $value = sanitize_text_field($value);
                        break;
                    case 'website':
                        $value = esc_url_raw($value);
                        break;
                    case 'capacity':
                        $value = absint($value);
                        break;
                    case 'featured':
                        $value = isset($meta[$key]) ? '1' : '0';
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }

                update_post_meta($post_id, '_event_' . $key, $value);
            }

            // Update event status based on dates
            $this->update_event_status($post_id);
        }
    }

    private function update_event_status($post_id) {
        $start_date = get_post_meta($post_id, '_event_start_date', true);
        $end_date = get_post_meta($post_id, '_event_end_date', true);
        $current_status = get_post_meta($post_id, '_event_status', true);

        if ($current_status === 'cancelled') {
            return; // Don't auto-update if manually cancelled
        }

        $now = current_time('mysql');
        $status = 'upcoming';

        if ($start_date <= $now && $end_date >= $now) {
            $status = 'ongoing';
        } elseif ($end_date < $now) {
            $status = 'completed';
        }

        update_post_meta($post_id, '_event_status', $status);
    }

    private function get_event_meta($post_id) {
        $meta = [];
        foreach ($this->default_fields as $key => $default) {
            $meta[$key] = get_post_meta($post_id, '_event_' . $key, true);
            if ($meta[$key] === '') {
                $meta[$key] = $default;
            }
        }
        return $meta;
    }

    public function register_rest_fields() {
        register_rest_field($this->post_type, 'event_meta', [
            'get_callback' => [$this, 'get_event_rest_meta'],
            'schema' => [
                'description' => __('Event meta data', 'ai-calendar'),
                'type'        => 'object',
                'context'     => ['view', 'edit'],
                'properties'  => [
                    'start_date' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    'location' => [
                        'type' => 'string',
                    ],
                    'venue' => [
                        'type' => 'string',
                    ],
                    'organizer' => [
                        'type' => 'string',
                    ],
                    'website' => [
                        'type' => 'string',
                        'format' => 'uri',
                    ],
                    'cost' => [
                        'type' => 'string',
                    ],
                    'capacity' => [
                        'type' => 'integer',
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['upcoming', 'ongoing', 'completed', 'cancelled'],
                    ],
                    'featured' => [
                        'type' => 'boolean',
                    ],
                ],
            ],
        ]);
    }

    public function get_event_rest_meta($post) {
        return $this->get_event_meta($post['id']);
    }

    public function display_event_details($content) {
        if (!is_singular($this->post_type)) {
            return $content;
        }

        $post_id = get_the_ID();
        $meta = $this->get_event_meta($post_id);
        
        ob_start();
        ?>
        <div class="event-details" data-event-id="<?php echo esc_attr($post_id); ?>">
            <div class="event-header">
                <?php if ($meta['featured']) : ?>
                    <div class="event-featured-badge">
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php _e('Featured Event', 'ai-calendar'); ?>
                    </div>
                <?php endif; ?>

                <div class="event-status <?php echo esc_attr($meta['status']); ?>">
                    <?php echo esc_html(ucfirst($meta['status'])); ?>
                </div>
            </div>

            <div class="event-meta">
                <div class="event-meta-primary">
                    <?php if ($meta['start_date']) : ?>
                        <div class="event-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <div class="event-date-details">
                                <div class="event-date-label"><?php _e('Date & Time', 'ai-calendar'); ?></div>
                                <div class="event-date-value">
                                    <?php
                                    $start = new \DateTime($meta['start_date']);
                                    $end = new \DateTime($meta['end_date']);
                                    echo $start->format('F j, Y g:i A');
                                    if ($meta['end_date']) {
                                        echo ' - ';
                                        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
                                            echo $end->format('g:i A');
                                        } else {
                                            echo $end->format('F j, Y g:i A');
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($meta['location']) : ?>
                        <div class="event-location">
                            <span class="dashicons dashicons-location"></span>
                            <div class="event-location-details">
                                <div class="event-location-label"><?php _e('Location', 'ai-calendar'); ?></div>
                                <div class="event-location-value"><?php echo esc_html($meta['location']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($meta['venue']) : ?>
                        <div class="event-venue">
                            <span class="dashicons dashicons-building"></span>
                            <div class="event-venue-details">
                                <div class="event-venue-label"><?php _e('Venue', 'ai-calendar'); ?></div>
                                <div class="event-venue-value"><?php echo esc_html($meta['venue']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="event-meta-secondary">
                    <?php if ($meta['organizer']) : ?>
                        <div class="event-organizer">
                            <span class="dashicons dashicons-groups"></span>
                            <div class="event-organizer-details">
                                <div class="event-organizer-label"><?php _e('Organizer', 'ai-calendar'); ?></div>
                                <div class="event-organizer-value"><?php echo esc_html($meta['organizer']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($meta['cost']) : ?>
                        <div class="event-cost">
                            <span class="dashicons dashicons-tickets-alt"></span>
                            <div class="event-cost-details">
                                <div class="event-cost-label"><?php _e('Price', 'ai-calendar'); ?></div>
                                <div class="event-cost-value"><?php echo esc_html($meta['cost']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($meta['capacity']) : ?>
                        <div class="event-capacity">
                            <span class="dashicons dashicons-groups"></span>
                            <div class="event-capacity-details">
                                <div class="event-capacity-label"><?php _e('Capacity', 'ai-calendar'); ?></div>
                                <div class="event-capacity-value">
                                    <?php printf(__('%d people', 'ai-calendar'), $meta['capacity']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="event-meta-additional">
                    <?php if ($meta['website']) : ?>
                        <div class="event-website">
                            <span class="dashicons dashicons-admin-links"></span>
                            <a href="<?php echo esc_url($meta['website']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php _e('Event Website', 'ai-calendar'); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php
                    $categories = get_the_terms($post_id, 'event_category');
                    if ($categories && !is_wp_error($categories)) : ?>
                        <div class="event-categories">
                            <span class="dashicons dashicons-category"></span>
                            <div class="event-categories-details">
                                <div class="event-categories-label"><?php _e('Categories', 'ai-calendar'); ?></div>
                                <div class="event-categories-value">
                                    <?php echo implode(', ', wp_list_pluck($categories, 'name')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    $tags = get_the_terms($post_id, 'event_tag');
                    if ($tags && !is_wp_error($tags)) : ?>
                        <div class="event-tags">
                            <span class="dashicons dashicons-tag"></span>
                            <div class="event-tags-details">
                                <div class="event-tags-label"><?php _e('Tags', 'ai-calendar'); ?></div>
                                <div class="event-tags-value">
                                    <?php echo implode(', ', wp_list_pluck($tags, 'name')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <style>
            .event-details {
                background: var(--calendar-background, #f8f9fa);
                border-radius: 8px;
                padding: 30px;
                margin-bottom: 30px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .event-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .event-featured-badge {
                background: var(--calendar-primary, #4a90e2);
                color: white;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 10px;
                border-radius: 4px;
            }

            .event-meta {
                display: grid;
                gap: 30px;
            }

            .event-meta-primary,
            .event-meta-secondary,
            .event-meta-additional {
                display: grid;
                gap: 20px;
            }

            .event-meta > div > div {
                display: flex;
                align-items: flex-start;
                gap: 15px;
            }

            .event-meta .dashicons {
                color: var(--calendar-primary, #4a90e2);
                margin-top: 3px;
            }

            [class*="-details"] {
                flex: 1;
            }

            [class*="-label"] {
                color: var(--calendar-text-secondary, #666);
                font-size: 0.9em;
                margin-bottom: 4px;
            }

            [class*="-value"] {
                color: var(--calendar-text, #333);
                font-weight: 500;
            }

            .event-status {
                display: inline-flex;
                align-items: center;
                padding: 6px 12px;
                border-radius: 4px;
                font-weight: 500;
                font-size: 0.9em;
            }

            .event-status.upcoming {
                background: #e3f2fd;
                color: #1976d2;
            }

            .event-status.ongoing {
                background: #e8f5e9;
                color: #2e7d32;
            }

            .event-status.completed {
                background: #f5f5f5;
                color: #616161;
            }

            .event-status.cancelled {
                background: #ffebee;
                color: #c62828;
            }

            .event-website a {
                color: var(--calendar-primary, #4a90e2);
                text-decoration: none;
                font-weight: 500;
            }

            .event-website a:hover {
                text-decoration: underline;
            }

            @media (min-width: 768px) {
                .event-meta-primary,
                .event-meta-secondary {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (prefers-color-scheme: dark) {
                .event-details {
                    background: var(--calendar-background, #2d3748);
                }
                [class*="-value"] {
                    color: var(--calendar-text, #e2e8f0);
                }
                [class*="-label"] {
                    color: var(--calendar-text-secondary, #a0aec0);
                }
            }
        </style>

        <?php
        $event_details = ob_get_clean();
        return $event_details . $content;
    }

    public function load_event_template($template) {
        global $post;

        if ($post->post_type === $this->post_type) {
            $plugin_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/single-event.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
} 