class EventRegistration {
    constructor() {
        // Initialize properties
        this.modal = document.getElementById('event-registration-modal');
        this.form = document.getElementById('event-registration-form');
        this.registerButton = document.querySelector('.register-button');
        this.closeButton = this.modal?.querySelector('.close-modal');
        this.overlay = this.modal?.querySelector('.registration-modal-overlay');
        this.dietarySelect = document.getElementById('dietary_requirements');
        this.dietaryOther = document.querySelector('.dietary-other');
        
        // Bind event handlers
        this.initializeEventHandlers();
    }

    initializeEventHandlers() {
        // Registration button click
        if (this.registerButton) {
            this.registerButton.addEventListener('click', () => this.openModal());
        }

        // Close button click
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => this.closeModal());
        }

        // Overlay click
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeModal());
        }

        // Escape key press
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isModalOpen()) {
                this.closeModal();
            }
        });

        // Dietary requirements change
        if (this.dietarySelect) {
            this.dietarySelect.addEventListener('change', () => this.toggleDietaryOther());
        }

        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }

    openModal() {
        if (!this.modal) return;
        
        this.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Focus first input
        setTimeout(() => {
            this.form?.querySelector('input:not([type="hidden"])').focus();
        }, 100);
    }

    closeModal() {
        if (!this.modal) return;
        
        this.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        
        // Reset form
        this.form?.reset();
        this.toggleDietaryOther();
        this.clearValidation();
    }

    isModalOpen() {
        return this.modal?.getAttribute('aria-hidden') === 'false';
    }

    toggleDietaryOther() {
        if (!this.dietaryOther || !this.dietarySelect) return;
        
        this.dietaryOther.style.display = 
            this.dietarySelect.value === 'other' ? 'block' : 'none';
    }

    validateForm() {
        if (!this.form) return false;

        let isValid = true;
        this.clearValidation();

        // Required fields validation
        this.form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                this.showError(field, aiCalendar.i18n.required_field);
                isValid = false;
            }
        });

        // Email validation
        const emailField = this.form.querySelector('[type="email"]');
        if (emailField?.value && !this.isValidEmail(emailField.value)) {
            this.showError(emailField, aiCalendar.i18n.invalid_email);
            isValid = false;
        }

        // Phone validation (if provided)
        const phoneField = this.form.querySelector('[type="tel"]');
        if (phoneField?.value && !this.isValidPhone(phoneField.value)) {
            this.showError(phoneField, aiCalendar.i18n.invalid_phone);
            isValid = false;
        }

        // Dietary requirements validation
        if (this.dietarySelect?.value === 'other' && 
            !document.getElementById('dietary_other')?.value.trim()) {
            this.showError(this.dietarySelect, aiCalendar.i18n.specify_dietary);
            isValid = false;
        }

        return isValid;
    }

    showError(field, message) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        formGroup.classList.add('has-error');
        
        const helpText = formGroup.querySelector('.help-text');
        if (helpText) {
            helpText.textContent = message;
        } else {
            const errorText = document.createElement('small');
            errorText.className = 'help-text';
            errorText.textContent = message;
            formGroup.appendChild(errorText);
        }
    }

    clearValidation() {
        if (!this.form) return;

        this.form.querySelectorAll('.has-error').forEach(group => {
            group.classList.remove('has-error');
            const helpText = group.querySelector('.help-text');
            if (helpText?.parentElement === group) {
                helpText.textContent = helpText.dataset.originalText || '';
            }
        });
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidPhone(phone) {
        return /^[\d\s\-+()]{10,}$/.test(phone);
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) return;

        const submitButton = this.form?.querySelector('.submit-registration');
        if (!submitButton) return;

        try {
            submitButton.disabled = true;
            submitButton.classList.add('loading');

            const formData = new FormData(this.form);
            formData.append('action', 'ai_calendar_register');
            
            const response = await fetch(aiCalendar.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('success', data.message);
                this.closeModal();
                
                // Update capacity display if available
                if (data.remaining !== undefined) {
                    this.updateCapacityDisplay(data.remaining);
                }
                
                // Redirect to payment page if needed
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } else {
                this.showNotification('error', data.message || aiCalendar.i18n.registration_error);
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showNotification('error', aiCalendar.i18n.registration_error);
        } finally {
            submitButton.disabled = false;
            submitButton.classList.remove('loading');
        }
    }

    updateCapacityDisplay(remaining) {
        const capacityValue = document.querySelector('.capacity-value');
        const capacityProgress = document.querySelector('.capacity-progress');
        const registerButton = document.querySelector('.register-button');
        
        if (capacityValue) {
            capacityValue.textContent = remaining === 1
                ? aiCalendar.i18n.one_spot_left
                : aiCalendar.i18n.spots_left.replace('%d', remaining);
        }
        
        if (capacityProgress && aiCalendar.eventCapacity) {
            const percentage = ((aiCalendar.eventCapacity - remaining) / aiCalendar.eventCapacity) * 100;
            capacityProgress.style.width = `${percentage}%`;
        }
        
        if (registerButton) {
            if (remaining <= 0) {
                registerButton.disabled = true;
                registerButton.textContent = aiCalendar.i18n.sold_out;
            }
        }
    }

    showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `ai-calendar-notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Remove after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new EventRegistration();
}); 