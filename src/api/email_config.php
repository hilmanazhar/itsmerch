<?php
/**
 * Email SMTP Configuration
 * 
 * Uses Brevo (formerly Sendinblue) SMTP - Free tier: 300 emails/day
 * No credit card required!
 * 
 * Setup Instructions:
 * 1. Go to https://www.brevo.com/ and create free account
 * 2. Go to SMTP & API menu: https://app.brevo.com/settings/keys/smtp
 * 3. Copy your SMTP login and Master password (key)
 * 4. Fill in the values below
 */

// Brevo SMTP Settings
define('SMTP_ENABLED', true);  // Set to false to disable SMTP and use logging only
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');  // tls or ssl
define('SMTP_AUTH', true);

// =====================================================
// IMPORTANT: Fill in your Brevo credentials below!
// =====================================================
define('SMTP_USERNAME', 'your-brevo-email@example.com');  // Your Brevo login email
define('SMTP_PASSWORD', 'your-smtp-master-password');      // Your SMTP key from Brevo dashboard

// Sender Information
define('MAIL_FROM_EMAIL', 'noreply@itsmerch.id');  // Use a valid email you control
define('MAIL_FROM_NAME', 'myITS Merchandise');

// Alternative Free SMTP Services (uncomment to use):

/*
// Gmail SMTP (requires App Password)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');
*/

/*
// SendPulse SMTP (12,000 emails/month free)
define('SMTP_HOST', 'smtp-pulse.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-sendpulse-email');
define('SMTP_PASSWORD', 'your-sendpulse-smtp-password');
*/

/*
// Mailtrap SMTP (for testing only)
define('SMTP_HOST', 'sandbox.smtp.mailtrap.io');
define('SMTP_PORT', 2525);
define('SMTP_USERNAME', 'your-mailtrap-username');
define('SMTP_PASSWORD', 'your-mailtrap-password');
*/
?>
