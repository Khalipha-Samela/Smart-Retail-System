/**
 * Combined Login and Registration Script
*/

// Helper function to show alerts
function showAlert(message, type = 'error') {
    // Remove existing dynamic alerts
    const existingAlerts = document.querySelectorAll('.dynamic-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} dynamic-alert`;
    alert.textContent = message;
    
    // Insert after the role hint or appropriate element
    const roleHint = document.getElementById('roleHint');
    if (roleHint) {
        roleHint.after(alert);
    } else {
        // Fallback: insert at the beginning of the form
        const form = document.querySelector('form');
        if (form) {
            form.insertBefore(alert, form.firstChild);
        }
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function showError(inputElement, errorElementId, message) {
    inputElement.classList.add('error');
    const errorElement = document.getElementById(errorElementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

// Toggle password visibility
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    if (!passwordInput) return;
    
    const toggleBtn = passwordInput.parentNode.querySelector('.toggle-btn i');
    if (!toggleBtn) return;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleBtn.className = 'fas fa-eye';
    }
}

/*
* LOGIN SPECIFIC FUNCTIONS
*/

// Login type selection
function selectLoginType(type) {
    document.getElementById('loginType').value = type;
    
    // Update active button
    document.querySelectorAll('.login-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.login-type-btn.${type}`).classList.add('active');
    
    // Update button text and hint
    const btnText = document.getElementById('btnText');
    const roleHint = document.getElementById('roleHint');
    
    if (type === 'customer') {
        btnText.textContent = 'Sign In as Customer';
        roleHint.textContent = 'Sign in to shop and manage your orders';
    } else {
        btnText.textContent = 'Sign In as Admin';
        roleHint.textContent = 'Access the admin dashboard to manage products and orders';
    }
}

function validateLoginForm() {
    let isValid = true;

    // Reset errors
    document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));

    // Username validation
    const username = document.getElementById('username');
    if (!username.value.trim()) {
        showError(username, 'username_error', 'Please enter your username or email');
        isValid = false;
    }

    // Password validation
    const password = document.getElementById('password');
    if (!password.value) {
        showError(password, 'password_error', 'Please enter your password');
        isValid = false;
    }

    return isValid;
}

// Login form submission with loading state
async function submitLoginForm() {
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Signing In...';
    btnSpinner.style.display = 'inline-block';

    try {
        const formData = new FormData(document.getElementById('loginForm'));
        
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        const response = await fetch('api/auth/login-api.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        console.log('Response status:', response.status);

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
            console.log('Parsed JSON:', result);

            if (result.success) {
                // Redirect on success
                console.log('Login successful, redirecting to:', result.redirect);
                window.location.href = result.redirect || 'dashboard.php';
            } else {
                // Show server-side validation errors
                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        const input = document.getElementById(field);
                        const errorElement = document.getElementById(field + '_error');
                        if (input && errorElement) {
                            showError(input, field + '_error', result.errors[field]);
                        }
                    });
                }
                
                // Show error message
                showAlert(result.message || 'Login failed. Please try again.', 'error');
            }
        } catch (e) {
            console.log('❌ Response is not JSON:', responseText);
            showAlert('Server error: The response was not valid JSON. Check the console for details.', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showAlert('Network error: ' + error.message, 'error');
    } finally {
        // Reset loading state
        submitBtn.disabled = false;
        const loginType = document.getElementById('loginType').value;
        btnText.textContent = loginType === 'customer' ? 'Sign In as Customer' : 'Sign In as Admin';
        btnSpinner.style.display = 'none';
    }
}

/*
*REGISTER SPECIFIC FUNCTIONS
*/

// Role selection for registration
function selectRole(role) {
    document.getElementById('roleInput').value = role;
    
    // Update active button
    document.querySelectorAll('.login-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.login-type-btn.${role}`).classList.add('active');
    
    // Update hint text and button text
    const roleHint = document.getElementById('roleHint');
    const btnText = document.getElementById('btnText');
    
    const roleHints = {
        'customer': 'Create a customer account to shop and manage orders',
        'staff': 'Create a staff account to manage store operations', 
        'admin': 'Create an admin account for full system access'
    };
    
    const buttonTexts = {
        'customer': 'Create Customer Account',
        'staff': 'Create Staff Account',
        'admin': 'Create Admin Account'
    };
    
    roleHint.textContent = roleHints[role];
    btnText.textContent = buttonTexts[role];
}

// Validate registration form inputs
function validateRegisterForm() {
    let isValid = true;

    // Reset errors
    document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));

    // Role validation
    const roleSelected = document.getElementById('roleInput').value;
    if (!roleSelected) {
        document.getElementById('roleHint').style.color = '#ef4444';
        document.getElementById('roleHint').textContent = 'Please select an account type';
        isValid = false;
    } else {
        document.getElementById('roleHint').style.color = '#6b7280';
    }

    // Full Name validation
    const fullName = document.getElementById('full_name');
    if (fullName && !fullName.value.trim()) {
        showError(fullName, 'full_name_error', 'Please enter your full name');
        isValid = false;
    }

    // Email validation
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email.value)) {
        showError(email, 'email_error', 'Please enter a valid email address');
        isValid = false;
    }

    // Username validation
    const username = document.getElementById('username');
    const usernameRegex = /^[a-zA-Z0-9_]{3,50}$/;
    if (username && !usernameRegex.test(username.value)) {
        showError(username, 'username_error', 'Username must be 3-50 characters and contain only letters, numbers, and underscores');
        isValid = false;
    }

    // Password validation
    const password = document.getElementById('password');
    if (password && password.value.length < 8) {
        showError(password, 'password_error', 'Password must be at least 8 characters long');
        isValid = false;
    } else if (password && !/(?=.*[A-Za-z])(?=.*\d)/.test(password.value)) {
        showError(password, 'password_error', 'Password must contain at least one letter and one number');
        isValid = false;
    }

    // Confirm password validation
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword && password && confirmPassword.value !== password.value) {
        showError(confirmPassword, 'confirm_password_error', 'Passwords do not match');
        isValid = false;
    }

    return isValid;
}

// Register form submission with loading state
async function submitRegisterForm() {
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Creating Account...';
    btnSpinner.style.display = 'inline-block';

    try {
        // Get form values
        const formData = new URLSearchParams();
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        formData.append('role', document.getElementById('roleInput').value);
        formData.append('full_name', document.getElementById('full_name').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('username', document.getElementById('username').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('confirm_password', document.getElementById('confirm_password').value);
        
        console.log('Sending registration data:', Object.fromEntries(formData.entries()));
        
        const response = await fetch('api/auth/register-api.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        });

        console.log('Response status:', response.status);
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Server returned invalid JSON. Check the console for details.');
        }
        
        console.log('Parsed response:', result);

        if (result.success) {
            showAlert('✅ ' + result.message, 'success');
            setTimeout(() => {
                window.location.href = result.redirect || 'dashboard.php';
            }, 2000);
        } else {
            // Show validation errors
            if (result.errors) {
                Object.keys(result.errors).forEach(field => {
                    const input = document.getElementById(field);
                    const errorElement = document.getElementById(field + '_error');
                    if (input && errorElement) {
                        showError(input, field + '_error', result.errors[field]);
                    }
                });
            }
            showAlert('❌ ' + (result.message || 'Registration failed'), 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showAlert('❌ ' + error.message, 'error');
    } finally {
        // Reset loading state
        submitBtn.disabled = false;
        const role = document.getElementById('roleInput').value;
        const buttonTexts = {
            'customer': 'Create Customer Account',
            'staff': 'Create Staff Account',
            'admin': 'Create Admin Account'
        };
        btnText.textContent = buttonTexts[role] || 'Create Account';
        btnSpinner.style.display = 'none';
    }
}

// ===== VALIDATION FUNCTIONS =====
function validateField(field) {
    const value = field.value.trim();
    
    switch(field.id) {
        case 'username':
            if (!value) {
                showError(field, 'username_error', 'Please enter your username or email');
            } else {
                const usernameRegex = /^[a-zA-Z0-9_]{3,50}$/;
                if (!usernameRegex.test(value)) {
                    showError(field, 'username_error', 'Username must be 3-50 characters and contain only letters, numbers, and underscores');
                }
            }
            break;
        case 'password':
            if (!value) {
                showError(field, 'password_error', 'Please enter your password');
            } else {
                if (value.length < 8) {
                    showError(field, 'password_error', 'Password must be at least 8 characters long');
                } else if (!/(?=.*[A-Za-z])(?=.*\d)/.test(value)) {
                    showError(field, 'password_error', 'Password must contain at least one letter and one number');
                }
            }
            break;
        case 'full_name':
            if (!value) showError(field, 'full_name_error', 'Please enter your full name');
            break;
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) showError(field, 'email_error', 'Please enter a valid email address');
            break;
        case 'confirm_password':
            const password = document.getElementById('password');
            if (password && value !== password.value) showError(field, 'confirm_password_error', 'Passwords do not match');
            break;
    }
}

// Initialize real-time validation
function initializeFormValidation() {
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Clear error when user starts typing
            this.classList.remove('error');
            const errorId = this.id + '_error';
            const errorElement = document.getElementById(errorId);
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            
            // Hide alert messages when user starts typing
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });
}

// ===== PAGE INITIALIZATION =====

// Initialize login page
function initializeLoginPage() {
    // Auto-focus on username field
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.focus();
    }
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (validateLoginForm()) {
                await submitLoginForm();
            }
        });
    }
    
    // Set default login type if not already set
    const loginTypeInput = document.getElementById('loginType');
    if (loginTypeInput && !loginTypeInput.value) {
        selectLoginType('customer');
    }
}

// Initialize register page
function initializeRegisterPage() {
    // Auto-focus on first field
    const fullNameField = document.getElementById('full_name');
    if (fullNameField) {
        fullNameField.focus();
    }
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize form submission
    const registerForm = document.getElementById('registrationForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (validateRegisterForm()) {
                await submitRegisterForm();
            }
        });
    }
    
    // Set default role if not already set
    const roleInput = document.getElementById('roleInput');
    if (roleInput && !roleInput.value) {
        selectRole('customer');
    }
}

// Auto-detect page type and initialize
function initializeAuthPage() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registrationForm');
    
    if (loginForm) {
        initializeLoginPage();
    } else if (registerForm) {
        initializeRegisterPage();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAuthPage();
});

// Export functions for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        // Common functions
        showAlert,
        showError,
        togglePassword,
        
        // Login functions
        selectLoginType,
        validateLoginForm,
        submitLoginForm,
        initializeLoginPage,
        
        // Register functions
        selectRole,
        validateRegisterForm,
        submitRegisterForm,
        initializeRegisterPage,
        
        // Main initialization
        initializeAuthPage
    };
}