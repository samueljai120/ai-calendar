<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('AI Calendar Instructions', 'ai-calendar'); ?></h1>
    
    <div class="ai-calendar-instructions">
        <!-- Basic Usage -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php _e('Basic Usage', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <p><?php _e('To display the calendar on any page or post, use one of these shortcodes:', 'ai-calendar'); ?></p>
                <div class="shortcode-examples">
                    <code>[ai_calendar]</code> - <?php _e('Basic calendar display', 'ai-calendar'); ?><br>
                    <code>[ai_calendar view="month"]</code> - <?php _e('Monthly view', 'ai-calendar'); ?><br>
                    <code>[ai_calendar view="week"]</code> - <?php _e('Weekly view', 'ai-calendar'); ?><br>
                    <code>[ai_calendar category="meetings"]</code> - <?php _e('Filter by category', 'ai-calendar'); ?>
                </div>
            </div>
        </div>

        <!-- Creating Events -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-plus-alt"></span> <?php _e('Creating Events', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <ol>
                    <li><?php _e('Navigate to "AI Calendar → Add New" in the WordPress admin menu', 'ai-calendar'); ?></li>
                    <li><?php _e('Enter the event title and description', 'ai-calendar'); ?></li>
                    <li><?php _e('Set the event date and time using the date picker', 'ai-calendar'); ?></li>
                    <li><?php _e('Add location details (optional)', 'ai-calendar'); ?></li>
                    <li><?php _e('Select or add event categories and tags', 'ai-calendar'); ?></li>
                    <li><?php _e('Upload a featured image (optional)', 'ai-calendar'); ?></li>
                    <li><?php _e('Click "Publish" to make the event live', 'ai-calendar'); ?></li>
                </ol>
            </div>
        </div>

        <!-- Managing Events -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Managing Events', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <ul>
                    <li><?php _e('View all events under "AI Calendar → All Events"', 'ai-calendar'); ?></li>
                    <li><?php _e('Edit existing events by clicking on their titles', 'ai-calendar'); ?></li>
                    <li><?php _e('Quick edit options available for basic information', 'ai-calendar'); ?></li>
                    <li><?php _e('Bulk actions available for multiple events', 'ai-calendar'); ?></li>
                    <li><?php _e('Filter events by date, category, or status', 'ai-calendar'); ?></li>
                </ul>
            </div>
        </div>

        <!-- Responsive Behavior -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-smartphone"></span> <?php _e('Responsive Behavior', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <p><?php _e('The calendar automatically adapts to different screen sizes:', 'ai-calendar'); ?></p>
                <ul>
                    <li><?php _e('Desktop (min-width: 985px): Shows up to 3 event slots per day', 'ai-calendar'); ?></li>
                    <li><?php _e('Tablet (min-width: 481px and max-width: 984px): Shows up to 2 event slots per day', 'ai-calendar'); ?></li>
                    <li><?php _e('Mobile (max-width: 480px): Shows 1 event slot per day', 'ai-calendar'); ?></li>
                    <li><?php _e('When more events exist than can be displayed, a "+X more" indicator appears', 'ai-calendar'); ?></li>
                    <li><?php _e('The calendar dynamically calculates available space to ensure events are properly displayed without overflow', 'ai-calendar'); ?></li>
                    <li><?php _e('Events maintain consistent height and spacing across all device sizes', 'ai-calendar'); ?></li>
                </ul>
                <p><?php _e('Note: The calendar also considers the actual height of each day container when determining how many events to display, ensuring proper layout regardless of theme or container constraints.', 'ai-calendar'); ?></p>
            </div>
        </div>

        <!-- Customization -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-admin-appearance"></span> <?php _e('Customization', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <!-- Calendar Theme section removed for public version -->

                <h3><?php _e('Event Page Settings', 'ai-calendar'); ?></h3>
                <p><?php _e('Configure event page display under "AI Calendar → Event Page":', 'ai-calendar'); ?></p>
                <ul>
                    <li><?php _e('Choose event page template', 'ai-calendar'); ?></li>
                    <li><?php _e('Configure event details display', 'ai-calendar'); ?></li>
                    <li><?php _e('Set up event registration options', 'ai-calendar'); ?></li>
                    <li><?php _e('Customize event page layout', 'ai-calendar'); ?></li>
                </ul>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-sos"></span> <?php _e('Troubleshooting', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <h3><?php _e('Common Issues', 'ai-calendar'); ?></h3>
                <ul>
                    <li><?php _e('Calendar not displaying: Check shortcode placement and theme compatibility', 'ai-calendar'); ?></li>
                    <li><?php _e('Events not showing: Verify event dates and publication status', 'ai-calendar'); ?></li>
                    <li><?php _e('Styling issues: Check for theme conflicts and try resetting to default styles', 'ai-calendar'); ?></li>
                    <li><?php _e('Responsive display problems: Ensure your theme is fully responsive and does not override container styles', 'ai-calendar'); ?></li>
                    <li><?php _e('"+X more" indicator not showing: Check if your theme has CSS that might be hiding overflow indicators', 'ai-calendar'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- About -->
        <div class="instruction-card">
            <h2><span class="dashicons dashicons-info"></span> <?php _e('About', 'ai-calendar'); ?></h2>
            <div class="instruction-content">
                <p><?php _e('AI Calendar is developed and maintained by Samuel So.', 'ai-calendar'); ?></p>
                <p><?php _e('For support or inquiries, please contact: samuelso0105@gmail.com', 'ai-calendar'); ?></p>
                <p><?php _e('Copyright © 2023 Samuel So. All rights reserved.', 'ai-calendar'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.ai-calendar-instructions {
    max-width: 1200px;
    margin: 20px 0;
}

.instruction-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.instruction-card h2 {
    margin: 0 0 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    font-size: 1.3em;
    color: #1d2327;
}

.instruction-card h2 .dashicons {
    margin-right: 10px;
    color: #2271b1;
}

.instruction-card h3 {
    margin: 20px 0 10px;
    font-size: 1.1em;
    color: #1d2327;
}

.instruction-content {
    color: #50575e;
    line-height: 1.6;
}

.instruction-content ul,
.instruction-content ol {
    margin: 0 0 15px 20px;
}

.instruction-content li {
    margin-bottom: 8px;
}

.shortcode-examples {
    background: #f0f0f1;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.shortcode-examples code {
    background: #fff;
    padding: 3px 5px;
    border-radius: 3px;
}

@media screen and (max-width: 782px) {
    .instruction-card {
        padding: 15px;
    }
    
    .instruction-card h2 {
        font-size: 1.2em;
    }
}
</style> 