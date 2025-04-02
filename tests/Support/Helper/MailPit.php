<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Exception;
use GuzzleHttp\Client;
use Codeception\Module\TestsEmails;

/**
 * MailPit helper for testing emails with Codeception.
 * 
 * This class uses the TestsEmails trait from koehnlein/codeception-email-mailpit package.
 */
class MailPit extends Module
{
    use TestsEmails;

    /**
     * Codeception exposed variables.
     */
    protected array $config = [
        'url',
        'port',
        'guzzleRequestOptions',
        'deleteEmailsAfterScenario',
        'timeout',
    ];

    /**
     * Codeception required variables.
     */
    protected array $requiredFields = ['url', 'port'];

    /**
     * HTTP Client to interact with MailPit.
     */
    protected Client $mailpit;

    /**
     * Raw email header data converted to JSON.
     */
    protected array $fetchedEmails = [];

    /**
     * Currently selected set of email headers to work with.
     */
    protected array $currentInbox = [];

    /**
     * Starts as the same data as the current inbox, but items are removed as they're used.
     */
    protected array $unreadInbox = [];

    /**
     * Contains the currently open email on which test operations are conducted.
     */
    protected mixed $openedEmail;

    public function _initialize(): void
    {
        $url = trim($this->config['url'], '/') . ':' . $this->config['port'];

        $config = ['base_uri' => $url, 'timeout' => $this->config['timeout'] ?? 1];
        if (isset($this->config['guzzleRequestOptions']) && is_array($this->config['guzzleRequestOptions'])) {
            foreach ($this->config['guzzleRequestOptions'] as $option => $value) {
                $config[$option] = $value;
            }
        }

        $this->mailpit = new Client($config);
    }

    /**
     * Method executed after each scenario.
     */
    public function _after(TestInterface $test): void
    {
        if (isset($this->config['deleteEmailsAfterScenario']) && $this->config['deleteEmailsAfterScenario']) {
            $this->deleteAllEmails();
        }
    }

    /**
     * Fetch Emails.
     *
     * Accessible from tests, fetches all emails
     */
    public function fetchEmails(): void
    {
        $this->fetchedEmails = [];

        try {
            $response = $this->mailpit->request('GET', '/api/v1/messages');
            $listMessages = json_decode($response->getBody(), false);
            $this->fetchedEmails = $listMessages->messages;
        } catch (Exception $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
        $this->sortEmails($this->fetchedEmails);

        // by default, work on all emails
        $this->setCurrentInbox($this->fetchedEmails);
    }

    /**
     * Access Inbox For *.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxFor(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if (str_contains($email->To[0]->Address, $address)) {
                $inbox[] = $email;
            }

            if (isset($email->Cc) && in_array($address, $email->Cc, true)) {
                $inbox[] = $email;
            }

            if (isset($email->Bcc) && in_array($address, $email->Bcc, true)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Access Inbox For To.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxForTo(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if (str_contains($email->To[0]->Address, $address)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Access Inbox For CC.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxForCc(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if (isset($email->Cc) && in_array($address, $email->Cc, true)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Access Inbox For BCC.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxForBcc(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if (isset($email->Bcc) && in_array($address, $email->Bcc, true)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Delete All Emails.
     *
     * Accessible from tests, deletes all emails
     */
    public function deleteAllEmails(): void
    {
        try {
            $this->mailpit->request('DELETE', '/api/v1/messages');
        } catch (Exception $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Open Next Unread Email.
     *
     * Pops the most recent unread email and assigns it as the email to conduct tests on
     */
    public function openNextUnreadEmail(): void
    {
        $this->openedEmail = $this->getMostRecentUnreadEmail();
    }

    /**
     * Get Opened Email.
     *
     * Main method called by the tests, providing either the currently open email or the next unread one
     *
     * @param bool $fetchNextUnread Goes to the next Unread Email
     *
     * @return mixed Returns a JSON encoded Email
     */
    protected function getOpenedEmail($fetchNextUnread = false): mixed
    {
        if ($fetchNextUnread || $this->openedEmail === null) {
            $this->openNextUnreadEmail();
        }

        return $this->openedEmail;
    }

    /**
     * Get Most Recent Unread Email.
     *
     * Pops the most recent unread email, fails if the inbox is empty
     *
     * @return mixed Returns a JSON encoded Email
     */
    protected function getMostRecentUnreadEmail(): mixed
    {
        if (count($this->unreadInbox) === 0) {
            $this->fail('Unread Inbox is Empty');
        }

        $email = array_shift($this->unreadInbox);

        return $this->getFullEmail($email->ID);
    }

    /**
     * Get Full Email.
     *
     * Returns the full content of an email
     *
     * @param string $id ID from the header
     *
     * @return mixed Returns a JSON encoded Email
     */
    protected function getFullEmail(string $id): mixed
    {
        try {
            $response = $this->mailpit->request('GET', "/api/v1/message/{$id}");

            return json_decode($response->getBody(), false);
        } catch (Exception $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Get Email Subject.
     *
     * Returns the subject of an email
     *
     * @param mixed $email Email
     *
     * @return string Subject
     */
    protected function getEmailSubject(mixed $email): string
    {
        return $this->getDecodedEmailProperty($email->Subject);
    }

    /**
     * Get Email Body.
     *
     * Returns the body of an email
     *
     * @param mixed $email Email
     *
     * @return string Body
     */
    protected function getEmailBody(mixed $email): string
    {
        return $this->getDecodedEmailProperty($email->HTML);
    }

    /**
     * Get Email To.
     *
     * Returns the string containing the persons included in the To field
     *
     * @param mixed $email Email
     *
     * @return string To
     */
    protected function getEmailTo(mixed $email): string
    {
        return $this->getDecodedEmailProperty($email->To[0]->Address);
    }

    /**
     * Get Email CC.
     *
     * Returns the string containing the persons included in the CC field
     *
     * @param mixed $email Email
     *
     * @return string CC
     */
    protected function getEmailCC(mixed $email): string
    {
        $emailCc = '';
        if (isset($email->Cc[0])) {
            $emailCc = $this->getDecodedEmailProperty($email->Cc[0]->Address);
        }

        return $emailCc;
    }

    /**
     * Get Email BCC.
     *
     * Returns the string containing the persons included in the BCC field
     *
     * @param mixed $email Email
     *
     * @return string BCC
     */
    protected function getEmailBCC(mixed $email): string
    {
        $emailBcc = '';
        if (isset($email->Bcc[0])) {
            $emailBcc = $this->getDecodedEmailProperty($email->Bcc[0]->Address);
        }

        return $emailBcc;
    }

    /**
     * Get Email Recipients.
     *
     * Returns the string containing all of the recipients, such as To, CC and if provided BCC
     *
     * @param mixed $email Email
     *
     * @return string Recipients
     */
    protected function getEmailRecipients(mixed $email): string
    {
        $recipients = [];
        if (isset($email->To)) {
            $recipients[] = $this->getEmailTo($email);
        }
        if (isset($email->Cc)) {
            $recipients[] = $this->getEmailCC($email);
        }
        if (isset($email->Bcc)) {
            $recipients[] = $this->getEmailBCC($email);
        }

        $recipients = implode(' ', $recipients);

        return $recipients;
    }

    /**
     * Get Email Sender.
     *
     * Returns the string containing the sender of the email
     *
     * @param mixed $email Email
     *
     * @return string Sender
     */
    protected function getEmailSender(mixed $email): string
    {
        return $this->getDecodedEmailProperty($email->From->Address);
    }

    /**
     * Get Email Reply To.
     *
     * Returns the string containing the address to reply to
     *
     * @param mixed $email Email
     *
     * @return string ReplyTo
     */
    protected function getEmailReplyTo(mixed $email): string
    {
        return $this->getDecodedEmailProperty($email->ReplyTo[0]);
    }

    /**
     * Returns the decoded email property.
     *
     * @param mixed $property
     *
     * @return string
     */
    protected function getDecodedEmailProperty(mixed $property): string
    {
        if ($property === '') {
            return $property;
        }

        if (stripos($property, '=?utf-8?Q?') !== false) {
            if (extension_loaded('iconv')) {
                return iconv_mime_decode($property);
            }
            if (extension_loaded('mbstring')) {
                return mb_decode_mimeheader($property);
            }
        }

        return $property;
    }

    /**
     * Set Current Inbox.
     *
     * Sets the current inbox to work on, also create a copy of it to handle unread emails
     *
     * @param array $inbox Inbox
     */
    protected function setCurrentInbox(array $inbox): void
    {
        $this->currentInbox = $inbox;
        $this->unreadInbox = $inbox;
    }

    /**
     * Get Current Inbox.
     *
     * Returns the complete current inbox
     *
     * @return array Current Inbox
     */
    protected function getCurrentInbox(): array
    {
        return $this->currentInbox;
    }

    /**
     * Get Unread Inbox.
     *
     * Returns the inbox containing unread emails
     *
     * @return array Unread Inbox
     */
    protected function getUnreadInbox(): array
    {
        return $this->unreadInbox;
    }

    /**
     * Count Emails.
     *
     * Returns the number of emails in the current inbox
     *
     * @return int Number of emails
     */
    public function countEmails(): int
    {
        return count($this->currentInbox);
    }

    /**
     * Sort Emails.
     *
     * Sorts the inbox based on the timestamp
     *
     * @param array $inbox Inbox to sort
     */
    protected function sortEmails(array $inbox): void
    {
        usort($inbox, [$this, 'sortEmailsByCreationDatePredicate']);
    }

    /**
     * Get Email To.
     *
     * Returns the string containing the persons included in the To field
     *
     * @param mixed $emailA Email
     * @param mixed $emailB Email
     *
     * @return int Which email should go first
     */
    protected static function sortEmailsByCreationDatePredicate(mixed $emailA, mixed $emailB): int
    {
        $sortKeyA = $emailA->Created;
        $sortKeyB = $emailB->Created;

        return ($sortKeyA > $sortKeyB) ? -1 : 1;
    }
}
