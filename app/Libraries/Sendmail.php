<?php

namespace App\Libraries;

use App\Models\MessageModel;
use InvalidArgumentException;
use RuntimeException;

/**
 * Stores an outbound email message in the local database via MessageModel.
 *
 * Uses fluent setters to build the message before calling send().
 * Validation is performed in each setter, so errors surface early
 * with a descriptive InvalidArgumentException.
 *
 * @example
 * $result = (new \App\Libraries\Sendmail())
 *     ->setFrom('sender@example.com')
 *     ->setTo('recipient@example.com')
 *     ->setSubject('Hello')
 *     ->setBody('<p>Hi there</p>')
 *     ->setMailtype(\App\Libraries\Sendmail::MAILTYPE_HTML)
 *     ->send();
 */
class Sendmail
{
    public const MAILTYPE_TEXT = 'text';
    public const MAILTYPE_HTML = 'html';

    private string $from     = '';
    private string $to       = '';
    private string $subject  = '';
    private string $body     = '';
    private string $mailtype = self::MAILTYPE_TEXT;
    private string $cc       = '';
    private string $bcc      = '';

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setFrom(string $from): static
    {
        $from = trim($from);
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid 'from' email address: {$from}");
        }
        $this->from = $from;
        return $this;
    }

    public function setTo(string $to): static
    {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid 'to' email address: {$to}");
        }
        $this->to = $to;
        return $this;
    }

    public function setSubject(string $subject): static
    {
        $subject = trim($subject);
        if ($subject === '') {
            throw new InvalidArgumentException("Subject cannot be empty.");
        }
        $this->subject = $subject;
        return $this;
    }

    public function setBody(string $body): static
    {
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException("Body cannot be empty.");
        }
        $this->body = $body;
        return $this;
    }

    /**
     * @param string $mailtype  Use Sendmail::MAILTYPE_TEXT or Sendmail::MAILTYPE_HTML
     */
    public function setMailtype(string $mailtype): static
    {
        $mailtype = strtolower(trim($mailtype));
        if (!in_array($mailtype, [self::MAILTYPE_TEXT, self::MAILTYPE_HTML], true)) {
            throw new InvalidArgumentException("Invalid mailtype '{$mailtype}'. Must be 'text' or 'html'.");
        }
        $this->mailtype = $mailtype;
        return $this;
    }

    public function setCc(string $cc): static
    {
        $cc = trim($cc);
        if ($cc !== '' && !filter_var($cc, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid CC email address: {$cc}");
        }
        $this->cc = $cc;
        return $this;
    }

    public function setBcc(string $bcc): static
    {
        $bcc = trim($bcc);
        if ($bcc !== '' && !filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid BCC email address: {$bcc}");
        }
        $this->bcc = $bcc;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getFrom(): string    { return $this->from; }
    public function getTo(): string      { return $this->to; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): string    { return $this->body; }
    public function getMailtype(): string { return $this->mailtype; }
    public function getCc(): string      { return $this->cc; }
    public function getBcc(): string     { return $this->bcc; }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * Send the email. All required fields must be set beforehand.
     *
     * @throws RuntimeException if a required field has not been set.
     * @return array ['success' => true, 'id' => int] on success, or ['success' => false, 'errors' => array] on failure.
     */
    public function send(): array
    {
        foreach (['from', 'to', 'subject', 'body'] as $field) {
            if ($this->$field === '') {
                throw new RuntimeException("Required field '{$field}' has not been set.");
            }
        }

        $data = [
            'from'     => $this->from,
            'to'       => $this->to,
            'subject'  => $this->subject,
            'body'     => $this->body,
            'mailtype' => $this->mailtype,
            'domain'   => $_SERVER['HTTP_HOST'] ?? 'localhost',
        ];

        if ($this->cc !== '') {
            $data['cc'] = $this->cc;
        }
        if ($this->bcc !== '') {
            $data['bcc'] = $this->bcc;
        }
        $model = new MessageModel();
        $id    = $model->insert($data, true);

        if ($id === false) {
            return ['success' => false, 'errors' => $model->errors()];
        }

        return ['success' => true, 'id' => $id];
    }

    /**
     * Reset all fields back to their defaults so the instance can be reused.
     */
    public function reset(): static
    {
        $this->from     = '';
        $this->to       = '';
        $this->subject  = '';
        $this->body     = '';
        $this->mailtype = self::MAILTYPE_TEXT;
        $this->cc       = '';
        $this->bcc      = '';
        return $this;
    }
}
