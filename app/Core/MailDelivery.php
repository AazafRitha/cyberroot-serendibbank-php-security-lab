<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class MailDelivery
{
    public static function sendSignInCode(string $email, string $code): void
    {
        $transport = strtolower((string) (getenv('MAIL_TRANSPORT') ?: 'outbox'));
        if ($transport === 'outbox') {
            OtpOutbox::deliver($email, $code);
            return;
        }
        if ($transport !== 'smtp') {
            throw new RuntimeException('Verification delivery is not configured.');
        }

        $host = trim((string) getenv('SMTP_HOST'));
        $from = trim((string) getenv('MAIL_FROM_ADDRESS'));
        $port = filter_var(getenv('SMTP_PORT') ?: '587', FILTER_VALIDATE_INT);
        $encryption = strtolower((string) (getenv('SMTP_ENCRYPTION') ?: 'starttls'));
        if ($host === '' || !filter_var($from, FILTER_VALIDATE_EMAIL) ||
            $port === false || $port < 1 || $port > 65535 ||
            !in_array($encryption, ['starttls', 'smtps', 'none'], true) ||
            ($encryption === 'none' && !in_array($host, ['mailpit', 'localhost', '127.0.0.1'], true))) {
            throw new RuntimeException('Verification delivery is not configured.');
        }

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->Port = $port;
            $mailer->Timeout = 10;
            $mailer->SMTPAutoTLS = false;
            $mailer->SMTPAuth = (string) getenv('SMTP_USERNAME') !== '';
            $mailer->Username = (string) getenv('SMTP_USERNAME');
            $mailer->Password = (string) getenv('SMTP_PASSWORD');
            if ($encryption === 'starttls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'smtps') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mailer->setFrom($from, (string) (getenv('MAIL_FROM_NAME') ?: 'SerendibBank'));
            $mailer->addAddress($email);
            $mailer->CharSet = 'UTF-8';
            $mailer->isHTML(false);
            $mailer->Subject = 'Your SerendibBank sign-in code';
            $mailer->Body = "Your SerendibBank sign-in code is {$code}.\n\nIt expires in 5 minutes. If you did not try to sign in, you can ignore this message.\n";
            $mailer->send();
        } catch (MailException $exception) {
            error_log('Verification email delivery failed.');
            throw new RuntimeException('Verification delivery is unavailable.', 0, $exception);
        }
    }
}
