document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const email = document.getElementById('email');
    const username = document.getElementById('username');
    
    // Real-time password confirmation validation
    function validatePasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
            confirmPassword.style.borderColor = '#ef4444';
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.style.borderColor = '';
        }
    }
    
    // Real-time email validation
    function validateEmail() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email.value && !emailRegex.test(email.value)) {
            email.setCustomValidity('Please enter a valid email address');
            email.style.borderColor = '#ef4444';
        } else {
            email.setCustomValidity('');
            email.style.borderColor = '';
        }
    }
    
    // Real-time username validation
    function validateUsername() {
        if (username.value && username.value.length < 3) {
            username.setCustomValidity('Username must be at least 3 characters long');
            username.style.borderColor = '#ef4444';
        } else {
            username.setCustomValidity('');
            username.style.borderColor = '';
        }
    }
    
    // Password strength indicator
    function checkPasswordStrength() {
        const passwordValue = password.value;
        let strength = 0;
        
        if (passwordValue.length >= 8) strength++;
        if (passwordValue.match(/[a-z]/)) strength++;
        if (passwordValue.match(/[A-Z]/)) strength++;
        if (passwordValue.match(/[0-9]/)) strength++;
        if (passwordValue.match(/[^a-zA-Z0-9]/)) strength++;
        
        // Update password field border based on strength
        if (passwordValue) {
            if (strength <= 2) {
                password.style.borderColor = '#ef4444';
            } else if (strength <= 3) {
                password.style.borderColor = '#f59e0b';
            } else {
                password.style.borderColor = '#10b981';
            }
        } else {
            password.style.borderColor = '';
        }
    }
    
    // Add event listeners
    password.addEventListener('input', function() {
        validatePasswordMatch();
        checkPasswordStrength();
    });
    
    confirmPassword.addEventListener('input', validatePasswordMatch);
    email.addEventListener('input', validateEmail);
    username.addEventListener('input', validateUsername);
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        // Run all validations
        validatePasswordMatch();
        validateEmail();
        validateUsername();
        checkPasswordStrength();
        
        // Check if form is valid
        if (!form.checkValidity()) {
            e.preventDefault();
            
            // Find the first invalid field and focus it
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            // Show error message
            showError('Please fix the errors below before submitting.');
            return false;
        }
        
        // Show loading state
        const submitButton = form.querySelector('.piston-button');
        submitButton.textContent = 'Creating Account...';
        submitButton.disabled = true;
        submitButton.style.opacity = '0.7';
    });
    
    // Phone number formatting
    const phone = document.getElementById('phone');
    phone.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 6) {
            value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 10);
        } else if (value.length >= 3) {
            value = value.slice(0, 3) + ' ' + value.slice(3);
        }
        e.target.value = value;
    });
    
    // Error display function
    function showError(message) {
        // Remove any existing error alerts
        const existingAlert = document.querySelector('.modern-alert.error');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create new error alert
        const errorDiv = document.createElement('div');
        errorDiv.className = 'modern-alert error';
        errorDiv.innerHTML = `<ul><li>${message}</li></ul>`;
        
        // Insert at the top of the form
        form.insertBefore(errorDiv, form.firstChild);
        
        // Scroll to top of form
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    // Add input animations
    const inputs = form.querySelectorAll('.modern-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
});
