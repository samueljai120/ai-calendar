<?php
namespace AiCalendar\Admin;

class Dashboard {
    public function __construct() {
        // Remove the admin_menu hook to prevent duplicate menu entries
        // add_action('admin_menu', [$this, 'add_menu_pages']);
        
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('init', [$this, 'register_event_post_type']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add debug action to trace theme settings on each page load
        add_action('admin_init', [$this, 'debug_theme_settings']);
        
        // Add handler for manual theme settings saving
        add_action('admin_init', [$this, 'handle_manual_theme_save']);
    }

    public function register_settings() {
        // Register calendar theme settings
        register_setting(
            'ai_calendar_settings',
            'ai_calendar_theme_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_theme_settings'],
                'default' => [
                    'enable_theme' => true,
                    'theme' => 'modern',
                    'colors' => [
                        'primary' => '#3182ce',
                        'secondary' => '#4a5568',
                        'background' => '#ffffff',
                        'text' => '#2d3748'
                    ]
                ]
            ]
        );

        // Register event page settings
        register_setting(
            'ai_calendar_event_settings_group',
            'ai_calendar_event_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_event_settings'],
                'default' => [
                    'template' => 'default'
                ]
            ]
        );

        // Add settings section
        add_settings_section(
            'ai_calendar_theme_section',
            __('Theme Settings', 'ai-calendar'),
            [$this, 'theme_section_callback'],
            'ai-calendar-settings'
        );
    }

    public function theme_section_callback() {
        echo '<p>' . __('Customize the appearance of your calendar.', 'ai-calendar') . '</p>';
    }

    public function sanitize_theme_settings($input) {
        $sanitized = [];
        
        // Enable theme
        $sanitized['enable_theme'] = isset($input['enable_theme']) ? true : false;
        
        // Theme
        $sanitized['theme'] = sanitize_text_field($input['theme'] ?? 'modern');
        
        // Colors
        $sanitized['colors'] = [];
        $default_colors = [
            'primary' => '#3182ce',
            'secondary' => '#4a5568',
            'background' => '#ffffff',
            'text' => '#2d3748'
        ];
        
        foreach ($default_colors as $key => $default) {
            $sanitized['colors'][$key] = isset($input['colors'][$key]) ? sanitize_hex_color($input['colors'][$key]) : $default;
        }

        // For debugging
        error_log('Sanitized theme settings: ' . print_r($sanitized, true));
        
        return $sanitized;
    }

    public function sanitize_event_settings($input) {
        $sanitized = [];
        $sanitized['template'] = sanitize_text_field($input['template'] ?? 'default');
        return $sanitized;
    }

    public function register_event_post_type() {
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
            'not_found_in_trash'   => __('No events found in Trash.', 'ai-calendar'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
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

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ai-calendar') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_style(
            'ai-calendar-admin',
            plugins_url('/assets/css/admin.css', dirname(dirname(__FILE__))),
            [],
            AI_CALENDAR_VERSION
        );
        
        wp_enqueue_script(
            'ai-calendar-admin',
            plugins_url('/assets/js/admin.js', dirname(dirname(__FILE__))),
            ['jquery', 'wp-color-picker'],
            AI_CALENDAR_VERSION,
            true
        );
    }

    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            __('AI Calendar', 'ai-calendar'),
            __('AI Calendar', 'ai-calendar'),
            'manage_options',
            'ai-calendar-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-calendar-alt',
            5
        );

        // Dashboard submenu
        add_submenu_page(
            'ai-calendar-dashboard',
            __('Dashboard', 'ai-calendar'),
            __('Dashboard', 'ai-calendar'),
            'manage_options',
            'ai-calendar-dashboard',
            [$this, 'render_dashboard']
        );

        // All Events submenu
        add_submenu_page(
            'ai-calendar-dashboard',
            __('All Events', 'ai-calendar'),
            __('All Events', 'ai-calendar'),
            'manage_options',
            'edit.php?post_type=ai_calendar_event',
            null
        );

        // Add New Event submenu
        add_submenu_page(
            'ai-calendar-dashboard',
            __('Add New Event', 'ai-calendar'),
            __('Add New Event', 'ai-calendar'),
            'manage_options',
            'post-new.php?post_type=ai_calendar_event',
            null
        );

        // Calendar Settings submenu
        add_submenu_page(
            'ai-calendar-dashboard',
            __('Settings', 'ai-calendar'),
            __('Settings', 'ai-calendar'),
            'manage_options',
            'ai-calendar-settings',
            [$this, 'render_settings']
        );

        // Instructions submenu
        add_submenu_page(
            'ai-calendar-dashboard',
            __('Instructions', 'ai-calendar'),
            __('Instructions', 'ai-calendar'),
            'manage_options',
            'ai-calendar-instructions',
            [$this, 'render_instructions']
        );
    }

    public function render_dashboard() {
        ?>
        <div class="wrap ai-calendar-wrap">
            <div class="ai-calendar-header">
                <h1><?php _e('AI Calendar Dashboard', 'ai-calendar'); ?></h1>
                <div class="header-actions">
                    <a href="<?php echo admin_url('post-new.php?post_type=ai_calendar_event'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Add New Event', 'ai-calendar'); ?>
                    </a>
                </div>
            </div>
            
            <div class="ai-calendar-dashboard">
                <div class="dashboard-grid">
                    <!-- Quick Stats -->
                    <div class="dashboard-card">
                        <h2><span class="dashicons dashicons-chart-bar"></span> <?php _e('Quick Stats', 'ai-calendar'); ?></h2>
                        <?php
                        $total_events = wp_count_posts('ai_calendar_event');
                        $upcoming_events = get_posts([
                            'post_type' => 'ai_calendar_event',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_event_start_date',
                                    'value' => current_time('mysql'),
                                    'compare' => '>=',
                                    'type' => 'DATETIME'
                                ]
                            ]
                        ]);
                        ?>
                        <ul class="stats-list">
                            <li>
                                <span class="stat-label"><?php _e('Total Events', 'ai-calendar'); ?></span>
                                <span class="stat-value"><?php echo $total_events->publish; ?></span>
                            </li>
                            <li>
                                <span class="stat-label"><?php _e('Upcoming Events', 'ai-calendar'); ?></span>
                                <span class="stat-value"><?php echo count($upcoming_events); ?></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Quick Start -->
                    <div class="dashboard-card">
                        <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php _e('Quick Start', 'ai-calendar'); ?></h2>
                        <p><?php _e('Display the calendar on any page using the shortcode:', 'ai-calendar'); ?></p>
                        <code class="shortcode-display">[ai_calendar]</code>
                        <button class="copy-shortcode button" data-shortcode="[ai_calendar]">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Copy Shortcode', 'ai-calendar'); ?>
                        </button>
                    </div>

                    <!-- Recent Events -->
                    <div class="dashboard-card">
                        <h2><span class="dashicons dashicons-calendar"></span> <?php _e('Recent Events', 'ai-calendar'); ?></h2>
                        <?php
                        $events = get_posts([
                            'post_type' => 'ai_calendar_event',
                            'posts_per_page' => 5,
                            'orderby' => 'meta_value',
                            'meta_key' => '_event_start_date',
                            'order' => 'DESC'
                        ]);

                        if ($events): ?>
                            <div class="recent-events-list">
                                <?php foreach ($events as $event): 
                                    $start_date = get_post_meta($event->ID, '_event_start_date', true);
                                    $location = get_post_meta($event->ID, '_event_location', true);
                                ?>
                                    <div class="event-item">
                                        <?php if (has_post_thumbnail($event->ID)): ?>
                                            <div class="event-thumbnail">
                                                <?php echo get_the_post_thumbnail($event->ID, 'thumbnail'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="event-details">
                                            <h3><a href="<?php echo get_edit_post_link($event->ID); ?>"><?php echo esc_html($event->post_title); ?></a></h3>
                                            <div class="event-meta">
                                                <span class="event-date">
                                                    <span class="dashicons dashicons-calendar-alt"></span>
                                                    <?php echo date_i18n(get_option('date_format'), strtotime($start_date)); ?>
                                                </span>
                                                <?php if ($location): ?>
                                                    <span class="event-location">
                                                        <span class="dashicons dashicons-location"></span>
                                                        <?php echo esc_html($location); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-events"><?php _e('No events found.', 'ai-calendar'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Links -->
                    <div class="dashboard-card">
                        <h2><span class="dashicons dashicons-admin-links"></span> <?php _e('Quick Links', 'ai-calendar'); ?></h2>
                        <div class="quick-links-grid">
                            <a href="<?php echo admin_url('post-new.php?post_type=ai_calendar_event'); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <span class="link-text"><?php _e('Add New Event', 'ai-calendar'); ?></span>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=ai_calendar_event'); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-list-view"></span>
                                <span class="link-text"><?php _e('Manage Events', 'ai-calendar'); ?></span>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=ai-calendar-settings'); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <span class="link-text"><?php _e('Calendar Settings', 'ai-calendar'); ?></span>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=ai-calendar-event-settings'); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span class="link-text"><?php _e('Event Page Settings', 'ai-calendar'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.copy-shortcode').on('click', function() {
                const shortcode = $(this).data('shortcode');
                navigator.clipboard.writeText(shortcode).then(() => {
                    const $button = $(this);
                    $button.addClass('copied');
                    setTimeout(() => $button.removeClass('copied'), 2000);
                });
            });
        });
        </script>
        <?php
    }

    public function render_settings() {
        // Force the active tab to be 'event', hiding the calendar theme tab
        $active_tab = 'event';
        ?>
        <div class="wrap ai-calendar-wrap">
            <div class="ai-calendar-header">
                <h1><?php _e('AI Calendar Settings', 'ai-calendar'); ?></h1>
            </div>
            
            <?php
            // Display settings updated message
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'ai-calendar') . '</p></div>';
            }
            
            // Display error message if there was a problem
            if (isset($_GET['settings-error']) && $_GET['settings-error'] === 'true') {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('There was a problem saving your settings.', 'ai-calendar') . '</p></div>';
            }
            ?>
            
            <!-- Remove tabs, only show event settings -->
            <div class="ai-calendar-settings-content">
                <?php $this->render_event_settings(); ?>
            </div>
        </div>
        <?php
    }

    public function render_event_settings() {
        ?>
        <div class="settings-container">
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_calendar_event_settings_group'); // Updated group name
                
                $event_settings = get_option('ai_calendar_event_settings', []);
                $current_template = isset($event_settings['template']) ? $event_settings['template'] : 'default';
                ?>
                
                <div class="event-settings-container">
                    <h2><?php _e('Event Page Template', 'ai-calendar'); ?></h2>
                    
                    <!-- Template Selection -->
                    <div class="template-section">
                        <h3><?php _e('Select Template', 'ai-calendar'); ?></h3>
                        <div class="template-options">
                            <div class="template-option<?php echo $current_template === 'default' ? ' active' : ''; ?>">
                                <h4>Default</h4>
                                <p>Standard event layout with featured image and details</p>
                                <input type="radio" name="ai_calendar_event_settings[template]" value="default" 
                                    <?php checked($current_template, 'default'); ?>>
                            </div>
                            <div class="template-option<?php echo $current_template === 'modern' ? ' active' : ''; ?>">
                                <h4>Modern</h4>
                                <p>Contemporary design with full-width images and clean typography</p>
                                <input type="radio" name="ai_calendar_event_settings[template]" value="modern" 
                                    <?php checked($current_template, 'modern'); ?>>
                            </div>
                        </div>
                    </div>
                    
                    <?php submit_button(); ?>
                </div>
            </form>
        </div>
        <?php
    }

    public function render_instructions() {
        ?>
        <div class="wrap ai-calendar-wrap">
            <div class="ai-calendar-header">
                <h1><?php _e('AI Calendar Instructions', 'ai-calendar'); ?></h1>
            </div>
            
            <div class="ai-calendar-instructions">
                <div class="instruction-grid">
                    <!-- Getting Started -->
                    <div class="instruction-card">
                        <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php _e('Getting Started', 'ai-calendar'); ?></h2>
                        <ol class="instruction-steps">
                            <li><?php _e('Add the calendar to any page using the shortcode [ai_calendar]', 'ai-calendar'); ?></li>
                            <li><?php _e('Create events from the Events menu', 'ai-calendar'); ?></li>
                            <li><?php _e('Customize the calendar appearance in Calendar Settings', 'ai-calendar'); ?></li>
                            <li><?php _e('Configure event page layout in Event Page Settings', 'ai-calendar'); ?></li>
                        </ol>
                    </div>

                    <!-- Creating Events -->
                    <div class="instruction-card">
                        <h2><span class="dashicons dashicons-plus-alt"></span> <?php _e('Creating Events', 'ai-calendar'); ?></h2>
                        <ol class="instruction-steps">
                            <li><?php _e('Go to AI Calendar → Add New Event', 'ai-calendar'); ?></li>
                            <li><?php _e('Enter the event title and description', 'ai-calendar'); ?></li>
                            <li><?php _e('Set the event start and end dates', 'ai-calendar'); ?></li>
                            <li><?php _e('Add the event location and other details', 'ai-calendar'); ?></li>
                            <li><?php _e('Upload a featured image for the event', 'ai-calendar'); ?></li>
                            <li><?php _e('Publish the event', 'ai-calendar'); ?></li>
                        </ol>
                    </div>

                    <!-- Calendar Customization -->
                    <div class="instruction-card">
                        <h2><span class="dashicons dashicons-admin-appearance"></span> <?php _e('Calendar Customization', 'ai-calendar'); ?></h2>
                        <ol class="instruction-steps">
                            <li><?php _e('Go to AI Calendar → Calendar Settings', 'ai-calendar'); ?></li>
                            <li><?php _e('Choose a calendar theme and layout', 'ai-calendar'); ?></li>
                            <li><?php _e('Customize colors and styles', 'ai-calendar'); ?></li>
                            <li><?php _e('Configure display options', 'ai-calendar'); ?></li>
                            <li><?php _e('Save your changes', 'ai-calendar'); ?></li>
                        </ol>
                    </div>

                    <!-- Event Page Customization -->
                    <div class="instruction-card">
                        <h2><span class="dashicons dashicons-admin-page"></span> <?php _e('Event Page Customization', 'ai-calendar'); ?></h2>
                        <ol class="instruction-steps">
                            <li><?php _e('Go to AI Calendar → Event Page Settings', 'ai-calendar'); ?></li>
                            <li><?php _e('Select an event page template', 'ai-calendar'); ?></li>
                            <li><?php _e('Configure layout options', 'ai-calendar'); ?></li>
                            <li><?php _e('Customize colors and typography', 'ai-calendar'); ?></li>
                            <li><?php _e('Save your changes', 'ai-calendar'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Debug function to trace theme settings
     */
    public function debug_theme_settings() {
        if (isset($_GET['page']) && $_GET['page'] === 'ai-calendar-settings') {
            $theme_settings = get_option('ai_calendar_theme_settings');
            error_log('Current AI Calendar Theme Settings: ' . print_r($theme_settings, true));
        }
    }

    /**
     * Handle manual theme settings saving as a backup approach
     */
    public function handle_manual_theme_save() {
        if (isset($_POST['ai_calendar_theme_settings']) && isset($_POST['submit'])) {
            if (
                isset($_POST['ai_calendar_theme_settings_nonce']) && 
                wp_verify_nonce($_POST['ai_calendar_theme_settings_nonce'], 'ai_calendar_theme_settings_update')
            ) {
                $settings = $_POST['ai_calendar_theme_settings'];
                
                // Sanitize settings
                $sanitized = $this->sanitize_theme_settings($settings);
                
                // Save settings
                update_option('ai_calendar_theme_settings', $sanitized);
                
                // Log the settings that were saved
                error_log('AI Calendar: Theme settings saved manually: ' . print_r($sanitized, true));
                
                // Redirect to prevent form resubmission
                wp_redirect(add_query_arg(['page' => 'ai-calendar-settings', 'tab' => 'calendar', 'settings-updated' => 'true'], admin_url('admin.php')));
                exit;
            }
        }
    }
} 