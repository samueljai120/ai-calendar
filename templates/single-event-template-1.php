<?php
if (!defined('ABSPATH')) exit;

global $post, $ai_calendar_display_options;

// Set default display options if not set
if (!is_array($ai_calendar_display_options)) {
    $ai_calendar_display_options = array(
        'show_featured_image' => true,
        'show_date' => true,
        'show_time' => true,
        'show_location' => true,
        'show_description' => true,
        'show_map' => true,
        'show_related_events' => true
    );
}

// Get event meta
$start_date = get_post_meta($post->ID, '_event_start_date', true);
$end_date = get_post_meta($post->ID, '_event_end_date', true);
$start_time = get_post_meta($post->ID, '_event_start_time', true);
$end_time = get_post_meta($post->ID, '_event_end_time', true);
$location = get_post_meta($post->ID, '_event_location', true);
$event_type = get_post_meta($post->ID, '_event_type', true);

get_header();
?>

<div class="ai-calendar-event-page-wrapper">
    <article class="event-single template-1">
        <?php if (!empty($ai_calendar_display_options['show_featured_image']) && has_post_thumbnail()): ?>
            <div class="featured-image-banner">
                <?php 
                $image_url = get_the_post_thumbnail_url($post->ID, 'full');
                ?>
                <div class="featured-image-container" style="background-image: url('<?php echo esc_url($image_url); ?>')">
                    <div class="event-title-overlay">
                        <div class="container">
                            <h1><?php the_title(); ?></h1>
                            <?php if (!empty($ai_calendar_display_options['show_date'])): ?>
                                <div class="event-date">
                                    <?php if ($start_date): ?>
                                        <span class="event-date-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                        </span>
                                        <?php
                                        if ($start_date === $end_date || !$end_date) {
                                            echo date_i18n(get_option('date_format'), strtotime($start_date));
                                        } else {
                                            echo sprintf(
                                                __('%s - %s', 'ai-calendar'),
                                                date_i18n(get_option('date_format'), strtotime($start_date)),
                                                date_i18n(get_option('date_format'), strtotime($end_date))
                                            );
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="event-content-wrapper">
            <div class="event-content-container">
                <?php if (empty($ai_calendar_display_options['show_featured_image']) || !has_post_thumbnail()): ?>
                    <h1 class="event-title"><?php the_title(); ?></h1>
                <?php endif; ?>

                <div class="event-meta-details">
                    <?php if (!empty($ai_calendar_display_options['show_date']) && $start_date): ?>
                        <div class="meta-item">
                            <span class="meta-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </span>
                            <div class="meta-content">
                                <span class="meta-label"><?php _e('Date', 'ai-calendar'); ?></span>
                                <span class="meta-value">
                                    <?php
                                    if ($start_date === $end_date || !$end_date) {
                                        echo date_i18n(get_option('date_format'), strtotime($start_date));
                                    } else {
                                        echo sprintf(
                                            __('%s - %s', 'ai-calendar'),
                                            date_i18n(get_option('date_format'), strtotime($start_date)),
                                            date_i18n(get_option('date_format'), strtotime($end_date))
                                        );
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($ai_calendar_display_options['show_time']) && ($start_time || $end_time)): ?>
                        <div class="meta-item">
                            <span class="meta-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </span>
                            <div class="meta-content">
                                <span class="meta-label"><?php _e('Time', 'ai-calendar'); ?></span>
                                <span class="meta-value">
                                    <?php
                                    if ($start_time && $end_time) {
                                        echo sprintf(
                                            __('%s - %s', 'ai-calendar'),
                                            date_i18n(get_option('time_format'), strtotime($start_time)),
                                            date_i18n(get_option('time_format'), strtotime($end_time))
                                        );
                                    } elseif ($start_time) {
                                        echo date_i18n(get_option('time_format'), strtotime($start_time));
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($ai_calendar_display_options['show_location']) && $location): ?>
                        <div class="meta-item">
                            <span class="meta-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </span>
                            <div class="meta-content">
                                <span class="meta-label">
                                    <?php 
                                    if ($event_type === 'virtual') {
                                        _e('Virtual Location', 'ai-calendar');
                                    } elseif ($event_type === 'hybrid') {
                                        _e('Location (Hybrid)', 'ai-calendar');
                                    } else {
                                        _e('Location', 'ai-calendar');
                                    }
                                    ?>
                                </span>
                                <span class="meta-value"><?php echo esc_html($location); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($ai_calendar_display_options['show_description'])): ?>
                    <div class="event-description">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

                <!-- Event Actions (Export & Share) -->
                <div class="event-actions-wrapper">
                    <?php
                    // Get event data for sharing
                    $event_id = get_the_ID();
                    $event_title = get_the_title();
                    $event_url = get_permalink();
                    $start_date = get_post_meta($event_id, '_event_start_date', true);
                    $end_date = get_post_meta($event_id, '_event_end_date', true);
                    $location = get_post_meta($event_id, '_event_location', true);

                    // Get the correct template path
                    $template_path = __DIR__ . '/parts/event-actions.php';
                    
                    if (WP_DEBUG) {
                        error_log('AI Calendar: Loading event actions template from: ' . $template_path);
                    }

                    // Include event actions template
                    if (file_exists($template_path)) {
                        include $template_path;
                    } else {
                        error_log('AI Calendar: Event actions template not found at: ' . $template_path);
                        echo '<!-- Event actions template not found at: ' . esc_html($template_path) . ' -->';
                    }
                    ?>
                </div>

                <?php if (!empty($ai_calendar_display_options['show_map']) && $location && $event_type !== 'virtual'): ?>
                    <div class="event-map">
                        <h3><?php _e('Location Map', 'ai-calendar'); ?></h3>
                        <div class="map-container">
                            <?php
                            $map_url = 'https://maps.google.com/maps?q=' . urlencode($location) . '&output=embed';
                            echo '<iframe width="100%" height="400" frameborder="0" style="border:0; border-radius: 8px;" src="' . esc_url($map_url) . '" allowfullscreen></iframe>';
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($ai_calendar_display_options['show_related_events'] ?? true): ?>
                    <div class="related-events">
                        <h3><?php _e('Upcoming Events', 'ai-calendar'); ?></h3>
                        <div class="upcoming-events-grid">
                            <?php
                            $upcoming_events = new WP_Query([
                                'post_type' => 'ai_calendar_event',
                                'posts_per_page' => 8,
                                'post__not_in' => [get_the_ID()],
                                'meta_key' => '_event_start_date',
                                'orderby' => 'meta_value',
                                'order' => 'ASC',
                                'meta_query' => [
                                    [
                                        'key' => '_event_start_date',
                                        'value' => date('Y-m-d'),
                                        'compare' => '>=',
                                        'type' => 'DATE'
                                    ]
                                ]
                            ]);

                            if ($upcoming_events->have_posts()) :
                                while ($upcoming_events->have_posts()) : $upcoming_events->the_post();
                                    $start_date = get_post_meta(get_the_ID(), '_event_start_date', true);
                                    $location = get_post_meta(get_the_ID(), '_event_location', true);
                                    ?>
                                    <div class="upcoming-event-card">
                                        <div class="upcoming-event-image">
                                            <?php if (has_post_thumbnail()): ?>
                                                <?php the_post_thumbnail('medium'); ?>
                                            <?php else: ?>
                                                <div class="no-image"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upcoming-event-content">
                                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                            <div class="upcoming-event-meta">
                                                <?php if ($start_date): ?>
                                                    <div class="upcoming-event-date">
                                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                                            <polyline points="12 6 12 12 16 14" fill="none" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        <?php echo date_i18n(get_option('date_format'), strtotime($start_date)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($location): ?>
                                                    <div class="upcoming-event-location">
                                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" fill="none" stroke="currentColor" stroke-width="2"/>
                                                            <circle cx="12" cy="10" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        <?php echo esc_html(wp_trim_words($location, 3)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile;
                                wp_reset_postdata();
                            else: ?>
                                <div class="no-upcoming-events">
                                    <p><?php _e('No upcoming events found.', 'ai-calendar'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
</div>

<style>
.ai-calendar-event-page-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
}

.event-single.template-1 {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    line-height: 1.6;
    color: #333;
    width: 100%;
    max-width: 100%;
}

.featured-image-banner {
    width: 100%;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}

.featured-image-container {
    height: 400px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

.event-title-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
    padding: 2rem 0;
    color: #fff;
}

.event-title-overlay .container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.event-title-overlay h1 {
    margin: 0 0 0.5rem;
    font-size: 2.5rem;
    line-height: 1.2;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.event-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
}

.event-date-icon svg {
    vertical-align: middle;
}

/* Content wrapper for consistent centering */
.event-content-wrapper {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.event-content-container {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.05);
    margin-bottom: 3rem;
}

.event-title {
    font-size: 2.2rem;
    margin: 0 0 1.5rem;
    color: #333;
    line-height: 1.2;
}

.event-meta-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.meta-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1 0 250px;
}

.meta-icon {
    color: #3182ce;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(49, 130, 206, 0.1);
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.meta-content {
    display: flex;
    flex-direction: column;
}

.meta-label {
    font-weight: 600;
    color: #4a5568;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.meta-value {
    color: #2d3748;
    font-size: 1rem;
}

.event-description {
    margin-bottom: 2rem;
    font-size: 1.05rem;
    line-height: 1.7;
}

.event-description p:first-child {
    margin-top: 0;
}

.event-description p:last-child {
    margin-bottom: 0;
}

.event-actions-wrapper {
    margin: 2rem 0;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.event-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.event-action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #4a5568;
    font-weight: 500;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.event-action-button:hover {
    background: #f1f5f9;
    color: #3182ce;
    border-color: #cbd5e0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.event-map {
    margin: 2rem 0;
}

.event-map h3 {
    font-size: 1.4rem;
    margin: 0 0 1rem;
    color: #2d3748;
}

.map-container {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
}

.related-events {
    margin: 3rem 0 1rem;
}

.related-events h3 {
    font-size: 1.4rem;
    margin: 0 0 1.5rem;
    color: #2d3748;
    text-align: center;
}

.upcoming-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.upcoming-event-card {
    display: flex;
    flex-direction: column;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    background: #fff;
}

.upcoming-event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.upcoming-event-image {
    height: 160px;
    overflow: hidden;
}

.upcoming-event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.upcoming-event-card:hover .upcoming-event-image img {
    transform: scale(1.1);
}

.upcoming-event-image .no-image {
    height: 100%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upcoming-event-content {
    padding: 1.25rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.upcoming-event-content h4 {
    margin: 0 0 0.75rem;
    font-size: 1.1rem;
    line-height: 1.4;
}

.upcoming-event-content h4 a {
    color: #2d3748;
    text-decoration: none;
    transition: color 0.2s ease;
}

.upcoming-event-content h4 a:hover {
    color: #3182ce;
}

.upcoming-event-meta {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #4a5568;
}

.upcoming-event-date,
.upcoming-event-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.no-upcoming-events {
    grid-column: 1 / -1;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    color: #4a5568;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .event-content-wrapper {
        padding: 0 1rem;
    }
}

@media (max-width: 768px) {
    .featured-image-container {
        height: 300px;
    }
    
    .event-title-overlay h1 {
        font-size: 2rem;
    }
    
    .event-content-container {
        padding: 1.5rem;
    }
    
    .meta-item {
        flex: 1 0 100%;
    }
    
    .upcoming-events-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
}

@media (max-width: 480px) {
    .featured-image-container {
        height: 220px;
    }
    
    .event-title-overlay h1 {
        font-size: 1.75rem;
    }
    
    .event-content-container {
        padding: 1.25rem;
    }
    
    .event-meta-details {
        padding: 1rem;
    }
    
    .upcoming-events-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?> 