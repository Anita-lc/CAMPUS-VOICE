<?php
require_once __DIR__ . '/../config/email-config.php';

class EmailSender {
    private $config;
    private $db;
    private $smtpConfig;
    
    public function __construct($db = null) {
        $this->config = require __DIR__ . '/../config/email-config.php';
        $this->smtpConfig = $this->config['smtp'];
        $this->db = $db;
        
        // Set default timezone
        date_default_timezone_set('UTC');
    }
    
    private function checkRateLimit($email) {
        if (!$this->db) return true; // Skip rate limiting if no DB connection
        
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM email_logs 
             WHERE email = ? AND created_at > NOW() - INTERVAL ? SECOND"
        );
        $timeWindow = $this->config['rate_limit']['time_window'];
        $stmt->execute([$email, $timeWindow]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] < $this->config['rate_limit']['max_emails'];
    }
    
    private function logEmail($email, $subject, $status) {
        if (!$this->db) return;
        
        $stmt = $this->db->prepare(
            "INSERT INTO email_logs (email, subject, status, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$email, $subject, $status]);
    }
    
    private function sendEmail($to, $subject, $body, $isHtml = true) {
        try {
            if (!$this->checkRateLimit($to)) {
                throw new Exception("Rate limit exceeded for $to");
            }
            
            // Basic headers
            $headers = [];
            $from = $this->smtpConfig['from_name'] . ' <' . $this->smtpConfig['from_email'] . '>';
            $headers[] = 'From: ' . $from;
            $headers[] = 'Reply-To: ' . $this->smtpConfig['reply_to'];
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            // For HTML emails
            if ($isHtml) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
                
                // Add proper email formatting
                $body = "<!DOCTYPE html>
                <html>
                <head>
                    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
                    <title>" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</title>
                </head>
                <body>
                    " . $body . "
                </body>
                </html>";
            }
            
            // Set additional parameters for mail()
            $params = "-f" . $this->smtpConfig['from_email'];
            
            // Send the email
            $result = @mail($to, $subject, $body, implode("\r\n", $headers), $params);
            
            if ($result) {
                $this->logEmail($to, $subject, 'sent');
                return true;
            } else {
                $error = error_get_last();
                throw new Exception($error['message'] ?? 'Failed to send email. Check your server mail configuration.');
            }
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            if ($this->db) {
                $this->logEmail($to, $subject, 'failed: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    public function sendFeedbackConfirmation($userEmail, $userName, $feedbackId) {
        $subject = "Feedback Received - Campus Voice";
        $template = file_get_contents(__DIR__ . '/../templates/emails/feedback-confirmation.html');
        
        // Replace placeholders
        $body = str_replace(
            ['{{user_name}}', '{{feedback_id}}'],
            [htmlspecialchars($userName), $feedbackId],
            $template
        );
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
    
    public function sendAdminResponse($userEmail, $userName, $adminName, $response, $feedbackId) {
        $subject = "Update on Your Feedback #$feedbackId - Campus Voice";
        $template = file_get_contents(__DIR__ . '/../templates/emails/admin-response.html');
        
        // Replace placeholders
        $body = str_replace(
            ['{{user_name}}', '{{admin_name}}', '{{response}}', '{{feedback_id}}'],
            [
                htmlspecialchars($userName),
                htmlspecialchars($adminName),
                nl2br(htmlspecialchars($response)),
                $feedbackId
            ],
            $template
        );
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
    
    public function sendStatusUpdate($userEmail, $userName, $status, $feedbackId) {
        $subject = "Feedback #$feedbackId Status Update - Campus Voice";
        $template = file_get_contents(__DIR__ . '/../templates/emails/status-update.html');
        
        // Replace placeholders
        $body = str_replace(
            ['{{user_name}}', '{{status}}', '{{feedback_id}}'],
            [htmlspecialchars($userName), htmlspecialchars($status), $feedbackId],
            $template
        );
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
}
?>
