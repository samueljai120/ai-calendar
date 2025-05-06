<?php
/**
 * Theme Settings Admin Page Template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

$theme_settings = new AiCalendar\Settings\ThemeSettings();
$current_settings = $theme_settings->get_current_theme();
$themes = $theme_settings->get_themes();
$color_options = $theme_settings->get_color_options();
?>

<!-- Main Form - Start form at the top of the template -->
<form method="post" id="ai-calendar-theme-form" action="options.php">
    <input type="hidden" name="action" value="save_calendar_theme">
    <?php wp_nonce_field('ai_calendar_theme_settings', 'theme_settings_nonce'); ?>
    
    <div class="wrap">
        <h1><?php _e('Calendar Theme Settings', 'ai-calendar'); ?></h1>
        
        <div class="theme-settings-container">
            <div class="theme-settings-row">
                <!-- Theme Selection -->
                <div class="theme-section">
                    <h2><?php _e('Select Theme', 'ai-calendar'); ?></h2>
                    
                    <!-- Theme Enable/Disable -->
                    <div class="theme-toggle">
                        <label>
                            <input type="checkbox" name="ai_calendar_theme_settings[enable_theme]" value="1" 
                                <?php checked(isset($current_settings['enable_theme']) ? $current_settings['enable_theme'] : true); ?>>
                            <?php _e('Enable custom theme', 'ai-calendar'); ?>
                        </label>
                    </div>
                    
                    <div class="theme-options">
                        <?php foreach ($themes as $theme_id => $theme): ?>
                            <div class="theme-option<?php echo $current_settings['theme'] === $theme_id ? ' active' : ''; ?>"
                                 data-theme="<?php echo esc_attr($theme_id); ?>">
                                <h3><?php echo esc_html($theme['name']); ?></h3>
                                <p><?php echo esc_html($theme['description']); ?></p>
                                <div class="color-palette">
                                    <?php foreach ($theme['colors'] as $color_key => $color): ?>
                                        <div class="color-preview" style="background-color: <?php echo esc_attr($color); ?>"
                                             title="<?php echo esc_attr(ucwords(str_replace('_', ' ', $color_key))); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="radio" name="ai_calendar_theme_settings[theme]" value="<?php echo esc_attr($theme_id); ?>"
                                       <?php checked($current_settings['theme'], $theme_id); ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="theme-preview">
                    <h2><?php _e('Live Preview', 'ai-calendar'); ?></h2>
                    <div id="calendar-preview">
                        <?php echo do_shortcode('[ai_calendar]'); ?>
                    </div>
                </div>
            </div>

            <!-- Customize Colors -->
            <div class="customize-colors">
                <h2><?php _e('Customize Colors', 'ai-calendar'); ?></h2>
                <div class="color-settings">
                    <?php foreach ($color_options as $color_key => $color_data): ?>
                        <div class="color-field">
                            <label><?php echo esc_html($color_data['label']); ?></label>
                            <div class="color-palette-picker">
                                <div class="current-color">
                                    <div class="color-preview" style="background-color: <?php echo esc_attr($current_settings['colors'][$color_key] ?? $color_data['default']); ?>"></div>
                                    <input type="text" class="color-picker" name="ai_calendar_theme_settings[colors][<?php echo esc_attr($color_key); ?>]" 
                                           value="<?php echo esc_attr($current_settings['colors'][$color_key] ?? $color_data['default']); ?>">
                                </div>
                                <div class="color-options">
                                    <?php foreach ($color_data['options'] as $color): ?>
                                        <div class="color-option<?php echo ($current_settings['colors'][$color_key] ?? $color_data['default']) === $color ? ' active' : ''; ?>"
                                             data-color="<?php echo esc_attr($color); ?>"
                                             style="background-color: <?php echo esc_attr($color); ?>"
                                             title="<?php echo esc_attr($color); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="submit-section">
                <button type="submit" class="button button-primary" id="save-settings-btn">
                    <?php _e('Save Settings', 'ai-calendar'); ?>
                </button>
                <span class="spinner"></span>
                <div class="save-status"></div>
            </div>
        </div>
    </div>
</form>

<style>
.theme-settings-container {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: 2rem;
    margin-top: 2rem;
    max-width: 1600px;
}

.theme-settings-form {
    min-width: 0;
}

.theme-section {
    background: #fff;
    padding: 2rem;
    margin-bottom: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.theme-section h2 {
    margin: 0 0 1.5rem;
    padding: 0;
    font-size: 1.3rem;
    color: #1d2327;
}

.theme-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.theme-toggle input[type="checkbox"] {
    margin: 0;
}

.description {
    color: #646970;
    font-size: 0.9rem;
    margin: 0;
}

.theme-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.theme-option {
    border: 2px solid #ddd;
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    background: #fff;
}

.theme-option:hover {
    border-color: #2271b1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.theme-option.active {
    border-color: #2271b1;
    background: #f0f7ff;
}

.theme-option h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    padding-right: 2rem;
}

.theme-option p {
    margin: 0 0 1rem;
    color: #646970;
    font-size: 0.9rem;
}

.theme-option input[type="radio"] {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    margin: 0;
}

.color-palette {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1rem 0;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.02);
    border-radius: 6px;
}

.color-preview {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.color-preview:hover {
    transform: scale(1.1);
}

.color-settings {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.color-field {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e2e4e7;
}

.color-field label {
    display: block;
    margin-bottom: 1rem;
    font-weight: 500;
    color: #1d2327;
}

.color-palette-picker {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.current-color {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.current-color .color-preview {
    width: 36px;
    height: 36px;
}

.wp-picker-container {
    display: inline-block;
}

.wp-picker-container .wp-color-result.button {
    margin: 0;
}

.color-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    padding: 1rem;
    background: #fff;
    border-radius: 6px;
    border: 1px solid #e2e4e7;
}

.color-option {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.color-option:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.color-option.active {
    border-color: #2271b1;
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.theme-preview {
    position: sticky;
    top: 32px;
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.theme-preview h2 {
    margin: 0 0 1.5rem;
    padding: 0;
    font-size: 1.3rem;
    color: #1d2327;
}

#calendar-preview {
    overflow: hidden;
    border-radius: 6px;
    border: 1px solid #e2e4e7;
}

.submit-section {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.save-status {
    flex-grow: 1;
}

.save-status .notice {
    margin: 0;
}

@media (max-width: 1400px) {
    .theme-settings-container {
        grid-template-columns: 1fr;
    }
    
    .theme-preview {
        position: static;
        margin-top: 2rem;
    }
}

@media (max-width: 782px) {
    .theme-section {
        padding: 1.5rem;
    }
    
    .theme-options {
        grid-template-columns: 1fr;
    }
    
    .color-settings {
        grid-template-columns: 1fr;
    }
    
    .submit-section {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .submit-section .button {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize WordPress color picker
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            const $input = $(event.target);
            const color = ui.color.toString();
            
            // Update color preview
            $input.closest('.color-field').find('.current-color .color-preview')
                .css('background-color', color);
            
            // Update active state of color options
            const $options = $input.closest('.color-field').find('.color-option');
            $options.removeClass('active');
            $options.filter(`[data-color="${color}"]`).addClass('active');
            
            // Immediately update preview after color change
            setTimeout(updatePreview, 50);
        }
    });

    // Theme option selection
    $('.theme-option').on('click', function() {
        const $option = $(this);
        const themeId = $option.data('theme');
        
        // Update radio button
        $option.find('input[type="radio"]').prop('checked', true);
        
        // Update active state
        $('.theme-option').removeClass('active');
        $option.addClass('active');
        
        // Get theme colors
        const themes = <?php echo wp_json_encode($themes); ?>;
        const theme = themes[themeId];
        
        if (theme && theme.colors) {
            // Update each color picker with theme colors
            Object.entries(theme.colors).forEach(([key, value]) => {
                const $picker = $(`.color-picker[name="ai_calendar_theme_settings[colors][${key}]"]`);
                if ($picker.length) {
                    $picker.wpColorPicker('color', value);
                }
            });
        }
        
        // Update preview with a slight delay to ensure color pickers are updated
        setTimeout(updatePreview, 100);
    });

    // Color option selection
    $('.color-option').on('click', function() {
        const $option = $(this);
        const color = $option.data('color');
        const $field = $option.closest('.color-field');
        
        // Update color picker
        $field.find('.color-picker').wpColorPicker('color', color);
        
        // Update preview immediately
        setTimeout(updatePreview, 50);
    });
    
    // Form submission handler
    $('#ai-calendar-theme-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $('#save-settings-btn');
        const $spinner = $form.find('.spinner');
        const $status = $form.find('.save-status');
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Get form data directly
        const formData = new FormData(this);
        formData.append('action', 'save_calendar_theme');
        formData.append('nonce', '<?php echo wp_create_nonce('ai_calendar_theme_settings'); ?>');
        
        // Log form data
        console.log('Submitting theme settings');
        
        // Submit via ajax
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                console.log('Save response:', response);
                
                if (response.success) {
                    // Show success message
                    $status.html(`<div class="notice notice-success inline"><p>${response.data.message}</p></div>`);
                    
                    // Clear message after delay
                    setTimeout(function() {
                        $status.html('');
                    }, 3000);
                } else {
                    // Show error message
                    $status.html(`<div class="notice notice-error inline"><p>${response.data.message || 'Error saving settings'}</p></div>`);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                $status.html('<div class="notice notice-error inline"><p>Error saving settings. Please try again.</p></div>');
            },
            complete: function() {
                // Reset button and spinner
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    function updatePreview() {
        const themeId = $('input[name="ai_calendar_theme_settings[theme]"]:checked').val();
        const themes = <?php echo wp_json_encode($themes); ?>;
        let colors = {};
        
        // Get current color values from color pickers
        $('.color-picker').each(function() {
            const $picker = $(this);
            const key = $picker.attr('name').match(/\[colors\]\[(.*?)\]/)[1];
            colors[key] = $picker.val() || (themes[themeId] && themes[themeId].colors[key]);
        });
        
        // Build CSS custom properties string
        const style = Object.entries(colors)
            .map(([key, value]) => `--calendar-${key.replace(/_/g, '-')}: ${value}`)
            .join(';');
        
        // Apply styles to preview calendar
        $('#calendar-preview .ai-calendar').attr('style', style);
        
        // Force refresh of calendar display
        $('#calendar-preview .ai-calendar').trigger('calendar:refresh');
    }

    // Initial preview update with delay to ensure everything is loaded
    setTimeout(updatePreview, 200);
});
</script> 