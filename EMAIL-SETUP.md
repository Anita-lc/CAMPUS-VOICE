# Email Notification System Setup

This guide will help you set up the email notification system for the Campus Voice feedback platform.

## Prerequisites

1. PHP 7.4 or higher
2. Composer (PHP package manager)
3. Gmail account (for SMTP)

## Installation

1. **Install Dependencies**
   Run the following command in your project root directory:
   ```bash
   composer require phpmailer/phpmailer
   ```

2. **Configure Email Settings**
   Edit `config/email-config.php` with your Gmail credentials:
   ```php
   return [
       'smtp' => [
           'host' => 'smtp.gmail.com',
           'port' => 587,
           'username' => 'your-email@gmail.com', // Your Gmail
           'password' => 'your-app-password',    // App Password (not your Gmail password)
           'encryption' => 'tls',
           'from_email' => 'your-email@gmail.com',
           'from_name' => 'Campus Voice Admin'
       ],
       // ... rest of the config
   ];
   ```

## Gmail Setup

1. **Enable 2-Step Verification**
   - Go to your [Google Account](https://myaccount.google.com/)
   - Navigate to "Security"
   - Under "Signing in to Google," select "2-Step Verification"
   - Follow the prompts to enable it

2. **Generate App Password**
   - Go to [App Passwords](https://myaccount.google.com/apppasswords)
   - Select "Mail" as the app
   - Select "Other (Custom name)" as the device
   - Enter "Campus Voice" as the name and click "Generate"
   - Copy the 16-character password (you'll only see it once)

3. **Update Configuration**
   - Paste the generated App Password in `config/email-config.php`
   - Make sure to use the same email in both `username` and `from_email`

## Testing the Email System

1. Open `test-email.php` in your browser:
   ```
   http://localhost/CAMPUS%20VOICE/test-email.php
   ```

2. The test page will:
   - Check if PHPMailer is installed
   - Attempt to send three test emails:
     1. Feedback Confirmation
     2. Admin Response
     3. Status Update
   - Display the results of each test
   - Show the current SMTP configuration (with password masked)

## Troubleshooting

### Common Issues

1. **PHPMailer not found**
   - Run `composer install` in your project directory
   - Make sure the `vendor` directory exists and is readable

2. **SMTP Connection Failed**
   - Verify your Gmail credentials in `email-config.php`
   - Make sure you're using an App Password, not your Gmail password
   - Check if your server allows outbound connections on port 587
   - Try disabling your firewall temporarily for testing

3. **Emails going to spam**
   - Check your spam folder
   - Make sure your `from_email` matches the Gmail account
   - Consider setting up SPF and DKIM for better deliverability

## Integration with Your Application

### 1. Sending Feedback Confirmation
```php
$emailSender = new EmailSender($db); // $db is your database connection
$emailSender->sendFeedbackConfirmation(
    $userEmail,    // User's email
    $userName,     // User's name
    $feedbackId    // Feedback ID
);
```

### 2. Sending Admin Response
```php
$emailSender = new EmailSender($db);
$emailSender->sendAdminResponse(
    $feedback['user_email'],  // Recipient email
    $feedback['user_name'],   // Recipient name
    $adminName,              // Admin's name
    $responseText,           // The response message
    $feedbackId              // Feedback ID
);
```

### 3. Sending Status Updates
```php
$emailSender = new EmailSender($db);
$emailSender->sendStatusUpdate(
    $feedback['user_email'],  // Recipient email
    $feedback['user_name'],   // Recipient name
    $newStatus,              // New status (e.g., 'In Progress', 'Resolved')
    $feedbackId              // Feedback ID
);
```

## Security Notes

1. Never commit `config/email-config.php` to version control
2. Add it to your `.gitignore` file:
   ```
   /config/email-config.php
   ```
3. For production, consider using environment variables for sensitive data
4. The system includes rate limiting to prevent abuse (10 emails per hour per user by default)

## Customizing Email Templates

You can modify the HTML email templates in the `templates/emails/` directory:

- `feedback-confirmation.html` - Sent when user submits feedback
- `admin-response.html` - Sent when admin responds to feedback
- `status-update.html` - Sent when feedback status changes

Each template supports the following variables:
- `{{user_name}}` - Recipient's name
- `{{feedback_id}}` - The feedback ID
- Additional variables specific to each template

## Support

For additional help, please contact your system administrator or open an issue in the project repository.
