<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-calendar-dashboard">
    <h1><?php _e('AI Calendar Dashboard', 'ai-calendar'); ?></h1>
    
    <div class="dashboard-grid">
        <!-- Quick Stats -->
        <div class="dashboard-card">
            <h2><?php _e('Quick Stats', 'ai-calendar'); ?></h2>
            <?php
            $total_events = wp_count_posts('ai_calendar_event');
            $upcoming_events = new WP_Query([
                'post_type' => 'ai_calendar_event',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_event_start_date',
                        'value' => current_time('Y-m-d'),
                        'compare' => '>=',
                        'type' => 'DATE'
                    ]
                ]
            ]);
            ?>
            <ul>
                <li><?php printf(__('Total Events: %d', 'ai-calendar'), $total_events->publish); ?></li>
                <li><?php printf(__('Upcoming Events: %d', 'ai-calendar'), $upcoming_events->post_count); ?></li>
            </ul>
        </div>

        <!-- Quick Links -->
        <div class="dashboard-card">
            <h2><?php _e('Quick Links', 'ai-calendar'); ?></h2>
            <ul>
                <li><a href="<?php echo admin_url('post-new.php?post_type=ai_calendar_event'); ?>"><?php _e('Add New Event', 'ai-calendar'); ?></a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=ai_calendar_event'); ?>"><?php _e('Manage Events', 'ai-calendar'); ?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=ai-calendar-theme-settings'); ?>"><?php _e('Calendar Theme Settings', 'ai-calendar'); ?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=ai-calendar-event-settings'); ?>"><?php _e('Event Page Settings', 'ai-calendar'); ?></a></li>
            </ul>
        </div>

        <!-- Recent Events -->
        <div class="dashboard-card full-width">
            <h2><?php _e('Recent Events', 'ai-calendar'); ?></h2>
            <?php
            $recent_events = new WP_Query([
                'post_type' => 'ai_calendar_event',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            if ($recent_events->have_posts()) :
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th>' . __('Event', 'ai-calendar') . '</th>';
                echo '<th>' . __('Date', 'ai-calendar') . '</th>';
                echo '<th>' . __('Location', 'ai-calendar') . '</th>';
                echo '</tr></thead><tbody>';
                
                while ($recent_events->have_posts()) : $recent_events->the_post();
                    $start_date = get_post_meta(get_the_ID(), '_event_start_date', true);
                    $location = get_post_meta(get_the_ID(), '_event_location', true);
                    echo '<tr>';
                    echo '<td><a href="' . get_edit_post_link() . '">' . get_the_title() . '</a></td>';
                    echo '<td>' . ($start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '-') . '</td>';
                    echo '<td>' . ($location ? esc_html($location) : '-') . '</td>';
                    echo '</tr>';
                endwhile;
                
                echo '</tbody></table>';
            else:
                echo '<p>' . __('No events found.', 'ai-calendar') . '</p>';
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.dashboard-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.dashboard-card.full-width {
    grid-column: 1 / -1;
}

.dashboard-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.dashboard-card ul {
    margin: 0;
}

.dashboard-card ul li {
    margin-bottom: 10px;
}

.dashboard-card a {
    text-decoration: none;
}

.dashboard-card table {
    margin-top: 10px;
}
</style> 