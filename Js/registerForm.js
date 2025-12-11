// Password toggle functionality
function setupPasswordToggle(toggleId, passwordId) {
    const toggleButton = document.querySelector(`#${toggleId}`);
    const passwordField = document.querySelector(`#${passwordId}`);

    if (toggleButton && passwordField) {
        toggleButton.addEventListener('click', () => {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle icon
            const icon = toggleButton.querySelector('i');
            icon.classList.toggle('bx-show');
            icon.classList.toggle('bx-hide');
        });
    }
}

// Initialize password toggles
setupPasswordToggle('togglePassword', 'password');
setupPasswordToggle('toggleConfirmPassword', 'confirmPassword');

// Form validation
function validateForm(formData) {
    const errors = [];
    
    // Name validation
    if (formData.fullname.length < 2) {
        errors.push('Nama lengkap minimal 2 karakter');
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
        errors.push('Format email tidak valid');
    }
    
    // Phone validation
    const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
    if (!phoneRegex.test(formData.no_hp)) {
        errors.push('Format nomor HP tidak valid');
    }
    
    // Role validation
    if (!formData.role) {
        errors.push('Silakan pilih peran Anda');
    }
    
    // Password validation
    if (formData.password.length < 6) {
        errors.push('Password minimal 6 karakter');
    }
    
    // Confirm password validation
    if (formData.password !== formData.confirmPassword) {
        errors.push('Konfirmasi password tidak cocok');
    }
    
    // Terms validation
    if (!formData.terms) {
        errors.push('Anda harus menyetujui syarat & ketentuan');
    }
    
    return errors;
}

function handleSubmit(event) {
    const formData = new FormData(event.target);
    const data = {
        fullname: formData.get('fullname'),
        email: formData.get('email'),
        no_hp: formData.get('no_hp'),
        role: formData.get('role'),
        password: formData.get('password'),
        confirmPassword: formData.get('confirmPassword'),
        terms: formData.get('terms')
    };
    
    // Validate form
    const errors = validateForm(data);
    
    if (errors.length > 0) {
        showMessage(errors[0], 'error');
        event.preventDefault();
        return false;
    }
    
    // Allow form submission to server
    return true;
}

// Message display function
function showMessage(message, type = 'error') {
    const messageBox = document.getElementById('messageBox');
    const messageText = document.getElementById('messageText');
    
    if (messageBox && messageText) {
        messageText.textContent = message;
        messageBox.className = `message-box ${type}`;
        
        // Change icon based on type
        const icon = messageBox.querySelector('i');
        if (icon) {
            if (type === 'success') {
                icon.className = 'bx bx-check-circle';
            } else {
                icon.className = 'bx bx-error-circle';
            }
        }
        
        // Auto hide error messages
        if (type === 'error') {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 5000);
        }
    }
}

// Login link handler
function showLoginMessage() {
    alert('Anda akan diarahkan ke halaman login.');
    // In real implementation: window.location.href = 'login.html';
}

// Add input animation effects
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.input-field, .select-field');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            const container = this.closest('.input-box') || this.closest('.select-box');
            if (container) {
                container.style.transform = 'scale(1.02)';
            }
        });
        
        input.addEventListener('blur', function() {
            const container = this.closest('.input-box') || this.closest('.select-box');
            if (container) {
                container.style.transform = 'scale(1)';
            }
        });
    });
    
    // Real-time password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    
    if (password && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value && password.value !== this.value) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            }
        });
    }
});

// Phone number formatting
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[name="no_hp"]');
    
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            // Remove non-numeric characters except + and -
            let value = this.value.replace(/[^\d+\-]/g, '');
            
            // Ensure it starts with + or number
            if (value && !value.startsWith('+') && !value.match(/^\d/)) {
                value = value.substring(1);
            }
            
            this.value = value;
        });
    }
});