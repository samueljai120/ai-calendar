jQuery(document).ready(function($) {
    'use strict';

    // Initialize theme settings when document is ready
    new ThemeSettings();

    class ThemeSettings {
        constructor() {
            this.$form = $('#ai-calendar-theme-form');
            this.$enableTheme = $('input[name="ai_calendar_theme_settings[enable_theme]"]');
            this.$themeOptions = $('.theme-option');
            this.$colorPickers = $('.color-picker');
            this.$colorOptions = $('.color-option');
            this.$preview = $('#calendar-preview');
            this.currentTheme = this.$themeOptions.filter('.active').data('theme');
            this.previewCalendar = null;
            
            this.init();
        }
        
        init() {
            // Check if form exists
            if (!this.$form.length) {
                console.error('Theme settings form not found');
                return;
            }
            
            console.log('Theme settings initializing...');
            
            this.initColorPickers();
            this.initPreviewCalendar();
            this.attachEventListeners();
            
            // Initial state
            this.toggleThemeControls(this.$enableTheme.is(':checked'));
            this.updatePreview();
            
            console.log('Theme settings initialized');
        }
        
        initColorPickers() {
            if (typeof $.fn.wpColorPicker !== 'function') {
                console.error('WordPress Color Picker not loaded');
                return;
            }
            
            // Initialize WordPress color picker for each color field
            this.$colorPickers.each((index, element) => {
                const $picker = $(element);
                $picker.wpColorPicker({
                    change: (event, ui) => {
                        $picker.val(ui.color.toString());
                        this.updatePreview();
                    },
                    clear: () => {
                        this.updatePreview();
                    }
                });
            });
        }
        
        initPreviewCalendar() {
            if (this.$preview.length) {
                this.previewCalendar = new PreviewCalendar(this.$preview.find('.ai-calendar')[0]);
                window.previewCalendar = this.previewCalendar;
            }
        }
        
        attachEventListeners() {
            // Theme enable/disable
            this.$enableTheme.on('change', (e) => {
                this.toggleThemeControls(e.target.checked);
                this.updatePreview();
            });
            
            // Theme selection
            this.$themeOptions.on('click', (e) => {
                const $clicked = $(e.currentTarget);
                this.$themeOptions.removeClass('active');
                $clicked.addClass('active');
                
                const themeId = $clicked.data('theme');
                
                // Update radio button and theme value
                $clicked.find('input[type="radio"]').prop('checked', true);
                
                // Update color pickers with theme colors
                if (aiCalendarThemes.themes[themeId]) {
                    const colors = aiCalendarThemes.themes[themeId].colors;
                    Object.entries(colors).forEach(([key, value]) => {
                        const $picker = this.$colorPickers.filter(`[name="ai_calendar_theme_settings[colors][${key}]"]`);
                        if ($picker.length) {
                            $picker.wpColorPicker('color', value);
                        }
                    });
                }
                
                // Force immediate preview update
                this.updatePreview();
            });
            
            // Color option selection
            this.$colorOptions.on('click', (e) => {
                const $clicked = $(e.currentTarget);
                const $field = $clicked.closest('.color-field');
                const color = $clicked.data('color');
                
                // Update active state
                $field.find('.color-option').removeClass('active');
                $clicked.addClass('active');
                
                // Update color picker
                const $picker = $field.find('.color-picker');
                $picker.wpColorPicker('color', color);
                
                this.updatePreview();
            });
            
            // Form submission
            this.$form.on('submit', (e) => {
                e.preventDefault();
                console.log('Form submitted');
                this.saveSettings();
            });
        }
        
        toggleThemeControls(enabled) {
            const $controls = this.$form.find('.customize-colors');
            if (enabled) {
                $controls.slideDown();
            } else {
                $controls.slideUp();
            }
        }
        
        getFormData() {
            const formData = new FormData(this.$form[0]);
            const settings = {
                enable_theme: Boolean(formData.get('ai_calendar_theme_settings[enable_theme]')),
                theme: formData.get('ai_calendar_theme_settings[theme]'),
                colors: {}
            };
            
            // Get color values from the DOM since formData might not have all colors
            this.$colorPickers.each((i, picker) => {
                const $picker = $(picker);
                const name = $picker.attr('name');
                // Extract the color key from the name pattern: ai_calendar_theme_settings[colors][primary]
                const matches = name.match(/\[colors\]\[([^\]]+)\]/);
                if (matches && matches[1]) {
                    const key = matches[1];
                    settings.colors[key] = $picker.val();
                }
            });
            
            console.log('Form data collected:', settings);
            return settings;
        }
        
        updatePreview() {
            const settings = this.getFormData();
            
            if (this.$preview.length) {
                // Get the correct theme colors
                const themeColors = settings.enable_theme ? 
                    (settings.theme ? aiCalendarThemes.themes[settings.theme].colors : {}) : 
                    settings.colors;
                
                // Apply theme class first
                const $calendar = this.$preview.find('.ai-calendar');
                $calendar.removeClass((index, className) => {
                    return (className.match(/(^|\s)theme-\S+/g) || []).join(' ');
                });
                
                if (settings.enable_theme && settings.theme) {
                    $calendar.addClass(`theme-${settings.theme}`);
                }
                
                // Apply color variables
                Object.entries(themeColors).forEach(([key, value]) => {
                    const cssVar = `--calendar-${key.replace(/_/g, '-')}`;
                    $calendar[0].style.setProperty(cssVar, value);
                });
                
                // Force refresh of preview calendar
                if (this.previewCalendar) {
                    this.previewCalendar.renderCalendar();
                }
            }
        }
        
        saveSettings() {
            const settings = this.getFormData();
            
            // Create form data for submission
            const formData = new FormData();
            formData.append('action', 'save_calendar_theme');
            formData.append('nonce', aiCalendarThemes.nonce);
            formData.append('settings', JSON.stringify(settings));
            
            // Log what we're sending for debugging
            console.log('Sending settings:', settings);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: () => {
                    this.$form.find('button[type="submit"]').prop('disabled', true);
                    this.showMessage('info', 'Saving settings...');
                },
                success: (response) => {
                    console.log('Server response:', response);
                    if (response.success) {
                        this.showMessage('success', 'Theme settings saved successfully.');
                        // Force page reload to ensure new settings are applied
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showMessage('error', response.data?.message || 'Failed to save settings.');
                        console.error('Save error:', response);
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('error', 'Failed to save settings. Please try again.');
                    console.error('Ajax error:', error, xhr.responseText);
                },
                complete: () => {
                    this.$form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        }
        
        showMessage(type, message) {
            const $message = $('<div>')
                .addClass(`notice notice-${type} is-dismissible`)
                .append($('<p>').text(message))
                .append('<button type="button" class="notice-dismiss"></button>');
            
            // Remove existing notices
            $('.notice').remove();
            
            // Add new notice
            this.$form.before($message);
            
            // Auto dismiss after 3 seconds
            if (type !== 'error') {
                setTimeout(() => {
                    $message.fadeOut(() => $message.remove());
                }, 3000);
            }
            
            // Make notices dismissible
            $message.find('.notice-dismiss').on('click', () => {
                $message.fadeOut(() => $message.remove());
            });
        }
    }

    function updateEventCounts() {
        document.querySelectorAll('.events-container').forEach(container => {
            const events = container.querySelectorAll('.event');
            const moreEvents = container.querySelector('.more-events');
            const totalEvents = events.length;
            
            // Hide all events except the first one
            events.forEach((event, index) => {
                event.style.display = index === 0 ? 'block' : 'none';
            });
            
            // Show +x count only if there are more than 1 events
            if (totalEvents > 1) {
                moreEvents.style.display = 'block';
                moreEvents.textContent = `+${totalEvents - 1} more`;
            } else {
                moreEvents.style.display = 'none';
            }
        });
    }

    // Call after DOM load and after any calendar updates
    document.addEventListener('DOMContentLoaded', updateEventCounts);
}); 