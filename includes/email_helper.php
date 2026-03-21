<?php
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailHelper {
    private $mailer;
    private $defaultFromEmail;
    private $defaultFromName;

    public function __construct() {
        try {
            // Log SMTP configuration for debugging
            error_log('SMTP Config - Host: ' . SMTP_HOST . ', Port: ' . SMTP_PORT);
            
            $dsn = sprintf(
                'smtp://%s:%s@%s:%d',
                urlencode(SMTP_USER),
                urlencode(SMTP_PASS),
                SMTP_HOST,
                SMTP_PORT
            );

            $transport = Transport::fromDsn($dsn);
            $this->mailer = new Mailer($transport);
            $this->defaultFromEmail = SMTP_FROM_EMAIL;
            $this->defaultFromName = SMTP_FROM_NAME;
        } catch (\Exception $e) {
            error_log('Mailer initialization failed: ' . $e->getMessage());
            throw new \Exception('Email system initialization failed: ' . $e->getMessage());
        }
    }

    public function sendEmail($to, $subject, $htmlBody, $recipientName = '') {
        try {
            $email = (new Email())
                ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
                ->to(new Address($to))
                ->subject($subject)
                ->html($htmlBody)
                ->text(strip_tags($htmlBody));

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to send email to ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }
}
