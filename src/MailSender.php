<?php

namespace mywishlist;

use PHPMailer\PHPMailer\{PHPMailer, Exception, SMTP};

/**
 * Class Mail Sender
 * Usage of PHPMailer
 * @author Guillaume ARNOUX
 * @package mywishlist
 */
class MailSender
{

    /**
     * Send an email
     * @param string $subject subject of the mail
     * @param string $body body of the mail
     * @param array $recipients list of recipients
     * @param bool $debug at false. if true, show smtp transaction
     * @return bool true if mail was sent
     */
    public static function sendMail(string $subject, string $body, array $recipients, bool $debug = false): bool{
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        try {
            //enable debug
            if($debug)
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $conf = parse_ini_file(__DIR__.DIRECTORY_SEPARATOR."conf".DIRECTORY_SEPARATOR."conf.ini");
            $mail->isSMTP();
            $mail->Host = $conf['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $conf['smtp_username'];
            $mail->Password = $conf['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $from = explode("%", $conf['smtp_from']);
            $mail->setFrom(ltrim($from[1], '%'), $from[0]);
            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
        return false;
    }

}
