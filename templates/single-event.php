<?php
/**
 * Template for displaying single event
 */

// Get theme settings
$theme_settings = new AiCalendar\Settings\ThemeSettings();
$current_theme = $theme_settings->get_current_theme();
$colors = $current_theme['colors'];

get_header();

// Get event meta data
$event_meta = array(
    'start_date' => get_post_meta(get_the_ID(), '_event_start_date', true),
    'end_date' => get_post_meta(get_the_ID(), '_event_end_date', true),
    'location' => get_post_meta(get_the_ID(), '_event_location', true),
    'organizer' => get_post_meta(get_the_ID(), '_event_organizer', true)
);

// Calculate dates
$start_datetime = new DateTime($event_meta['start_date']);
$end_datetime = new DateTime($event_meta['end_date']);
?>

<div class="event-single-container">
    <main id="main" class="site-main">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('event-single'); ?>>
                <!-- Event Header -->
                <header class="event-header">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="event-featured-image">
                            <?php the_post_thumbnail('full'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    
                    <div class="event-meta">
                        <!-- Date & Time -->
                        <div class="event-meta-item">
                            <i class="ai-calendar-icon-calendar"></i>
                            <div class="meta-content">
                                <?php
                                if ($event_meta['start_date'] === $event_meta['end_date']) {
                                    echo sprintf(
                                        '%s at %s',
                                        esc_html($start_datetime->format(get_option('date_format'))),
                                        esc_html($start_datetime->format(get_option('time_format')))
                                    );
                                } else {
                                    echo sprintf(
                                        '%s - %s',
                                        esc_html($start_datetime->format('M j, Y g:i A')),
                                        esc_html($end_datetime->format('M j, Y g:i A'))
                                    );
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Location -->
                        <?php if ($event_meta['location']) : ?>
                        <div class="event-meta-item">
                            <i class="ai-calendar-icon-location"></i>
                            <div class="meta-content">
                                <?php echo esc_html($event_meta['location']); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Organizer -->
                        <?php if ($event_meta['organizer']) : ?>
                        <div class="event-meta-item">
                            <i class="ai-calendar-icon-user"></i>
                            <div class="meta-content">
                                <?php echo esc_html($event_meta['organizer']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- Event Content -->
                <div class="event-content">
                    <?php the_content(); ?>
                </div>

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
                    $template_path = plugin_dir_path(dirname(__DIR__)) . 'templates/parts/event-actions.php';
                    
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

                <!-- Event Categories and Tags -->
                <footer class="event-footer">
                    <?php
                    $categories = get_the_terms(get_the_ID(), 'event_category');
                    $tags = get_the_terms(get_the_ID(), 'event_tag');
                    ?>
                    
                    <?php if ($categories && !is_wp_error($categories)) : ?>
                    <div class="event-categories">
                        <h3><?php _e('Categories', 'ai-calendar'); ?></h3>
                        <div class="term-list">
                            <?php foreach ($categories as $category) : ?>
                                <a href="<?php echo esc_url(get_term_link($category)); ?>" class="term-link">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($tags && !is_wp_error($tags)) : ?>
                    <div class="event-tags">
                        <h3><?php _e('Tags', 'ai-calendar'); ?></h3>
                        <div class="term-list">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="term-link">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </footer>
            </article>
        <?php endwhile; ?>
    </main>
</div>

<style>
    :root {
        --calendar-primary: <?php echo esc_attr($colors['primary']); ?>;
        --calendar-secondary: <?php echo esc_attr($colors['secondary']); ?>;
        --calendar-background: <?php echo esc_attr($colors['background']); ?>;
        --calendar-text: <?php echo esc_attr($colors['text']); ?>;
        --calendar-border: <?php echo esc_attr($colors['border']); ?>;
    }

    .event-single-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .event-featured-image {
        margin-bottom: 2rem;
    }

    .event-featured-image img {
        width: 100%;
        height: auto;
    }

    .entry-title {
        margin-bottom: 1.5rem;
        color: var(--calendar-text);
    }

    .event-meta {
        margin-bottom: 2rem;
        padding: 1rem;
        background: var(--calendar-background);
        border: 1px solid var(--calendar-border);
        border-radius: 4px;
    }

    .event-meta-item {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .event-meta-item i {
        margin-right: 0.5rem;
        color: var(--calendar-primary);
    }

    .event-content {
        color: var(--calendar-text);
    }

    /* Added: Export and Share Styles */
    .event-actions {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--calendar-border);
    }

    .export-options,
    .social-share {
        margin-bottom: 2rem;
    }

    .export-options h3,
    .social-share h3 {
        font-size: 1.2rem;
        margin-bottom: 1rem;
        color: var(--calendar-text);
    }

    .export-buttons,
    .share-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .export-button,
    .share-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .export-button svg,
    .share-button svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
    }

    .google-calendar {
        background-color: #4285f4;
        color: white;
    }

    .ical {
        background-color: #34A853;
        color: white;
    }

    .facebook {
        background-color: #1877f2;
        color: white;
    }

    .twitter {
        background-color: #1da1f2;
        color: white;
    }

    .linkedin {
        background-color: #0077b5;
        color: white;
    }

    .export-button:hover,
    .share-button:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .export-buttons,
        .share-buttons {
            flex-direction: column;
        }

        .export-button,
        .share-button {
            width: 100%;
            justify-content: center;
        }
    }

    .event-footer {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--calendar-border);
    }

    .event-categories,
    .event-tags {
        margin-bottom: 1.5rem;
    }

    .event-categories h3,
    .event-tags h3 {
        font-size: 1.2rem;
        margin-bottom: 1rem;
        color: var(--calendar-text);
    }

    .term-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .term-link {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: var(--calendar-background);
        border: 1px solid var(--calendar-border);
        border-radius: 4px;
        text-decoration: none;
        color: var(--calendar-text);
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .term-link:hover {
        background: var(--calendar-primary);
        color: white;
        border-color: var(--calendar-primary);
    }

    @media (max-width: 768px) {
        .term-list {
            flex-direction: column;
        }

        .term-link {
            width: 100%;
            text-align: center;
        }
    }
</style>

<?php get_footer(); ?> 