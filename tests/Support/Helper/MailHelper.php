<?php

namespace Tests\Support\Helper;

use Codeception\Module;

/**
 * Helper for sending emails programmatically in tests.
 */
class MailHelper extends Module
{
    /**
     * Send an email using PHP's mail() function.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $from Sender email address
     * @return bool Whether the email was sent successfully
     */
    public function sendEmail(string $to, string $subject, string $body, string $from = 'test@example.com'): bool
    {
        // Configure PHP mail settings for MailPit
        ini_set('SMTP', 'mailpit');
        ini_set('smtp_port', 1025);
        
        // Set up email headers
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Format the body as HTML
        $htmlBody = "<html><body>$body</body></html>";
        
        // Send the email
        $result = mail($to, $subject, $htmlBody, $headers);
        
        // Log the result
        if ($result) {
            $this->debug("Email sent successfully to $to with subject: $subject");
        } else {
            $this->debug("Failed to send email to $to");
        }
        
        return $result;
    }
}
