<?php
namespace AiCalendar\Events;

class Events {
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_event_meta_boxes']);
        add_action('save_post', [$this, 'save_event_meta']);
    }

    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Events', 'Post type general name', 'ai-calendar'),
            'singular_name'         => _x('Event', 'Post type singular name', 'ai-calendar'),
            'menu_name'            => _x('Events', 'Admin Menu text', 'ai-calendar'),
            'add_new'              => __('Add New', 'ai-calendar'),
            'add_new_item'         => __('Add New Event', 'ai-calendar'),
            'edit_item'            => __('Edit Event', 'ai-calendar'),
            'view_item'            => __('View Event', 'ai-calendar'),
            'all_items'            => __('All Events', 'ai-calendar'),
            'search_items'         => __('Search Events', 'ai-calendar'),
            'not_found'            => __('No events found.', 'ai-calendar'),
            'not_found_in_trash'   => __('No events found in Trash.', 'ai-calendar')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'event'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'       => true
        );

        register_post_type('ai_calendar_event', $args);
    }

    public function add_event_meta_boxes() {
        add_meta_box(
            'event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_event_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );
    }

    public function render_event_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('event_meta_box', 'event_meta_box_nonce');

        // Get existing values
        $start_date = get_post_meta($post->ID, '_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_event_end_date', true);
        $location = get_post_meta($post->ID, '_event_location', true);

        ?>
        <div class="event-meta-box">
            <p>
                <label for="event_start_date"><?php _e('Start Date:', 'ai-calendar'); ?></label>
                <input type="datetime-local" id="event_start_date" name="event_start_date" value="<?php echo esc_attr($start_date); ?>">
            </p>
            <p>
                <label for="event_end_date"><?php _e('End Date:', 'ai-calendar'); ?></label>
                <input type="datetime-local" id="event_end_date" name="event_end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <p>
                <label for="event_location"><?php _e('Location:', 'ai-calendar'); ?></label>
                <input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($location); ?>">
            </p>
        </div>
        <?php
    }

    public function save_event_meta($post_id) {
        // Security checks
        if (!isset($_POST['event_meta_box_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['event_meta_box_nonce'], 'event_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save event meta
        if (isset($_POST['event_start_date'])) {
            update_post_meta($post_id, '_event_start_date', sanitize_text_field($_POST['event_start_date']));
        }
        if (isset($_POST['event_end_date'])) {
            update_post_meta($post_id, '_event_end_date', sanitize_text_field($_POST['event_end_date']));
        }
        if (isset($_POST['event_location'])) {
            update_post_meta($post_id, '_event_location', sanitize_text_field($_POST['event_location']));
        }
    }
} 