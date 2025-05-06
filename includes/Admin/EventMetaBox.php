<?php
namespace AiCalendar\Admin;

class EventMetaBox {
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_event_meta_box']);
        add_action('save_post_ai_calendar_event', [$this, 'save_event_meta']);
    }

    public function add_event_meta_box() {
        add_meta_box(
            'ai_calendar_event_details',
            __('Event Details', 'ai-calendar'),
            [$this, 'render_meta_box'],
            'ai_calendar_event',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('ai_calendar_event_meta', 'ai_calendar_event_nonce');

        // Get saved values
        $start_date = get_post_meta($post->ID, '_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_event_end_date', true);
        $location = get_post_meta($post->ID, '_event_location', true);

        // Format dates for input if they exist
        $start_date_formatted = $start_date ? date('Y-m-d\TH:i', strtotime($start_date)) : '';
        $end_date_formatted = $end_date ? date('Y-m-d\TH:i', strtotime($end_date)) : '';
        ?>
        <div class="ai-calendar-meta-box">
            <p>
                <label for="event_start_date">
                    <?php _e('Start Date & Time:', 'ai-calendar'); ?>
                </label>
                <input type="datetime-local" 
                    id="event_start_date" 
                    name="event_start_date" 
                    value="<?php echo esc_attr($start_date_formatted); ?>" 
                    required>
            </p>

            <p>
                <label for="event_end_date">
                    <?php _e('End Date & Time:', 'ai-calendar'); ?>
                </label>
                <input type="datetime-local" 
                    id="event_end_date" 
                    name="event_end_date" 
                    value="<?php echo esc_attr($end_date_formatted); ?>" 
                    required>
            </p>

            <p>
                <label for="event_location">
                    <?php _e('Location:', 'ai-calendar'); ?>
                </label>
                <input type="text" 
                    id="event_location" 
                    name="event_location" 
                    value="<?php echo esc_attr($location); ?>" 
                    class="widefat">
            </p>
        </div>

        <style>
            .ai-calendar-meta-box label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .ai-calendar-meta-box input[type="datetime-local"] {
                width: 100%;
                margin-bottom: 15px;
            }
        </style>
        <?php
    }

    public function save_event_meta($post_id) {
        // Check nonce
        if (!isset($_POST['ai_calendar_event_nonce']) || 
            !wp_verify_nonce($_POST['ai_calendar_event_nonce'], 'ai_calendar_event_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save start date and time
        if (isset($_POST['event_start_date'])) {
            $start_datetime = sanitize_text_field($_POST['event_start_date']);
            if ($start_datetime) {
                $datetime = new \DateTime($start_datetime);
                $mysql_start_date = $datetime->format('Y-m-d');
                $start_time = $datetime->format('H:i');
                
                update_post_meta($post_id, '_event_start_date', $mysql_start_date);
                update_post_meta($post_id, '_event_start_time', $start_time);
            }
        }

        // Save end date and time
        if (isset($_POST['event_end_date'])) {
            $end_datetime = sanitize_text_field($_POST['event_end_date']);
            if ($end_datetime) {
                $datetime = new \DateTime($end_datetime);
                $mysql_end_date = $datetime->format('Y-m-d');
                $end_time = $datetime->format('H:i');
                
                update_post_meta($post_id, '_event_end_date', $mysql_end_date);
                update_post_meta($post_id, '_event_end_time', $end_time);
            }
        }

        // Save location
        if (isset($_POST['event_location'])) {
            update_post_meta($post_id, '_event_location', 
                sanitize_text_field($_POST['event_location'])
            );
        }
    }
} 