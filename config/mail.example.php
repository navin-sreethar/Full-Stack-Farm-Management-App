<?php
/**
 * Mail Configuration — EXAMPLE FILE
 * ----------------------------------------
 * Copy this file to mail.php and fill in your real credentials.
 * mail.php is listed in .gitignore and will NOT be pushed to GitHub.
 *
 * HOW TO GET A GMAIL APP PASSWORD:
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable "2-Step Verification" if not already on
 * 3. Search for "App Passwords" on that page
 * 4. Create one → choose "Mail" + "Other (Custom)" → name it "FarmApp"
 * 5. Copy the 16-character password and paste it below
 */
return [
    'host'        => 'smtp.gmail.com',
    'port'        => 587,
    'username'    => 'your-email@gmail.com',
    'password'    => 'your-16-char-gmail-app-password',   // Gmail App Password
    'from_email'  => 'your-email@gmail.com',
    'from_name'   => 'Your App Name',
    'admin_email' => 'admin@yourdomain.com',
];
