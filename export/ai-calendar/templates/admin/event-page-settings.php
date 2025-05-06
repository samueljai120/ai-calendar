<?php
if (!defined('ABSPATH')) {
    exit;
}

$event_settings = new AiCalendar\Settings\EventPageSettings();
$current_settings = $event_settings->get_current_settings();
$templates = $event_settings->get_templates();
?>

<div class="wrap ai-calendar-settings">
    <h1><?php _e('Event Page Settings', 'ai-calendar'); ?></h1>
    
    <form id="ai-calendar-event-settings" method="post">
        <?php wp_nonce_field('ai_calendar_event_settings', 'event_settings_nonce'); ?>
        
        <!-- Template Selection -->
        <div class="settings-section">
            <h2><?php _e('Select Template', 'ai-calendar'); ?></h2>
            <p class="section-description"><?php _e('Choose how your event pages will be displayed.', 'ai-calendar'); ?></p>
            
            <div class="template-options">
                <?php foreach ($templates as $template_id => $template): ?>
                    <div class="template-option<?php echo $current_settings['template'] === $template_id ? ' active' : ''; ?>"
                         data-template="<?php echo esc_attr($template_id); ?>">
                        <?php if ($template_id !== 'none' && isset($template['preview_image'])): ?>
                            <div class="template-preview">
                                <img src="<?php echo esc_url(plugins_url('/assets/images/' . $template['preview_image'], dirname(dirname(__FILE__)))); ?>"
                                     alt="<?php echo esc_attr($template['name']); ?> preview">
                            </div>
                        <?php endif; ?>
                        
                        <div class="template-content">
                            <div class="template-header">
                                <h3><?php echo esc_html($template['name']); ?></h3>
                                <input type="radio" name="settings[template]" value="<?php echo esc_attr($template_id); ?>"
                                       <?php checked($current_settings['template'], $template_id); ?>>
                            </div>
                            
                            <p class="description"><?php echo esc_html($template['description']); ?></p>
                            
                            <?php if ($template_id !== 'none'): ?>
                                <div class="template-features">
                                    <?php if (!empty($template['features']) && is_array($template['features'])): ?>
                                        <ul>
                                            <?php foreach ($template['features'] as $feature): ?>
                                                <li><?php echo esc_html($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif ($template_id === 'template-1'): ?>
                                        <ul>
                                            <li><?php _e('Full-width featured image banner', 'ai-calendar'); ?></li>
                                            <li><?php _e('Clean and modern layout', 'ai-calendar'); ?></li>
                                            <li><?php _e('Optimized for visual impact', 'ai-calendar'); ?></li>
                                        </ul>
                                    <?php elseif ($template_id === 'template-2'): ?>
                                        <ul>
                                            <li><?php _e('Sidebar with event details', 'ai-calendar'); ?></li>
                                            <li><?php _e('Organized content layout', 'ai-calendar'); ?></li>
                                            <li><?php _e('Perfect for detailed events', 'ai-calendar'); ?></li>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Display Options -->
        <div class="settings-section">
            <h2><?php _e('Display Options', 'ai-calendar'); ?></h2>
            <p class="section-description"><?php _e('Customize which information is shown on your event pages.', 'ai-calendar'); ?></p>
            
            <div class="display-options">
                <?php
                $display_options = [
                    'show_featured_image' => [
                        'label' => __('Featured Image', 'ai-calendar'),
                        'description' => __('Display the event\'s featured image at the top of the page', 'ai-calendar'),
                        'icon' => 'dashicons-format-image'
                    ],
                    'show_date' => [
                        'label' => __('Event Date', 'ai-calendar'),
                        'description' => __('Show the date when the event takes place', 'ai-calendar'),
                        'icon' => 'dashicons-calendar-alt'
                    ],
                    'show_time' => [
                        'label' => __('Event Time', 'ai-calendar'),
                        'description' => __('Display the start and end times of the event', 'ai-calendar'),
                        'icon' => 'dashicons-clock'
                    ],
                    'show_location' => [
                        'label' => __('Location', 'ai-calendar'),
                        'description' => __('Show where the event is taking place', 'ai-calendar'),
                        'icon' => 'dashicons-location'
                    ],
                    'show_description' => [
                        'label' => __('Description', 'ai-calendar'),
                        'description' => __('Display the full event description', 'ai-calendar'),
                        'icon' => 'dashicons-text'
                    ],
                    'show_map' => [
                        'label' => __('Map', 'ai-calendar'),
                        'description' => __('Show an interactive map of the event location', 'ai-calendar'),
                        'icon' => 'dashicons-location-alt'
                    ],
                    'show_related_events' => [
                        'label' => __('Related Events', 'ai-calendar'),
                        'description' => __('Display other upcoming events', 'ai-calendar'),
                        'icon' => 'dashicons-calendar'
                    ]
                ];

                foreach ($display_options as $option => $details): ?>
                    <div class="display-option">
                        <label class="display-option-label">
                            <div class="option-header">
                                <span class="dashicons <?php echo esc_attr($details['icon']); ?>"></span>
                                <div class="option-text">
                                    <span class="option-label"><?php echo esc_html($details['label']); ?></span>
                                    <span class="option-description"><?php echo esc_html($details['description']); ?></span>
                                </div>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" 
                                           name="settings[<?php echo esc_attr($option); ?>]" 
                                           value="1"
                                           <?php checked(isset($current_settings[$option]) && $current_settings[$option]); ?>>
                                    <span class="checkbox-toggle"></span>
                                </div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="submit-section">
            <?php submit_button(__('Save Settings', 'ai-calendar'), 'primary', 'submit', true, ['id' => 'save-settings']); ?>
            <div class="save-status"></div>
        </div>
    </form>
</div>

<style>
.ai-calendar-settings {
    max-width: 1200px;
}

.settings-section {
    background: #fff;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid #ddd;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.settings-section h2 {
    margin: 0 0 0.5rem;
    padding-bottom: 0;
    color: #1d2327;
    font-size: 1.5rem;
}

.section-description {
    color: #646970;
    font-size: 1rem;
    margin: 0 0 2rem;
}

.template-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.template-option {
    display: flex;
    flex-direction: column;
    border: 2px solid #ddd;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
}

.template-option:hover {
    border-color: #2271b1;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.template-option.active {
    border-color: #2271b1;
    background: #f0f7ff;
}

.template-preview {
    overflow: hidden;
    height: 200px;
    position: relative;
}

.template-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.template-option:hover .template-preview img {
    transform: scale(1.05);
}

.template-content {
    padding: 1.5rem;
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.template-header h3 {
    margin: 0;
    color: #1d2327;
    font-size: 1.2rem;
}

.template-option .description {
    margin-bottom: 1.5rem;
    color: #50575e;
    font-size: 0.95rem;
    line-height: 1.5;
}

.template-features {
    margin-top: 1.5rem;
}

.template-features ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.template-features li {
    position: relative;
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
    color: #50575e;
    font-size: 0.95rem;
}

.template-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #2271b1;
    font-weight: bold;
}

.display-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1rem;
}

.display-option {
    background: #f8f9fa;
    padding: 1.25rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.display-option:hover {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.option-header {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1rem;
}

.option-header .dashicons {
    color: #2271b1;
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.option-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.option-label {
    font-weight: 500;
    color: #1d2327;
    font-size: 1rem;
}

.option-description {
    color: #646970;
    font-size: 0.9rem;
    line-height: 1.4;
}

.checkbox-wrapper {
    position: relative;
}

.checkbox-wrapper input[type="checkbox"] {
    display: none;
}

.checkbox-toggle {
    display: block;
    width: 44px;
    height: 24px;
    background: #ccc;
    border-radius: 12px;
    cursor: pointer;
    position: relative;
    transition: background-color 0.2s;
}

.checkbox-toggle:before {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

input[type="checkbox"]:checked + .checkbox-toggle {
    background: #2271b1;
}

input[type="checkbox"]:checked + .checkbox-toggle:before {
    transform: translateX(20px);
}

.submit-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

#save-settings {
    min-width: 120px;
    height: 40px;
    line-height: 38px;
    padding: 0 20px;
    font-size: 1rem;
}

.save-status {
    flex-grow: 1;
    font-size: 0.95rem;
}

/* Add animation for status messages */
.save-status.success,
.save-status.error {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(-5px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Debug logging for form elements
    console.log('AI Calendar: Template options loaded', $('.template-option').length);
    console.log('AI Calendar: Display options loaded', $('.display-option').length);

    // Handle template selection
    $('.template-option').on('click', function() {
        $('.template-option').removeClass('active');
        $(this).addClass('active');
        $(this).find('input[type="radio"]').prop('checked', true);
        
        console.log('AI Calendar: Template selected', $(this).data('template'));
    });
    
    // Form submission with AJAX
    $('#ai-calendar-event-settings').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $('#save-settings');
        const $saveStatus = $('.save-status');
        
        // Log form data for debugging
        console.log('AI Calendar: Form data', $form.serialize());
        console.log('AI Calendar: Template selection', $form.find('input[name="settings[template]"]:checked').val());
        
        // Disable button and show loading
        $submitButton.prop('disabled', true).val('Saving...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=save_event_page_settings',
            success: function(response) {
                console.log('AI Calendar: Save response', response);
                
                if (response.success) {
                    $saveStatus.html('<span style="color: #00a32a;">✓ ' + response.data.message + '</span>').addClass('success');
                } else {
                    $saveStatus.html('<span style="color: #d63638;">✗ ' + response.data.message + '</span>').addClass('error');
                }
                
                // Re-enable button
                $submitButton.prop('disabled', false).val('Save Settings');
                
                // Clear status after a delay
                setTimeout(function() {
                    $saveStatus.html('').removeClass('success error');
                }, 5000);
            },
            error: function(xhr, status, error) {
                console.error('AI Calendar: Save error', xhr.responseText);
                $saveStatus.html('<span style="color: #d63638;">✗ An error occurred while saving settings</span>').addClass('error');
                $submitButton.prop('disabled', false).val('Save Settings');
            }
        });
    });
    
    // Initialize toggle states for checkboxes
    $('.checkbox-wrapper input[type="checkbox"]').each(function() {
        const isChecked = $(this).prop('checked');
        $(this).next('.checkbox-toggle').toggleClass('active', isChecked);
    });
    
    // Handle checkbox toggle clicks
    $('.checkbox-toggle').on('click', function() {
        const $checkbox = $(this).prev('input[type="checkbox"]');
        const newState = !$checkbox.prop('checked');
        $checkbox.prop('checked', newState);
        $(this).toggleClass('active', newState);
        
        console.log('AI Calendar: Checkbox toggled', $checkbox.attr('name'), newState);
    });
});
</script> 