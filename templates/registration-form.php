<?php
/**
 * Template for event registration form
 */

defined('ABSPATH') || exit;

$event_id = get_the_ID();
$remaining = intval($event_meta['capacity']) - intval($event_meta['current_attendees']);
?>

<div id="event-registration-modal" class="registration-modal" aria-hidden="true">
    <div class="registration-modal-overlay"></div>
    <div class="registration-modal-container" role="dialog" aria-modal="true">
        <header class="registration-modal-header">
            <h2><?php esc_html_e('Register for Event', 'ai-calendar'); ?></h2>
            <button class="close-modal" aria-label="<?php esc_attr_e('Close registration form', 'ai-calendar'); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
        </header>

        <div class="registration-modal-content">
            <form id="event-registration-form" class="registration-form">
                <?php wp_nonce_field('ai_calendar_event_registration', 'registration_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                
                <!-- Event Summary -->
                <div class="event-summary">
                    <h3><?php the_title(); ?></h3>
                    <div class="event-details">
                        <p class="event-date">
                            <i class="ai-calendar-icon-calendar"></i>
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
                        </p>
                        <?php if ($event_meta['location']) : ?>
                        <p class="event-location">
                            <i class="ai-calendar-icon-location"></i>
                            <?php echo esc_html($event_meta['location']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Personal Information -->
                <fieldset class="form-section">
                    <legend><?php esc_html_e('Personal Information', 'ai-calendar'); ?></legend>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name"><?php esc_html_e('First Name', 'ai-calendar'); ?> <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name"><?php esc_html_e('Last Name', 'ai-calendar'); ?> <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email"><?php esc_html_e('Email Address', 'ai-calendar'); ?> <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                        <small class="help-text"><?php esc_html_e('Confirmation details will be sent to this email address.', 'ai-calendar'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="phone"><?php esc_html_e('Phone Number', 'ai-calendar'); ?></label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </fieldset>

                <!-- Registration Options -->
                <?php if ($event_meta['cost']) : ?>
                <fieldset class="form-section">
                    <legend><?php esc_html_e('Registration Options', 'ai-calendar'); ?></legend>
                    
                    <div class="form-group">
                        <label for="ticket_quantity"><?php esc_html_e('Number of Tickets', 'ai-calendar'); ?> <span class="required">*</span></label>
                        <select id="ticket_quantity" name="ticket_quantity" required>
                            <?php
                            $max_tickets = min(10, $remaining);
                            for ($i = 1; $i <= $max_tickets; $i++) {
                                printf(
                                    '<option value="%1$d">%1$d</option>',
                                    $i
                                );
                            }
                            ?>
                        </select>
                        <small class="help-text">
                            <?php
                            echo sprintf(
                                esc_html__('Cost per ticket: %s', 'ai-calendar'),
                                esc_html($event_meta['cost'])
                            );
                            ?>
                        </small>
                    </div>
                </fieldset>
                <?php endif; ?>

                <!-- Additional Information -->
                <fieldset class="form-section">
                    <legend><?php esc_html_e('Additional Information', 'ai-calendar'); ?></legend>
                    
                    <div class="form-group">
                        <label for="dietary_requirements"><?php esc_html_e('Dietary Requirements', 'ai-calendar'); ?></label>
                        <select id="dietary_requirements" name="dietary_requirements">
                            <option value=""><?php esc_html_e('None', 'ai-calendar'); ?></option>
                            <option value="vegetarian"><?php esc_html_e('Vegetarian', 'ai-calendar'); ?></option>
                            <option value="vegan"><?php esc_html_e('Vegan', 'ai-calendar'); ?></option>
                            <option value="gluten-free"><?php esc_html_e('Gluten Free', 'ai-calendar'); ?></option>
                            <option value="other"><?php esc_html_e('Other (Please Specify)', 'ai-calendar'); ?></option>
                        </select>
                    </div>

                    <div class="form-group dietary-other" style="display: none;">
                        <label for="dietary_other"><?php esc_html_e('Please specify dietary requirements', 'ai-calendar'); ?></label>
                        <textarea id="dietary_other" name="dietary_other" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="special_requirements"><?php esc_html_e('Special Requirements', 'ai-calendar'); ?></label>
                        <textarea id="special_requirements" name="special_requirements" rows="3" placeholder="<?php esc_attr_e('Please let us know if you have any special requirements or accessibility needs.', 'ai-calendar'); ?>"></textarea>
                    </div>
                </fieldset>

                <!-- Terms and Conditions -->
                <div class="form-section">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms_accepted" name="terms_accepted" required>
                        <label for="terms_accepted">
                            <?php
                            echo sprintf(
                                esc_html__('I agree to the %sTerms and Conditions%s', 'ai-calendar'),
                                '<a href="#" target="_blank">',
                                '</a>'
                            );
                            ?> <span class="required">*</span>
                        </label>
                    </div>

                    <?php if ($event_meta['cost']) : ?>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="cancellation_policy" name="cancellation_policy" required>
                        <label for="cancellation_policy">
                            <?php
                            echo sprintf(
                                esc_html__('I understand the %sCancellation Policy%s', 'ai-calendar'),
                                '<a href="#" target="_blank">',
                                '</a>'
                            );
                            ?> <span class="required">*</span>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-registration">
                        <?php 
                        echo $event_meta['cost']
                            ? esc_html__('Proceed to Payment', 'ai-calendar')
                            : esc_html__('Complete Registration', 'ai-calendar');
                        ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 