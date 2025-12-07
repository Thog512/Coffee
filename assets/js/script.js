// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const icon = document.querySelector('.toggle-password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('remember').checked;
    
    // Basic validation
    if (!username || !password) {
        showAlert('Please fill in all fields', 'error');
        return;
    }
    
    // Show loading state
    const loginBtn = document.querySelector('.login-btn');
    const originalBtnText = loginBtn.innerHTML;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    loginBtn.disabled = true;
    
    // Simulate API call (replace with actual authentication)
    setTimeout(() => {
        // This is a simulation - in a real app, you would make an API call to your backend
        console.log('Login attempt with:', { username, rememberMe });
        
        // For demo purposes, always show success
        showAlert('Login successful! Redirecting...', 'success');
        
        // Reset button state
        loginBtn.innerHTML = originalBtnText;
        loginBtn.disabled = false;
        
        // Redirect to dashboard after successful login
        setTimeout(() => {
            window.location.href = 'dashboard.html'; // Replace with your dashboard URL
        }, 1500);
        
    }, 1500);
});

// Show alert message
function showAlert(message, type = 'info') {
    // Remove any existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // Style the alert
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.padding = '12px 20px';
    alert.style.borderRadius = '4px';
    alert.style.color = 'white';
    alert.style.fontWeight = '500';
    alert.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    alert.style.zIndex = '1000';
    alert.style.animation = 'slideIn 0.3s ease-out forwards';
    
    // Set background color based on type
    if (type === 'error') {
        alert.style.backgroundColor = '#ff4d4f';
    } else if (type === 'success') {
        alert.style.backgroundColor = '#52c41a';
    } else {
        alert.style.backgroundColor = '#1890ff';
    }
    
    document.body.appendChild(alert);
    
    // Auto-remove alert after 5 seconds
    setTimeout(() => {
        alert.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Add keyframe animations for alerts
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }
`;
document.head.appendChild(style);

// Social login handlers
document.querySelector('.social-btn.google').addEventListener('click', function() {
    showAlert('Redirecting to Google login...', 'info');
    // Implement Google OAuth here
});

document.querySelector('.social-btn.facebook').addEventListener('click', function() {
    showAlert('Redirecting to Facebook login...', 'info');
    // Implement Facebook OAuth here
});

// Forgot password handler
document.querySelector('.forgot-password').addEventListener('click', function(e) {
    e.preventDefault();
    showAlert('Please contact support to reset your password.', 'info');
});

// Add input focus effects
const inputs = document.querySelectorAll('input');
inputs.forEach(input => {
    // Add focus class when input is focused
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    // Remove focus class when input loses focus
    input.addEventListener('blur', function() {
        this.parentElement.classList.remove('focused');
    });
});

// Add this to your existing CSS
const additionalStyles = `
    .input-with-icon.focused i {
        color: var(--primary-color) !important;
    }
    
    .input-with-icon.focused input {
        border-color: var(--primary-color) !important;
    }
`;

const styleElement = document.createElement('style');
styleElement.textContent = additionalStyles;
document.head.appendChild(styleElement);
