<?php
/**
 * Template for event custom fields meta box
 */

// Get existing custom fields
$custom_fields = get_post_meta($post->ID, '_event_custom_fields', true) ?: array();
wp_nonce_field('event_custom_fields_meta_box', 'event_custom_fields_meta_box_nonce');
?>

<div class="ai-calendar-custom-fields">
    <div id="custom-fields-container">
        <?php 
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $index => $field) {
                ?>
                <div class="custom-field-row">
                    <input type="text" 
                           name="custom_field_keys[]" 
                           value="<?php echo esc_attr($field['key']); ?>" 
                           placeholder="<?php esc_attr_e('Field Name', 'ai-calendar'); ?>" 
                           class="regular-text">
                    <input type="text" 
                           name="custom_field_values[]" 
                           value="<?php echo esc_attr($field['value']); ?>" 
                           placeholder="<?php esc_attr_e('Field Value', 'ai-calendar'); ?>" 
                           class="regular-text">
                    <button type="button" class="button remove-field"><?php _e('Remove', 'ai-calendar'); ?></button>
                </div>
                <?php
            }
        }
        ?>
    </div>
    
    <button type="button" id="add-custom-field" class="button button-secondary">
        <?php _e('Add Custom Field', 'ai-calendar'); ?>
    </button>
</div>

<style>
.ai-calendar-custom-fields .custom-field-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.ai-calendar-custom-fields .custom-field-row input {
    flex: 1;
}

.ai-calendar-custom-fields .remove-field {
    color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#add-custom-field').on('click', function() {
        const template = `
            <div class="custom-field-row">
                <input type="text" 
                       name="custom_field_keys[]" 
                       placeholder="<?php esc_attr_e('Field Name', 'ai-calendar'); ?>" 
                       class="regular-text">
                <input type="text" 
                       name="custom_field_values[]" 
                       placeholder="<?php esc_attr_e('Field Value', 'ai-calendar'); ?>" 
                       class="regular-text">
                <button type="button" class="button remove-field"><?php _e('Remove', 'ai-calendar'); ?></button>
            </div>
        `;
        $('#custom-fields-container').append(template);
    });

    $(document).on('click', '.remove-field', function() {
        $(this).closest('.custom-field-row').remove();
    });
});
</script> 