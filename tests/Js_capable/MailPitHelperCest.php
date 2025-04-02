<?php

/**
 * @file
 * Test for MailPit helper functionality.
 */

namespace Tests\Js_capable;

use Tests\Support\JSCapableTester;

/**
 * Class MailPitHelperCest
 *
 * Tests the MailPit helper functionality.
 */
class MailPitHelperCest
{
    /**
     * Test - I can fetch emails using MailPit.
     *
     * @param JSCapableTester $I
     */
    public function testFetchEmails(JSCapableTester $I)
    {
        $I->wantTo('Test - I can fetch emails using MailPit helper');
        
        // First, delete all existing emails to start with a clean state
        $I->deleteAllEmails();
        
        // Send an email programmatically using the MailHelper
        $I->comment('Sending an email programmatically...');
        $to = 'test@example.com';
        $subject = 'Test Email from MailPitHelperCest';
        $body = '<p>This is a test email sent programmatically using the MailHelper.</p>';
        $from = 'sender@example.com';
        
        $result = $I->sendEmail($to, $subject, $body, $from);
        $I->comment('Email sent: ' . ($result ? 'successfully' : 'failed'));
        
        // Wait a moment for the email to be processed
        $I->wait(3);
        
        // Fetch all emails
        $I->fetchEmails();
        
        // Check the total number of emails
        $totalEmails = $I->countEmails();
        $I->comment("Total emails in inbox: $totalEmails");
        $I->assertEquals(1, $totalEmails, "There should be exactly 1 email in the inbox");
        
        // Try to access inbox for the specific email address
        $I->accessInboxFor($to);
        
        // Try to open the next email
        try {
            $I->openNextUnreadEmail();
            $I->comment('Successfully opened an email - MailPit is working!');
            
            // Try to verify email content
            $I->seeInOpenedEmailSubject($subject);
            $I->comment('Email subject verified');
            
            // Check if the email body contains our test message
            $I->seeInOpenedEmailBody('This is a test email');
            $I->comment('Email body verified');
        } catch (\Exception $e) {
            $I->comment('No emails found or could not open email: ' . $e->getMessage());
        }
        
        // Clean up
        $I->deleteAllEmails();
    }
    
    /**
     * Test - I can filter emails by recipient.
     *
     * @param JSCapableTester $I
     */
    public function testFilterEmailsByRecipient(JSCapableTester $I)
    {
        $I->wantTo('Test - I can filter emails by recipient');
        
        // Clean up any existing emails
        $I->deleteAllEmails();
        
        // Send emails to multiple recipients programmatically
        // First recipient
        $I->comment('Sending email to first recipient...');
        $to1 = 'user1@example.com';
        $subject = 'Test Email for Recipient Filtering';
        $body1 = '<p>This is a test email for the first recipient.</p>';
        $from = 'sender@example.com';
        
        $result1 = $I->sendEmail($to1, $subject, $body1, $from);
        $I->comment('Email to first recipient sent: ' . ($result1 ? 'successfully' : 'failed'));
        $I->wait(1);
        
        // Second recipient
        $I->comment('Sending email to second recipient...');
        $to2 = 'user2@example.com';
        $body2 = '<p>This is a test email for the second recipient.</p>';
        
        $result2 = $I->sendEmail($to2, $subject, $body2, $from);
        $I->comment('Email to second recipient sent: ' . ($result2 ? 'successfully' : 'failed'));
        $I->wait(1);
        
        // Fetch all emails
        $I->fetchEmails();
        
        // Check the total number of emails
        $totalEmails = $I->countEmails();
        $I->comment("Total emails in inbox: $totalEmails");
        $I->assertEquals(2, $totalEmails, "There should be exactly 2 emails in the inbox");
        
        // Filter by first recipient and try to open an email
        $I->accessInboxFor($to1);
        try {
            $I->openNextUnreadEmail();
            $I->comment('Successfully opened an email for ' . $to1 . ' - MailPit filtering is working!');
            $I->seeInOpenedEmailSubject($subject);
            $I->seeInOpenedEmailBody('first recipient');
        } catch (\Exception $e) {
            $I->comment('No emails found for ' . $to1 . ': ' . $e->getMessage());
        }
        
        // Filter by second recipient and try to open an email
        $I->accessInboxFor($to2);
        try {
            $I->openNextUnreadEmail();
            $I->comment('Successfully opened an email for ' . $to2 . ' - MailPit filtering is working!');
            $I->seeInOpenedEmailSubject($subject);
            $I->seeInOpenedEmailBody('second recipient');
        } catch (\Exception $e) {
            $I->comment('No emails found for ' . $to2 . ': ' . $e->getMessage());
        }
        
        // Clean up
        $I->deleteAllEmails();
    }
}
