/**
 * OTP Handler for HospiLink Registration
 * Manages OTP modal, verification, and user experience
 */

let otpTimer;
let timeLeft = 300; // 5 minutes in seconds
let currentEmail = '';
let registrationData = {};
let resendsRemaining = 3;

// Initialize OTP functionality
document.addEventListener('DOMContentLoaded', function() {
    // Intercept registration form submission
    const signupForm = document.querySelector('#signup form');
    if (signupForm) {
        signupForm.addEventListener('submit', handleRegistrationSubmit);
    }
    
    // Setup OTP input auto-focus and validation
    setupOTPInputs();
    
    // Setup button handlers
    document.getElementById('verifyOtpBtn').addEventListener('click', verifyOTP);
    
    // Setup success modal login button
    const successLoginBtn = document.getElementById('successLoginBtn');
    if (successLoginBtn) {
        successLoginBtn.addEventListener('click', closeSuccessModal);
    }
});

/**
 * Handle registration form submission
 */
function handleRegistrationSubmit(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(e.target);
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    // Validate passwords match
    if (password !== confirmPassword) {
        showAlert('error', 'Passwords do not match!');
        return;
    }
    
    // Validate password strength
    if (password.length < 6) {
        showAlert('error', 'Password must be at least 6 characters long!');
        return;
    }
    
    // Validate role selection
    const role = formData.get('role');
    if (!role) {
        showAlert('error', 'Please select a role!');
        return;
    }
    
    // Validate doctor fields
    if (role === 'doctor') {
        if (!formData.get('specialization') || !formData.get('department') || !formData.get('license_number')) {
            showAlert('error', 'Please fill in all doctor-specific fields!');
            return;
        }
    }
    
    // Store registration data
    registrationData = {
        firstName: formData.get('fName').trim(),
        lastName: formData.get('lName').trim(),
        email: formData.get('email').trim(),
        phone: formData.get('phone').trim(),
        role: role,
        password: password
    };
    
    // Add doctor fields if applicable
    if (role === 'doctor') {
        registrationData.specialization = formData.get('specialization').trim();
        registrationData.department = formData.get('department').trim();
        registrationData.license_number = formData.get('license_number').trim();
    }
    
    currentEmail = registrationData.email;
    
    // Show OTP modal and send OTP
    showOTPModal();
    sendOTP();
}

/**
 * Show OTP modal
 */
function showOTPModal() {
    const modal = document.getElementById('otpModal');
    const emailDisplay = document.getElementById('otpEmail');
    
    if (emailDisplay) {
        emailDisplay.textContent = currentEmail;
    }
    
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
        // Add small delay to ensure content is rendered
        setTimeout(() => {
            document.getElementById('otp1').focus();
        }, 100);
    }
    
    // Reset state
    clearOTPInputs();
    resetTimer();
    hideMessage();
}

/**
 * Send OTP to user's email
 */
async function sendOTP() {
    showLoading(true);
    hideMessage();
    
    try {
        // Log the request for debugging
        const apiUrl = 'php/otp_api.php';
        const requestData = {
            action: 'generate',
            ...registrationData
        };
        
        console.log('🔍 OTP Request Debug:', {
            url: apiUrl,
            fullUrl: new URL(apiUrl, window.location.href).href,
            method: 'POST',
            data: requestData
        });
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        console.log('✅ Response received:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        console.log('✅ Response received:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        const result = await response.json();
        
        console.log('📦 Parsed result:', result);
        
        showLoading(false);
        
        if (result.success) {
            showMessage('success', result.message);
            // Reset and start timer
            resetTimer();
            startTimer();
            document.getElementById('otp1').focus();
        } else {
            showMessage('error', result.message);
            // If email already registered, close modal and show on main form
            if (result.message.includes('already registered')) {
                setTimeout(() => {
                    closeOTPModal();
                    showAlert('error', result.message);
                }, 2000);
            }
        }
    } catch (error) {
        showLoading(false);
        console.error('❌ OTP Request Error:', {
            message: error.message,
            name: error.name,
            stack: error.stack,
            error: error
        });
        showMessage('error', 'Network error. Please check your connection and try again.');
    }
}

/**
 * Verify OTP entered by user
 */
async function verifyOTP() {
    const otp = getOTPValue();
    
    if (otp.length !== 6) {
        showMessage('error', 'Please enter the complete 6-digit OTP.');
        return;
    }
    
    console.log('🔐 Verifying OTP:', {
        email: currentEmail,
        otp: otp,
        length: otp.length
    });
    
    // CRITICAL: Stop timer FIRST to prevent "expired" message during verification
    stopTimer();
    hideMessage();
    
    disableOTPInputs(true);
    document.getElementById('verifyOtpBtn').disabled = true;
    document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    try {
        const response = await fetch('php/otp_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'verify',
                email: currentEmail,
                otp: otp
            })
        });
        
        const result = await response.json();
        
        console.log('✅ Verification result:', result);
        
        if (result.success) {
            // Stop timer
            stopTimer();
            
            // Update button to show registration in progress
            document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            hideMessage();
            
            // Now complete the registration with user data
            await completeRegistration(result.user_data);
        } else {
            showMessage('error', result.message);
            disableOTPInputs(false);
            document.getElementById('verifyOtpBtn').disabled = false;
            document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-check-circle"></i> Verify OTP';
            clearOTPInputs();
            document.getElementById('otp1').focus();
        }
    } catch (error) {
        console.error('❌ OTP Verify Error:', error);
        showMessage('error', 'Network error. Please try again.');
        disableOTPInputs(false);
        document.getElementById('verifyOtpBtn').disabled = false;
        document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-check-circle"></i> Verify OTP';
    }
}

/**
 * Complete user registration after OTP verification
 */
async function completeRegistration(userData) {
    try {
        console.log('🔄 Completing registration...', userData);
        
        const response = await fetch('php/complete_registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log('📦 Registration result:', result);
        
        if (result.success) {
            console.log('✅ User registered successfully in database');
            
            // Close OTP modal immediately
            closeOTPModal();
            
            // Show success modal
            setTimeout(() => {
                showSuccessModal();
            }, 200);
        } else {
            console.error('❌ Registration failed:', result.message);
            // Show error in OTP modal
            showMessage('error', result.message || 'Registration failed. Please try again.');
            disableOTPInputs(false);
            document.getElementById('verifyOtpBtn').disabled = false;
            document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-check-circle"></i> Verify OTP';
        }
    } catch (error) {
        console.error('❌ Registration Error:', error);
        showMessage('error', 'Registration failed. Please refresh and try again.');
        disableOTPInputs(false);
        document.getElementById('verifyOtpBtn').disabled = false;
        document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-check-circle"></i> Verify OTP';
    }
}

/**
 * Show success modal after registration
 */
function showSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
}

/**
 * Close success modal and switch to login
 */
function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    
    // Switch to login form
    document.getElementById('signup').style.display = 'none';
    document.getElementById('signIn').style.display = 'block';
}

/**
 * Setup OTP inputs with auto-focus and validation
 */
function setupOTPInputs() {
    const inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        // Handle input
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Only allow digits
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            // Move to next input
            if (value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            
            // Auto-verify when all 6 digits entered
            if (index === inputs.length - 1 && value) {
                setTimeout(() => {
                    const otp = getOTPValue();
                    if (otp.length === 6) {
                        verifyOTP();
                    }
                }, 100);
            }
        });
        
        // Handle backspace
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        // Handle paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text');
            const digits = pastedData.match(/\d/g);
            
            if (digits) {
                digits.slice(0, 6).forEach((digit, i) => {
                    if (inputs[i]) {
                        inputs[i].value = digit;
                    }
                });
                
                // Focus last filled input or verify if complete
                const lastIndex = Math.min(digits.length - 1, 5);
                inputs[lastIndex].focus();
                
                if (digits.length >= 6) {
                    setTimeout(() => verifyOTP(), 100);
                }
            }
        });
    });
}

/**
 * Get OTP value from inputs
 */
function getOTPValue() {
    const inputs = document.querySelectorAll('.otp-input');
    return Array.from(inputs).map(input => input.value.trim()).join('');
}

/**
 * Clear OTP inputs
 */
function clearOTPInputs() {
    const inputs = document.querySelectorAll('.otp-input');
    inputs.forEach(input => {
        input.value = '';
    });
}

/**
 * Disable/enable OTP inputs
 */
function disableOTPInputs(disabled) {
    const inputs = document.querySelectorAll('.otp-input');
    inputs.forEach(input => {
        input.disabled = disabled;
    });
}

/**
 * Start countdown timer
 */
function startTimer() {
    const timerElement = document.getElementById('timerText');
    const timerContainer = document.getElementById('otpTimer');
    
    console.log('⏱️ Timer started with timeLeft:', timeLeft);
    
    if (timerContainer) timerContainer.classList.remove('expired');
    
    otpTimer = setInterval(() => {
        timeLeft--;
        
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        if (timerElement) {
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        if (timeLeft <= 0) {
            stopTimer();
            if (timerElement) timerElement.textContent = 'Expired';
            if (timerContainer) timerContainer.classList.add('expired');
            showMessage('error', 'OTP expired. Please close and try again.');
            disableOTPInputs(true);
            document.getElementById('verifyOtpBtn').disabled = true;
        }
    }, 1000);
}

/**
 * Stop timer
 */
function stopTimer() {
    if (otpTimer) {
        clearInterval(otpTimer);
        otpTimer = null;
    }
}

/**
 * Reset timer
 */
function resetTimer() {
    stopTimer();
    timeLeft = 300;
    const timerElement = document.getElementById('timerText');
    const timerContainer = document.getElementById('otpTimer');
    const verifyBtn = document.getElementById('verifyOtpBtn');
    
    if (timerElement) {
        timerElement.textContent = '5:00';
    }
    if (timerContainer) {
        timerContainer.classList.remove('expired');
    }
    
    disableOTPInputs(false);
    if (verifyBtn) verifyBtn.disabled = false;
}

/**
 * Show loading state
 */
function showLoading(show) {
    const loading = document.getElementById('otpLoading');
    const inputSection = document.getElementById('otpInputSection');
    
    if (show) {
        loading.style.display = 'block';
        inputSection.style.display = 'none';
    } else {
        loading.style.display = 'none';
        inputSection.style.display = 'block';
    }
}

/**
 * Show message in modal
 */
function showMessage(type, message) {
    const messageEl = document.getElementById('otpMessage');
    messageEl.className = 'otp-message ' + type;
    messageEl.textContent = message;
    messageEl.style.display = 'block';
}

/**
 * Hide message
 */
function hideMessage() {
    const messageEl = document.getElementById('otpMessage');
    messageEl.style.display = 'none';
}

/**
 * Close OTP modal
 */
function closeOTPModal() {
    const modal = document.getElementById('otpModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    stopTimer();
    clearOTPInputs();
    hideMessage();
    resendsRemaining = 3;
    const resendCountEl = document.getElementById('resendCount');
    if (resendCountEl) {
        resendCountEl.textContent = '3';
    }
}

/**
 * Show alert on main page
 */
function showAlert(type, message) {
    alert(message);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('otpModal');
    if (event.target === modal) {
        // Don't allow closing modal by clicking outside during registration
        showMessage('info', 'Please complete email verification to continue registration.');
    }
});
