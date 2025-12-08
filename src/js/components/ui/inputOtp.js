// This file defines the Input OTP component, used for one-time password entry.
document.addEventListener('DOMContentLoaded', function () {
    const inputOtp = document.getElementById('inputOtp');
    const otpInputs = document.querySelectorAll('.otp-input');

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function () {
            if (this.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            } else if (this.value.length === 0 && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    inputOtp.addEventListener('submit', function (e) {
        e.preventDefault();
        const otpValue = Array.from(otpInputs).map(input => input.value).join('');
        console.log('OTP Entered:', otpValue);
        // Add further processing for the OTP value here
    });
});