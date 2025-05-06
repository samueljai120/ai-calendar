<?php
/**
 * Basic template for displaying event archive
 */

// Get theme settings
$theme_settings = new AiCalendar\Settings\ThemeSettings();
$current_theme = $theme_settings->get_current_theme();
$colors = $current_theme['colors'];

get_header();
?>

<div class="event-archive-container">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('Events', 'ai-calendar'); ?></h1>
    </header>

    <div class="event-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('event-card'); ?>>
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="event-image">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="event-content">
                        <h2 class="event-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <div class="event-meta">
                            <?php
                            $start_date = get_post_meta(get_the_ID(), '_event_start_date', true);
                            $location = get_post_meta(get_the_ID(), '_event_location', true);
                            
                            if ($start_date) {
                                $date = new DateTime($start_date);
                                echo '<div class="event-date">';
                                echo '<i class="ai-calendar-icon-calendar"></i> ';
                                echo esc_html($date->format(get_option('date_format')));
                                echo '</div>';
                            }

                            if ($location) {
                                echo '<div class="event-location">';
                                echo '<i class="ai-calendar-icon-location"></i> ';
                                echo esc_html($location);
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <div class="event-excerpt">
                            <?php the_excerpt(); ?>
                        </div>

                        <a href="<?php the_permalink(); ?>" class="read-more">
                            <?php esc_html_e('View Event', 'ai-calendar'); ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>

            <?php the_posts_pagination(); ?>

        <?php else : ?>
            <p class="no-events"><?php esc_html_e('No events found.', 'ai-calendar'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
    :root {
        --calendar-primary: <?php echo esc_attr($colors['primary']); ?>;
        --calendar-secondary: <?php echo esc_attr($colors['secondary']); ?>;
        --calendar-background: <?php echo esc_attr($colors['background']); ?>;
        --calendar-text: <?php echo esc_attr($colors['text']); ?>;
        --calendar-border: <?php echo esc_attr($colors['border']); ?>;
    }

    .event-archive-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .page-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-title {
        color: var(--calendar-text);
        margin: 0;
    }

    .event-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .event-card {
        background: var(--calendar-background);
        border: 1px solid var(--calendar-border);
        border-radius: 8px;
        overflow: hidden;
    }

    .event-image {
        position: relative;
        padding-top: 56.25%;
    }

    .event-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .event-content {
        padding: 1.5rem;
    }

    .event-title {
        margin: 0 0 1rem;
        font-size: 1.25rem;
    }

    .event-title a {
        color: var(--calendar-text);
        text-decoration: none;
    }

    .event-meta {
        margin-bottom: 1rem;
        color: var(--calendar-secondary);
        font-size: 0.875rem;
    }

    .event-date,
    .event-location {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .event-excerpt {
        color: var(--calendar-text);
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    .read-more {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: var(--calendar-primary);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.875rem;
    }

    .no-events {
        text-align: center;
        color: var(--calendar-text);
        grid-column: 1 / -1;
        padding: 2rem;
    }

    .pagination {
        margin-top: 2rem;
        text-align: center;
    }

    .pagination .page-numbers {
        display: inline-block;
        padding: 0.5rem 1rem;
        margin: 0 0.25rem;
        background: var(--calendar-background);
        border: 1px solid var(--calendar-border);
        border-radius: 4px;
        color: var(--calendar-text);
        text-decoration: none;
    }

    .pagination .current {
        background: var(--calendar-primary);
        color: white;
        border-color: var(--calendar-primary);
    }
</style>

<?php get_footer(); ?> 