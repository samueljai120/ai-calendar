<?php
/**
 * Calendar template
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Get theme settings
$theme_settings = new AiCalendar\Settings\ThemeSettings();
$current_theme = $theme_settings->get_current_theme();

// Add theme classes
$theme_class = isset($current_theme['theme']) ? ' theme-' . $current_theme['theme'] : '';
$enable_theme = isset($current_theme['enable_theme']) ? $current_theme['enable_theme'] : true;

// Generate inline styles with proper escaping
$style = '';
if ($enable_theme && !empty($current_theme['colors']) && is_array($current_theme['colors'])) {
    $style_vars = [];
    foreach ($current_theme['colors'] as $key => $value) {
        if ($value) {
            $style_vars[] = sprintf('--calendar-%s: %s', sanitize_html_class($key), esc_attr($value));
        }
    }
    $style = implode('; ', $style_vars);
}

// Debug theme settings
if (WP_DEBUG) {
    error_log('AI Calendar Template: Using theme settings: ');
    error_log('Theme: ' . $current_theme['theme']);
    error_log('Colors: ' . print_r($current_theme['colors'], true));
    error_log('Style: ' . $style);
}

// Get current month and year
$current_month = isset($_GET['month']) ? intval($_GET['month']) : current_time('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : current_time('Y');

// Get first day of the month and number of days
$first_day = new DateTime("$current_year-$current_month-01");
$num_days = intval($first_day->format('t'));

// Get the day of week for the first day (0 = Sunday, 6 = Saturday)
$first_day_of_week = intval($first_day->format('w'));

// Get month name and year
$month_name = date_i18n('F', $first_day->getTimestamp());
$year = $first_day->format('Y');

// Get localized weekday names starting from Sunday
$weekdays = [];
for ($i = 0; $i < 7; $i++) {
    $weekdays[] = date_i18n('D', strtotime("Sunday +{$i} days"));
}

// Debug output
if (WP_DEBUG) {
    error_log('AI Calendar Template Debug:');
    error_log('First day of month: ' . $first_day->format('Y-m-d'));
    error_log('First day of week: ' . $first_day_of_week);
    error_log('Number of days: ' . $num_days);
}
?>

<div class="ai-calendar<?php echo esc_attr($theme_class); ?>" 
     data-options='<?php echo json_encode([
         'eventsPerDay' => 3,
         'showEventTime' => true,
         'showEventLocation' => true,
         'firstDayOfWeek' => 0, // Always start with Sunday
         'debug' => WP_DEBUG
     ]); ?>'
     <?php echo $style ? sprintf('style="%s"', esc_attr($style)) : ''; ?>>
    <!-- Calendar Header -->
    <div class="calendar-header">
        <div class="month-row">
            <button type="button" class="nav-button prev-month" aria-label="<?php esc_attr_e('Previous month', 'ai-calendar'); ?>">
                &larr; <?php esc_html_e('Previous', 'ai-calendar'); ?>
            </button>
            <div class="current-month">
                <?php echo esc_html($month_name . ' ' . $year); ?>
            </div>
            <button type="button" class="nav-button next-month" aria-label="<?php esc_attr_e('Next month', 'ai-calendar'); ?>">
                <?php esc_html_e('Next', 'ai-calendar'); ?> &rarr;
            </button>
        </div>
        <div class="button-row">
            <button type="button" class="nav-button today-button" aria-label="<?php esc_attr_e('Go to today', 'ai-calendar'); ?>">
                <?php esc_html_e('Today', 'ai-calendar'); ?>
            </button>
        </div>
    </div>

    <!-- Weekday Headers -->
    <div class="weekday-header">
        <?php foreach ($weekdays as $weekday): ?>
            <div class="weekday"><?php echo esc_html($weekday); ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Calendar Grid -->
    <div class="calendar-grid">
        <?php
        // Add empty cells for days before the first day of the month
        for ($i = 0; $i < $first_day_of_week; $i++): ?>
            <div class="day-container empty">
                <div></div>
            </div>
        <?php endfor;

        // Add cells for each day of the month
        for ($day = 1; $day <= $num_days; $day++): 
            $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
            $is_today = $date === current_time('Y-m-d');
            $day_classes = ['day-container'];
            if ($is_today) {
                $day_classes[] = 'today';
            }
        ?>
            <div class="<?php echo esc_attr(implode(' ', $day_classes)); ?>" data-date="<?php echo esc_attr($date); ?>">
                <div>
                    <div class="day-header">
                <div class="day-number"><?php echo esc_html($day); ?></div>
                    </div>
                <div class="events-container"></div>
                </div>
            </div>
        <?php endfor;

        // Calculate remaining days to complete the grid
        $total_days = $first_day_of_week + $num_days;
        $remaining_days = $total_days % 7 === 0 ? 0 : 7 - ($total_days % 7);
        
        // Add empty cells for remaining days
        for ($i = 0; $i < $remaining_days; $i++): ?>
            <div class="day-container empty">
                <div></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Event Preview Modal -->
    <div class="event-preview-modal" style="display: none;">
        <div class="modal-content">
            <button type="button" class="close-modal" aria-label="<?php esc_attr_e('Close', 'ai-calendar'); ?>">&times;</button>
            <div class="event-preview-content"></div>
        </div>
    </div>

    <?php if (WP_DEBUG): ?>
        <div class="debug-info" style="display: none;">
            <pre><?php echo esc_html(print_r($current_theme, true)); ?></pre>
        </div>
    <?php endif; ?>
</div>

<script>
// Debug initialization
jQuery(document).ready(function($) {
    if (window.aiCalendar && aiCalendar.debug) {
        console.group('AI Calendar Initialization');
        console.log('Theme Settings:', aiCalendar.theme);
        console.log('AJAX URL:', aiCalendar.ajaxurl);
        console.log('Plugin URL:', aiCalendar.pluginUrl);
        console.groupEnd();
    }
});
</script> 