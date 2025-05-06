<?php
/**
 * Template 2: Sidebar layout with event details
 */

$event_settings = new AiCalendar\Settings\EventPageSettings();
$settings = $event_settings->get_current_settings();

get_header();

while (have_posts()) : the_post();
    // Get event meta
    $start_date = get_post_meta(get_the_ID(), '_event_start_date', true);
    $end_date = get_post_meta(get_the_ID(), '_event_end_date', true);
    $location = get_post_meta(get_the_ID(), '_event_location', true);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('event-single template-2'); ?>>
    <div class="container">
        <div class="event-content-grid">
            <main class="event-main-content">
                <h1 class="event-title"><?php the_title(); ?></h1>

                <?php if ($settings['show_featured_image'] && has_post_thumbnail()): ?>
                    <div class="featured-image">
                        <div class="image-wrapper">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($settings['show_description']): ?>
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

                <?php if ($settings['show_map'] && $location): ?>
                    <div class="event-map">
                        <h3><?php _e('Event Location', 'ai-calendar'); ?></h3>
                        <div class="map-container">
                            <iframe
                                width="100%"
                                height="400"
                                frameborder="0"
                                style="border:0"
                                src="https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=<?php echo urlencode($location); ?>"
                                allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($settings['show_related_events']): ?>
                    <div class="related-events">
                        <h3><?php _e('Related Events', 'ai-calendar'); ?></h3>
                        <?php
                        $related_args = array(
                            'post_type' => 'ai_calendar_event',
                            'posts_per_page' => 2,
                            'post__not_in' => array(get_the_ID()),
                            'orderby' => 'meta_value',
                            'meta_key' => '_event_start_date',
                            'order' => 'ASC',
                            'meta_query' => array(
                                array(
                                    'key' => '_event_start_date',
                                    'value' => date('Y-m-d'),
                                    'compare' => '>=',
                                    'type' => 'DATE'
                                )
                            )
                        );
                        $related_events = new WP_Query($related_args);

                        if ($related_events->have_posts()): ?>
                            <div class="related-events-grid">
                                <?php while ($related_events->have_posts()): $related_events->the_post(); ?>
                                    <div class="related-event-card">
                                        <?php if (has_post_thumbnail()): ?>
                                            <div class="event-thumbnail">
                                                <div class="thumbnail-wrapper">
                                                    <?php the_post_thumbnail('medium'); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="event-details">
                                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                            <?php
                                            $event_date = get_post_meta(get_the_ID(), '_event_start_date', true);
                                            if ($event_date) {
                                                echo '<div class="event-date">';
                                                echo date_i18n(get_option('date_format'), strtotime($event_date));
                                                echo '</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php wp_reset_postdata();
                        endif; ?>
                    </div>
                <?php endif; ?>
            </main>

            <aside class="event-sidebar">
                <div class="event-details-card">
                    <?php if ($settings['show_date'] && $start_date): ?>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="dashicons dashicons-calendar-alt"></i>
                            </div>
                            <div class="detail-content">
                                <h3><?php _e('Date & Time', 'ai-calendar'); ?></h3>
                                <div class="detail-text">
                                    <div class="date-start">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($start_date)); ?>
                                        <?php if ($settings['show_time']): ?>
                                            <div class="time">
                                                <?php echo date_i18n(get_option('time_format'), strtotime($start_date)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($end_date): ?>
                                        <div class="date-end">
                                            <span class="until"><?php _e('Until', 'ai-calendar'); ?></span>
                                            <?php if (date('Y-m-d', strtotime($start_date)) !== date('Y-m-d', strtotime($end_date))): ?>
                                                <?php echo date_i18n(get_option('date_format'), strtotime($end_date)); ?>
                                            <?php endif; ?>
                                            <?php if ($settings['show_time']): ?>
                                                <div class="time">
                                                    <?php echo date_i18n(get_option('time_format'), strtotime($end_date)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($settings['show_location'] && $location): ?>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="dashicons dashicons-location"></i>
                            </div>
                            <div class="detail-content">
                                <h3><?php _e('Location', 'ai-calendar'); ?></h3>
                                <div class="detail-text">
                                    <?php echo esc_html($location); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</article>

<style>
.event-single.template-2 {
    margin: 2rem 0;
    background: #f7fafc;
    width: 100%;
}

.container {
    width: 100%;
    margin: 0 auto;
    padding: 0;
}

.event-content-grid {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    align-items: start;
    box-sizing: border-box;
}

.event-main-content {
    background: #fff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    width: 100%;
}

.event-title {
    font-size: 2.25rem;
    margin: 0 0 1.5rem;
    color: #1a202c;
    line-height: 1.2;
}

.featured-image {
    margin: 0 -2rem 2rem;
    overflow: hidden;
}

.image-wrapper {
    position: relative;
    padding-top: 56.25%; /* 16:9 aspect ratio */
}

.image-wrapper img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-description {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #4a5568;
    margin-bottom: 2rem;
}

.event-map {
    margin: 2rem 0;
}

.event-map h3 {
    margin-bottom: 1rem;
    color: #2d3748;
}

.map-container {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.event-sidebar {
    position: sticky;
    top: 2rem;
}

.event-details-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.detail-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1rem;
    padding: 1.25rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.detail-item:first-child {
    padding-top: 0;
}

.detail-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.detail-icon {
    color: #4299e1;
    font-size: 1.5rem;
    line-height: 1;
}

.detail-content h3 {
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #718096;
    margin: 0 0 0.5rem;
}

.detail-text {
    color: #2d3748;
    line-height: 1.5;
}

.date-end {
    margin-top: 0.5rem;
}

.until {
    display: block;
    font-size: 0.875rem;
    color: #718096;
    margin-bottom: 0.25rem;
}

.time {
    color: #718096;
    font-size: 0.9375rem;
}

.related-events {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 2px solid #e2e8f0;
}

.related-events h3 {
    margin-bottom: 1.5rem;
    color: #2d3748;
}

.related-events-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.related-event-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.related-event-card:hover {
    transform: translateY(-2px);
}

.event-thumbnail {
    position: relative;
    padding-top: 66.67%; /* 3:2 aspect ratio */
}

.thumbnail-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.thumbnail-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-details {
    padding: 1.25rem;
}

.event-details h4 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    line-height: 1.4;
}

.event-details a {
    color: #2d3748;
    text-decoration: none;
}

.event-details .event-date {
    font-size: 0.9rem;
    color: #718096;
}

@media (max-width: 768px) {
    .event-content-grid {
        grid-template-columns: 1fr;
    }

    .event-sidebar {
        position: static;
        margin-top: 2rem;
    }

    .event-title {
        font-size: 1.875rem;
    }

    .featured-image {
        margin: -1.5rem -1.5rem 1.5rem;
    }

    .related-events-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
endwhile;
get_footer();
?> 