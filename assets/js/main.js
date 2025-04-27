document.addEventListener('DOMContentLoaded', function() {
    // Form validation for signup
    const signupForms = document.querySelectorAll('.needs-validation');
    
    // Handle form validation
    Array.from(signupForms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password strength meter
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrength = document.getElementById('password-strength');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (passwordStrength) {
                const strength = checkPasswordStrength(this.value);
                updatePasswordStrengthUI(strength, passwordStrength);
            }
            
            // Check password confirmation match
            if (confirmPasswordInput && confirmPasswordInput.value) {
                checkPasswordMatch(passwordInput, confirmPasswordInput);
            }
        });
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput) {
                checkPasswordMatch(passwordInput, this);
            }
        });
    }
    
    // Rating stars interactive behavior
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingValue = document.getElementById('rating-value');
    
    if (ratingStars.length > 0 && ratingValue) {
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                ratingValue.value = value;
                
                // Update visual
                ratingStars.forEach(s => {
                    const starValue = s.getAttribute('data-value');
                    if (starValue <= value) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
            
            // Hover effects
            star.addEventListener('mouseenter', function() {
                const value = this.getAttribute('data-value');
                
                ratingStars.forEach(s => {
                    const starValue = s.getAttribute('data-value');
                    if (starValue <= value) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const currentRating = ratingValue.value || 0;
                
                ratingStars.forEach(s => {
                    const starValue = s.getAttribute('data-value');
                    if (starValue <= currentRating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });
    }
    
    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});

// Check password strength
function checkPasswordStrength(password) {
    // Initialize score
    let score = 0;
    
    // If password is empty, return 0
    if (password.length === 0) return score;
    
    // Length check
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    
    // Complexity checks
    if (/[a-z]/.test(password)) score += 1; // Lowercase
    if (/[A-Z]/.test(password)) score += 1; // Uppercase
    if (/\d/.test(password)) score += 1;    // Numbers
    if (/[^a-zA-Z0-9]/.test(password)) score += 1; // Special characters
    
    return score;
}

// Update password strength UI
function updatePasswordStrengthUI(strength, element) {
    // Remove all existing classes
    element.className = 'password-strength progress-bar';
    
    // Set width
    element.style.width = (strength * 16.66) + '%';
    
    // Set color based on strength
    if (strength <= 2) {
        element.classList.add('bg-danger');
        element.textContent = 'Fraca';
    } else if (strength <= 4) {
        element.classList.add('bg-warning');
        element.textContent = 'Média';
    } else {
        element.classList.add('bg-success');
        element.textContent = 'Forte';
    }
}

// Check if passwords match
function checkPasswordMatch(password1, password2) {
    const feedbackElement = document.getElementById('password-match-feedback');
    
    if (!feedbackElement) return;
    
    if (password1.value === password2.value) {
        password2.setCustomValidity('');
        feedbackElement.textContent = 'Senhas conferem!';
        feedbackElement.className = 'valid-feedback';
    } else {
        password2.setCustomValidity('Passwords do not match');
        feedbackElement.textContent = 'Senhas não conferem!';
        feedbackElement.className = 'invalid-feedback';
    }
}

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.querySelector(`[data-toggle="${inputId}"] i`);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
