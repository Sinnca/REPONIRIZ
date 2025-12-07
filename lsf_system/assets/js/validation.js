/**
 * Lost & Found System - Form Validation
 * Client-side validation for all forms
 */

const Validator = {
    /**
     * Validate email format
     */
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    /**
     * Validate date (YYYY-MM-DD)
     */
    isValidDate(date) {
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(date)) return false;

        const dateObj = new Date(date);
        return dateObj instanceof Date && !isNaN(dateObj);
    },

    /**
     * Check if date is not in future
     */
    isNotFutureDate(date) {
        const inputDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return inputDate <= today;
    },

    /**
     * Validate file upload
     */
    isValidImageFile(file, maxSizeMB = 5) {
        if (!file) return true; // Optional file

        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        const maxSize = maxSizeMB * 1024 * 1024;

        if (!validTypes.includes(file.type)) {
            return {
                valid: false,
                message: 'Please upload a valid image file (JPG, PNG, GIF)'
            };
        }

        if (file.size > maxSize) {
            return {
                valid: false,
                message: `File size must be less than ${maxSizeMB}MB`
            };
        }

        return { valid: true };
    },

    /**
     * Validate required field
     */
    isRequired(value) {
        return value !== null && value !== undefined && value.trim() !== '';
    },

    /**
     * Validate minimum length
     */
    minLength(value, min) {
        return value.length >= min;
    },

    /**
     * Validate maximum length
     */
    maxLength(value, max) {
        return value.length <= max;
    },

    /**
     * Show error message
     */
    showError(inputElement, message) {
        this.clearError(inputElement);

        inputElement.classList.add('error');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;

        inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
    },

    /**
     * Clear error message
     */
    clearError(inputElement) {
        inputElement.classList.remove('error');

        const errorDiv = inputElement.parentNode.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    /**
     * Clear all errors in form
     */
    clearAllErrors(formElement) {
        const errorMessages = formElement.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());

        const errorInputs = formElement.querySelectorAll('.error');
        errorInputs.forEach(input => input.classList.remove('error'));
    }
};

// Form-specific validation functions
const FormValidation = {
    /**
     * Validate login form
     */
    validateLogin(formElement) {
        Validator.clearAllErrors(formElement);
        let isValid = true;

        const email = formElement.querySelector('#email');
        const password = formElement.querySelector('#password');

        // Validate email
        if (!Validator.isRequired(email.value)) {
            Validator.showError(email, 'Email is required');
            isValid = false;
        } else if (!Validator.isValidEmail(email.value)) {
            Validator.showError(email, 'Please enter a valid email address');
            isValid = false;
        }

        // Validate password
        if (!Validator.isRequired(password.value)) {
            Validator.showError(password, 'Password is required');
            isValid = false;
        }

        return isValid;
    },

    /**
     * Validate lost item form
     */
    validateLostItem(formElement) {
        Validator.clearAllErrors(formElement);
        let isValid = true;

        const itemName = formElement.querySelector('#item_name');
        const description = formElement.querySelector('#description');
        const dateLost = formElement.querySelector('#date_lost');
        const photo = formElement.querySelector('#photo');

        // Validate item name
        if (!Validator.isRequired(itemName.value)) {
            Validator.showError(itemName, 'Item name is required');
            isValid = false;
        } else if (!Validator.minLength(itemName.value, 3)) {
            Validator.showError(itemName, 'Item name must be at least 3 characters');
            isValid = false;
        }

        // Validate description
        if (!Validator.isRequired(description.value)) {
            Validator.showError(description, 'Description is required');
            isValid = false;
        } else if (!Validator.minLength(description.value, 10)) {
            Validator.showError(description, 'Description must be at least 10 characters');
            isValid = false;
        }

        // Validate date
        if (!Validator.isRequired(dateLost.value)) {
            Validator.showError(dateLost, 'Date lost is required');
            isValid = false;
        } else if (!Validator.isValidDate(dateLost.value)) {
            Validator.showError(dateLost, 'Please enter a valid date');
            isValid = false;
        } else if (!Validator.isNotFutureDate(dateLost.value)) {
            Validator.showError(dateLost, 'Date cannot be in the future');
            isValid = false;
        }

        // Validate photo (if provided)
        if (photo.files.length > 0) {
            const fileValidation = Validator.isValidImageFile(photo.files[0]);
            if (!fileValidation.valid) {
                Validator.showError(photo, fileValidation.message);
                isValid = false;
            }
        }

        return isValid;
    },

    /**
     * Validate found item form
     */
    validateFoundItem(formElement) {
        Validator.clearAllErrors(formElement);
        let isValid = true;

        const itemName = formElement.querySelector('#item_name');
        const description = formElement.querySelector('#description');
        const dateFound = formElement.querySelector('#date_found');
        const photo = formElement.querySelector('#photo');

        // Validate item name
        if (!Validator.isRequired(itemName.value)) {
            Validator.showError(itemName, 'Item name is required');
            isValid = false;
        } else if (!Validator.minLength(itemName.value, 3)) {
            Validator.showError(itemName, 'Item name must be at least 3 characters');
            isValid = false;
        }

        // Validate description
        if (!Validator.isRequired(description.value)) {
            Validator.showError(description, 'Description is required');
            isValid = false;
        } else if (!Validator.minLength(description.value, 10)) {
            Validator.showError(description, 'Description must be at least 10 characters');
            isValid = false;
        }

        // Validate date
        if (!Validator.isRequired(dateFound.value)) {
            Validator.showError(dateFound, 'Date found is required');
            isValid = false;
        } else if (!Validator.isValidDate(dateFound.value)) {
            Validator.showError(dateFound, 'Please enter a valid date');
            isValid = false;
        } else if (!Validator.isNotFutureDate(dateFound.value)) {
            Validator.showError(dateFound, 'Date cannot be in the future');
            isValid = false;
        }

        // Validate photo (if provided)
        if (photo.files.length > 0) {
            const fileValidation = Validator.isValidImageFile(photo.files[0]);
            if (!fileValidation.valid) {
                Validator.showError(photo, fileValidation.message);
                isValid = false;
            }
        }

        return isValid;
    },

    /**
     * Validate claim schedule form (admin)
     */
    validateClaimSchedule(formElement) {
        Validator.clearAllErrors(formElement);
        let isValid = true;

        const scheduleDate = formElement.querySelector('#schedule_date');
        const scheduleTime = formElement.querySelector('#schedule_time');

        // Validate date
        if (!Validator.isRequired(scheduleDate.value)) {
            Validator.showError(scheduleDate, 'Schedule date is required');
            isValid = false;
        } else if (!Validator.isValidDate(scheduleDate.value)) {
            Validator.showError(scheduleDate, 'Please enter a valid date');
            isValid = false;
        }

        // Validate time
        if (!Validator.isRequired(scheduleTime.value)) {
            Validator.showError(scheduleTime, 'Schedule time is required');
            isValid = false;
        }

        return isValid;
    }
};

// Real-time validation
function setupRealtimeValidation() {
    // Email fields
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !Validator.isValidEmail(this.value)) {
                Validator.showError(this, 'Please enter a valid email address');
            } else {
                Validator.clearError(this);
            }
        });
    });

    // Date fields
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                if (!Validator.isValidDate(this.value)) {
                    Validator.showError(this, 'Please enter a valid date');
                } else if (!Validator.isNotFutureDate(this.value)) {
                    Validator.showError(this, 'Date cannot be in the future');
                } else {
                    Validator.clearError(this);
                }
            }
        });
    });

    // File inputs
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const validation = Validator.isValidImageFile(this.files[0]);
                if (!validation.valid) {
                    Validator.showError(this, validation.message);
                    this.value = ''; // Clear invalid file
                } else {
                    Validator.clearError(this);
                }
            }
        });
    });

    // Required fields
    document.querySelectorAll('[required]').forEach(input => {
        input.addEventListener('blur', function() {
            if (!Validator.isRequired(this.value)) {
                Validator.showError(this, 'This field is required');
            } else {
                Validator.clearError(this);
            }
        });
    });
}

// Initialize validation on page load
document.addEventListener('DOMContentLoaded', function() {
    setupRealtimeValidation();
});

// Export for use in other scripts
window.Validator = Validator;
window.FormValidation = FormValidation;