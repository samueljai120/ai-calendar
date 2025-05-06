<?php
/**
 * Template for event recurrence meta box
 */
?>
<div class="ai-calendar-meta-box">
    <div class="meta-row">
        <div class="meta-group">
            <label for="event_recurring"><?php _e('Is this a recurring event?', 'ai-calendar'); ?></label>
            <select id="event_recurring" name="event_recurring">
                <option value="0" <?php selected($recurring, '0'); ?>><?php _e('No', 'ai-calendar'); ?></option>
                <option value="1" <?php selected($recurring, '1'); ?>><?php _e('Yes', 'ai-calendar'); ?></option>
            </select>
        </div>
    </div>

    <div id="recurring_options" style="display: none;">
        <div class="meta-row">
            <div class="meta-group">
                <label for="recurrence_type"><?php _e('Recurrence Pattern', 'ai-calendar'); ?></label>
                <select id="recurrence_type" name="recurrence_type">
                    <option value="daily" <?php selected($recurrence_type, 'daily'); ?>><?php _e('Daily', 'ai-calendar'); ?></option>
                    <option value="weekly" <?php selected($recurrence_type, 'weekly'); ?>><?php _e('Weekly', 'ai-calendar'); ?></option>
                    <option value="monthly" <?php selected($recurrence_type, 'monthly'); ?>><?php _e('Monthly', 'ai-calendar'); ?></option>
                    <option value="yearly" <?php selected($recurrence_type, 'yearly'); ?>><?php _e('Yearly', 'ai-calendar'); ?></option>
                </select>
            </div>
            <div class="meta-group">
                <label for="recurrence_interval"><?php _e('Repeat Every', 'ai-calendar'); ?></label>
                <input type="number" id="recurrence_interval" name="recurrence_interval" min="1" value="<?php echo esc_attr($recurrence_interval); ?>">
                <span class="interval-label"></span>
            </div>
        </div>

        <!-- Weekly Options -->
        <div id="weekly_options" class="recurrence-options" style="display: none;">
            <div class="meta-row">
                <div class="meta-group">
                    <label><?php _e('Repeat On', 'ai-calendar'); ?></label>
                    <div class="weekday-options">
                        <?php
                        $weekdays = [
                            'sunday' => __('Sunday', 'ai-calendar'),
                            'monday' => __('Monday', 'ai-calendar'),
                            'tuesday' => __('Tuesday', 'ai-calendar'),
                            'wednesday' => __('Wednesday', 'ai-calendar'),
                            'thursday' => __('Thursday', 'ai-calendar'),
                            'friday' => __('Friday', 'ai-calendar'),
                            'saturday' => __('Saturday', 'ai-calendar')
                        ];
                        foreach ($weekdays as $value => $label) {
                            echo '<label class="checkbox-label">';
                            echo '<input type="checkbox" name="recurrence_weekly_days[]" value="' . esc_attr($value) . '" ' . 
                                 (in_array($value, (array)$recurrence_weekly_days) ? 'checked' : '') . '> ' . 
                                 esc_html($label);
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Options -->
        <div id="monthly_options" class="recurrence-options" style="display: none;">
            <div class="meta-row">
                <div class="meta-group">
                    <label><?php _e('Repeat On', 'ai-calendar'); ?></label>
                    <select name="recurrence_monthly_type">
                        <option value="day_of_month" <?php selected($recurrence_monthly_type, 'day_of_month'); ?>>
                            <?php _e('Day of the month', 'ai-calendar'); ?>
                        </option>
                        <option value="day_of_week" <?php selected($recurrence_monthly_type, 'day_of_week'); ?>>
                            <?php _e('Day of the week', 'ai-calendar'); ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="meta-row">
            <div class="meta-group">
                <label><?php _e('End Recurrence', 'ai-calendar'); ?></label>
                <select name="recurrence_end_type" id="recurrence_end_type">
                    <option value="never" <?php selected($recurrence_end_type, 'never'); ?>>
                        <?php _e('Never', 'ai-calendar'); ?>
                    </option>
                    <option value="after" <?php selected($recurrence_end_type, 'after'); ?>>
                        <?php _e('After', 'ai-calendar'); ?>
                    </option>
                    <option value="on_date" <?php selected($recurrence_end_type, 'on_date'); ?>>
                        <?php _e('On Date', 'ai-calendar'); ?>
                    </option>
                </select>
            </div>
            <div class="meta-group" id="recurrence_count_group" style="display: none;">
                <label for="recurrence_count"><?php _e('Number of Occurrences', 'ai-calendar'); ?></label>
                <input type="number" id="recurrence_count" name="recurrence_count" min="1" value="<?php echo esc_attr($recurrence_count); ?>">
            </div>
            <div class="meta-group" id="recurrence_end_date_group" style="display: none;">
                <label for="recurrence_end_date"><?php _e('End Date', 'ai-calendar'); ?></label>
                <input type="date" id="recurrence_end_date" name="recurrence_end_date" value="<?php echo esc_attr($recurrence_end_date); ?>">
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle recurring options visibility
    function toggleRecurringOptions() {
        if ($('#event_recurring').val() === '1') {
            $('#recurring_options').slideDown();
        } else {
            $('#recurring_options').slideUp();
        }
    }

    $('#event_recurring').on('change', toggleRecurringOptions);
    toggleRecurringOptions();

    // Update interval label based on recurrence type
    function updateIntervalLabel() {
        const type = $('#recurrence_type').val();
        const labels = {
            daily: '<?php _e("Day(s)", "ai-calendar"); ?>',
            weekly: '<?php _e("Week(s)", "ai-calendar"); ?>',
            monthly: '<?php _e("Month(s)", "ai-calendar"); ?>',
            yearly: '<?php _e("Year(s)", "ai-calendar"); ?>'
        };
        $('.interval-label').text(labels[type]);
    }

    $('#recurrence_type').on('change', function() {
        updateIntervalLabel();
        $('.recurrence-options').hide();
        
        if ($(this).val() === 'weekly') {
            $('#weekly_options').show();
        } else if ($(this).val() === 'monthly') {
            $('#monthly_options').show();
        }
    }).trigger('change');

    // Handle end recurrence type
    $('#recurrence_end_type').on('change', function() {
        const type = $(this).val();
        $('#recurrence_count_group, #recurrence_end_date_group').hide();
        
        if (type === 'after') {
            $('#recurrence_count_group').show();
        } else if (type === 'on_date') {
            $('#recurrence_end_date_group').show();
        }
    }).trigger('change');
});
</script>

<style>
.ai-calendar-meta-box {
    padding: 12px;
}

.meta-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.meta-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.meta-group label {
    font-weight: 600;
    margin-bottom: 5px;
}

.meta-group input,
.meta-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.meta-group input:focus,
.meta-group select:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

#recurring_options {
    background: #f9f9f9;
    padding: 1rem;
    margin-top: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.weekday-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.interval-label {
    margin-left: 0.5rem;
    color: #666;
}

#recurrence_interval {
    width: 80px;
    display: inline-block;
}

.recurrence-options {
    margin-top: 1rem;
    padding: 1rem;
    background: #f0f0f1;
    border-radius: 4px;
}

@media (max-width: 782px) {
    .meta-row {
        flex-direction: column;
        gap: 12px;
    }

    .weekday-options {
        flex-direction: column;
    }
}
</style> 