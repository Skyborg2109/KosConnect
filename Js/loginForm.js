// Password toggle functionality
const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#password');

if (togglePassword && password) {
    togglePassword.addEventListener('click', () => {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Toggle icon
        const icon = togglePassword.querySelector('i');
        icon.classList.toggle('bx-show');
        icon.classList.toggle('bx-hide');
    });
}

// Form submission handler
function handleSubmit(event) {
    const email = event.target.email.value;
    const password = event.target.password.value;
    
    // Client-side validation
    if (!email || !password) {
        showError('Mohon lengkapi semua field');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('Format email tidak valid');
        return false;
    }
    
    // Allow form submission to server
    return true;
}

// Error message display
function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    
    if (errorDiv && errorText) {
        errorText.textContent = message;
        errorDiv.className = 'message-box error';
        
        // Change icon to error
        const icon = errorDiv.querySelector('i');
        if (icon) {
            icon.className = 'bx bx-error-circle';
        }
        
        setTimeout(() => {
            errorDiv.classList.add('hidden');
        }, 5000);
    }
}

// Success message display
function showSuccess(message) {
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    
    if (errorDiv && errorText) {
        errorText.textContent = message;
        errorDiv.className = 'message-box success';
        
        // Change icon to success
        const icon = errorDiv.querySelector('i');
        if (icon) {
            icon.className = 'bx bx-check-circle';
        }
    }
}

// Register link handler
function showRegisterMessage() {
    alert('Fitur registrasi akan segera tersedia! Silakan hubungi administrator untuk membuat akun baru.');
}

// Add input animation effects
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.input-field');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.input-box').style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.closest('.input-box').style.transform = 'scale(1)';
        });
    });
});

// Prevent form submission on Enter key in input fields (optional)
document.addEventListener('keypress', function(event) {
    if (event.key === 'Enter' && event.target.tagName === 'INPUT') {
        const form = event.target.closest('form');
        if (form) {
            handleSubmit(event);
        }
    }
});