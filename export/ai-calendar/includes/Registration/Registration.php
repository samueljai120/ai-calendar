<?php
namespace AiCalendar\Registration;

class Registration {
    /**
     * Initialize the registration system
     */
    public function __construct() {
        add_action('wp_ajax_ai_calendar_register', [$this, 'handle_registration']);
        add_action('wp_ajax_nopriv_ai_calendar_register', [$this, 'handle_registration']);
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_ai_calendar_registration', [$this, 'save_registration_meta']);
    }

    /**
     * Register the registration post type
     */
    public function register_post_type() {
        register_post_type('ai_calendar_registration', [
            'labels' => [
                'name' => __('Registrations', 'ai-calendar'),
                'singular_name' => __('Registration', 'ai-calendar'),
                'menu_name' => __('Registrations', 'ai-calendar'),
                'all_items' => __('All Registrations', 'ai-calendar'),
                'view_item' => __('View Registration', 'ai-calendar'),
                'add_new_item' => __('Add New Registration', 'ai-calendar'),
                'add_new' => __('Add New', 'ai-calendar'),
                'edit_item' => __('Edit Registration', 'ai-calendar'),
                'update_item' => __('Update Registration', 'ai-calendar'),
                'search_items' => __('Search Registrations', 'ai-calendar'),
                'not_found' => __('No registrations found', 'ai-calendar'),
                'not_found_in_trash' => __('No registrations found in Trash', 'ai-calendar'),
            ],
            'supports' => ['title'],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-clipboard',
            'hierarchical' => false,
            'has_archive' => false,
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    /**
     * Add meta boxes for registration details
     */
    public function add_meta_boxes() {
        add_meta_box(
            'registration_details',
            __('Registration Details', 'ai-calendar'),
            [$this, 'render_details_meta_box'],
            'ai_calendar_registration',
            'normal',
            'high'
        );
    }

    /**
     * Render the registration details meta box
     */
    public function render_details_meta_box($post) {
        wp_nonce_field('save_registration_meta', 'registration_meta_nonce');

        $meta = get_post_meta($post->ID);
        $event_id = get_post_meta($post->ID, '_event_id', true);
        $status = get_post_meta($post->ID, '_status', true) ?: 'pending';
        ?>
        <div class="registration-meta">
            <p>
                <label><strong><?php esc_html_e('Event', 'ai-calendar'); ?></strong></label><br>
                <?php if ($event_id) : ?>
                    <a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>">
                        <?php echo esc_html(get_the_title($event_id)); ?>
                    </a>
                <?php endif; ?>
            </p>

            <p>
                <label><strong><?php esc_html_e('Status', 'ai-calendar'); ?></strong></label><br>
                <select name="registration_status">
                    <option value="pending" <?php selected($status, 'pending'); ?>>
                        <?php esc_html_e('Pending', 'ai-calendar'); ?>
                    </option>
                    <option value="confirmed" <?php selected($status, 'confirmed'); ?>>
                        <?php esc_html_e('Confirmed', 'ai-calendar'); ?>
                    </option>
                    <option value="cancelled" <?php selected($status, 'cancelled'); ?>>
                        <?php esc_html_e('Cancelled', 'ai-calendar'); ?>
                    </option>
                </select>
            </p>

            <div class="registration-details">
                <h3><?php esc_html_e('Attendee Information', 'ai-calendar'); ?></h3>
                
                <p>
                    <label><strong><?php esc_html_e('Name', 'ai-calendar'); ?></strong></label><br>
                    <?php echo esc_html($meta['_first_name'][0] ?? ''); ?> <?php echo esc_html($meta['_last_name'][0] ?? ''); ?>
                </p>

                <p>
                    <label><strong><?php esc_html_e('Email', 'ai-calendar'); ?></strong></label><br>
                    <a href="mailto:<?php echo esc_attr($meta['_email'][0] ?? ''); ?>">
                        <?php echo esc_html($meta['_email'][0] ?? ''); ?>
                    </a>
                </p>

                <?php if (!empty($meta['_phone'][0])) : ?>
                <p>
                    <label><strong><?php esc_html_e('Phone', 'ai-calendar'); ?></strong></label><br>
                    <?php echo esc_html($meta['_phone'][0]); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($meta['_ticket_quantity'][0])) : ?>
                <p>
                    <label><strong><?php esc_html_e('Tickets', 'ai-calendar'); ?></strong></label><br>
                    <?php echo esc_html($meta['_ticket_quantity'][0]); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($meta['_dietary_requirements'][0])) : ?>
                <p>
                    <label><strong><?php esc_html_e('Dietary Requirements', 'ai-calendar'); ?></strong></label><br>
                    <?php echo esc_html($meta['_dietary_requirements'][0]); ?>
                    <?php if (!empty($meta['_dietary_other'][0])) : ?>
                        <br><?php echo esc_html($meta['_dietary_other'][0]); ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($meta['_special_requirements'][0])) : ?>
                <p>
                    <label><strong><?php esc_html_e('Special Requirements', 'ai-calendar'); ?></strong></label><br>
                    <?php echo esc_html($meta['_special_requirements'][0]); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save registration meta data
     */
    public function save_registration_meta($post_id) {
        if (!isset($_POST['registration_meta_nonce']) || 
            !wp_verify_nonce($_POST['registration_meta_nonce'], 'save_registration_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Update status
        if (isset($_POST['registration_status'])) {
            update_post_meta($post_id, '_status', sanitize_text_field($_POST['registration_status']));
        }
    }

    /**
     * Handle registration form submission
     */
    public function handle_registration() {
        check_ajax_referer('ai_calendar_event_registration', 'registration_nonce');

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error(['message' => __('Invalid event ID', 'ai-calendar')]);
        }

        // Validate capacity
        $capacity = get_post_meta($event_id, '_event_capacity', true);
        $current_attendees = get_post_meta($event_id, '_event_current_attendees', true) ?: 0;
        $ticket_quantity = isset($_POST['ticket_quantity']) ? intval($_POST['ticket_quantity']) : 1;

        if ($capacity && ($current_attendees + $ticket_quantity) > $capacity) {
            wp_send_json_error(['message' => __('Sorry, this event is full', 'ai-calendar')]);
        }

        // Create registration
        $registration_id = wp_insert_post([
            'post_type' => 'ai_calendar_registration',
            'post_title' => sprintf(
                __('Registration for %s', 'ai-calendar'),
                get_the_title($event_id)
            ),
            'post_status' => 'publish',
        ]);

        if (is_wp_error($registration_id)) {
            wp_send_json_error(['message' => __('Failed to create registration', 'ai-calendar')]);
        }

        // Save registration meta
        $meta_fields = [
            '_event_id' => $event_id,
            '_status' => 'pending',
            '_first_name' => 'first_name',
            '_last_name' => 'last_name',
            '_email' => 'email',
            '_phone' => 'phone',
            '_ticket_quantity' => 'ticket_quantity',
            '_dietary_requirements' => 'dietary_requirements',
            '_dietary_other' => 'dietary_other',
            '_special_requirements' => 'special_requirements',
        ];

        foreach ($meta_fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta(
                    $registration_id,
                    $meta_key,
                    sanitize_text_field($_POST[$post_key])
                );
            }
        }

        // Update event attendee count
        update_post_meta($event_id, '_event_current_attendees', $current_attendees + $ticket_quantity);

        // Send confirmation email
        $this->send_confirmation_email($registration_id);

        // Send notification to admin
        $this->send_admin_notification($registration_id);

        $remaining = $capacity ? ($capacity - ($current_attendees + $ticket_quantity)) : null;

        wp_send_json_success([
            'message' => __('Registration successful! Check your email for confirmation details.', 'ai-calendar'),
            'remaining' => $remaining,
        ]);
    }

    /**
     * Send confirmation email to attendee
     */
    private function send_confirmation_email($registration_id) {
        $registration = get_post($registration_id);
        $event_id = get_post_meta($registration_id, '_event_id', true);
        $first_name = get_post_meta($registration_id, '_first_name', true);
        $email = get_post_meta($registration_id, '_email', true);

        $subject = sprintf(
            __('Registration Confirmation - %s', 'ai-calendar'),
            get_the_title($event_id)
        );

        $message = sprintf(
            __('Dear %s,

Thank you for registering for %s.

Event Details:
Date: %s
Time: %s
Location: %s

Your registration is currently pending confirmation. We will notify you once it has been confirmed.

If you have any questions, please don\'t hesitate to contact us.

Best regards,
%s', 'ai-calendar'),
            esc_html($first_name),
            esc_html(get_the_title($event_id)),
            esc_html(get_post_meta($event_id, '_event_start_date', true)),
            esc_html(get_post_meta($event_id, '_event_start_time', true)),
            esc_html(get_post_meta($event_id, '_event_location', true)),
            esc_html(get_bloginfo('name'))
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        
        wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Send notification email to admin
     */
    private function send_admin_notification($registration_id) {
        $registration = get_post($registration_id);
        $event_id = get_post_meta($registration_id, '_event_id', true);
        $admin_email = get_option('admin_email');

        $subject = sprintf(
            __('New Registration - %s', 'ai-calendar'),
            get_the_title($event_id)
        );

        $message = sprintf(
            __('A new registration has been submitted for %s.

Registration Details:
Name: %s %s
Email: %s
Phone: %s

View registration: %s', 'ai-calendar'),
            esc_html(get_the_title($event_id)),
            esc_html(get_post_meta($registration_id, '_first_name', true)),
            esc_html(get_post_meta($registration_id, '_last_name', true)),
            esc_html(get_post_meta($registration_id, '_email', true)),
            esc_html(get_post_meta($registration_id, '_phone', true)),
            esc_url(get_edit_post_link($registration_id))
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
} 