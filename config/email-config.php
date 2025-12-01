<?php
// Email Configuration
return [
    'smtp' => [
        'host' => 'ssl://smtp.gmail.com',
        'port' => 465,
        'username' => 'your-email@gmail.com', // Your Gmail
        'password' => 'your-app-password',    // Gmail App Password (not your Gmail password)
        'encryption' => 'ssl',
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'Campus Voice',
        'reply_to' => 'your-email@gmail.com'
    ],
    'rate_limit' => [
        'max_emails' => 10,
        'time_window' => 3600 // 1 hour in seconds
    ]
];
?>
