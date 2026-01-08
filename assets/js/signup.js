/**
 * ChatPanel Leave Manager - Signup Form JavaScript
 * Handles form validation, password strength, and user interactions
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Password strength indicator
        $('#password').on('input', function() {
            updatePasswordStrength($(this).val());
        });

        // Form submission
        $('#signup-form').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time email validation
        $('#email').on('blur', function() {
            validateEmail($(this).val());
        });

        // Password confirmation check
        $('#password_confirm').on('input', function() {
            checkPasswordMatch();
        });

        // Inline form validation
        $('.form-input').on('blur', function() {
            validateField($(this));
        });
    });

    /**
     * Update password strength indicator
     */
    function updatePasswordStrength(password) {
        const strengthBar = $('#password-strength-bar');
        const strengthText = $('#password-strength-text');
        let strength = 0;
        let text = 'Weak';
        let color = '#ef4444'; // Red

        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[!@#$%^&*]/.test(password)) strength++;

        // Determine strength level
        if (strength <= 2) {
            text = 'Weak';
            color = '#ef4444'; // Red
        } else if (strength <= 3) {
            text = 'Fair';
            color = '#f59e0b'; // Orange
        } else if (strength <= 4) {
            text = 'Good';
            color = '#eab308'; // Yellow
        } else {
            text = 'Strong';
            color = '#10b981'; // Green
        }

        // Update visual indicator
        const percentage = (strength / 6) * 100;
        strengthBar.css({
            'width': percentage + '%',
            'background-color': color
        });

        strengthText.text('Password strength: ' + text).css('color', color);
    }

    /**
     * Validate email format
     */
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(email);

        if (email && !isValid) {
            showFieldError($('#email'), 'Please enter a valid email address');
            return false;
        } else {
            clearFieldError($('#email'));
            return true;
        }
    }

    /**
     * Check if passwords match
     */
    function checkPasswordMatch() {
        const password = $('#password').val();
        const passwordConfirm = $('#password_confirm').val();

        if (passwordConfirm && password !== passwordConfirm) {
            showFieldError($('#password_confirm'), 'Passwords do not match');
            return false;
        } else {
            clearFieldError($('#password_confirm'));
            return true;
        }
    }

    /**
     * Validate individual field
     */
    function validateField($field) {
        const fieldName = $field.attr('name');
        const fieldValue = $field.val().trim();

        switch (fieldName) {
            case 'first_name':
                if (!fieldValue) {
                    showFieldError($field, 'First name is required');
                    return false;
                }
                clearFieldError($field);
                return true;

            case 'last_name':
                if (!fieldValue) {
                    showFieldError($field, 'Last name is required');
                    return false;
                }
                clearFieldError($field);
                return true;

            case 'email':
                return validateEmail(fieldValue);

            case 'password':
                if (!fieldValue) {
                    showFieldError($field, 'Password is required');
                    return false;
                }
                if (fieldValue.length < 8) {
                    showFieldError($field, 'Password must be at least 8 characters');
                    return false;
                }
                if (!/[A-Z]/.test(fieldValue)) {
                    showFieldError($field, 'Password must contain an uppercase letter');
                    return false;
                }
                if (!/[0-9]/.test(fieldValue)) {
                    showFieldError($field, 'Password must contain a number');
                    return false;
                }
                if (!/[!@#$%^&*]/.test(fieldValue)) {
                    showFieldError($field, 'Password must contain a special character');
                    return false;
                }
                clearFieldError($field);
                return true;

            case 'password_confirm':
                return checkPasswordMatch();

            default:
                return true;
        }
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        // Remove existing error
        $field.siblings('.field-error').remove();

        // Add error class
        $field.css('border-color', '#ef4444');

        // Add error message
        const errorDiv = $('<div class="field-error" style="color: #991b1b; font-size: 13px; margin-top: 6px;">' + message + '</div>');
        $field.after(errorDiv);
    }

    /**
     * Clear field error
     */
    function clearFieldError($field) {
        $field.css('border-color', '#d1d5db');
        $field.siblings('.field-error').remove();
    }

    /**
     * Validate entire form
     */
    function validateForm() {
        let isValid = true;

        // Validate all required fields
        const fields = ['first_name', 'last_name', 'email', 'password', 'password_confirm'];

        fields.forEach(function(fieldName) {
            const $field = $('[name="' + fieldName + '"]');
            if (!validateField($field)) {
                isValid = false;
            }
        });

        // Check terms acceptance
        if (!$('[name="terms"]').is(':checked')) {
            alert('Please accept the Terms of Service and Privacy Policy');
            isValid = false;
        }

        return isValid;
    }

})(jQuery);
