<?php
// Mailer simple via PHP mail(). OVH mutualise accepte mail() depuis le domaine heberge.

declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $from = 'noreply@cafetiereagrain.fr';
        $fromName = 'cafetiereagrain.fr';

        $boundary = bin2hex(random_bytes(16));

        $headers = [];
        $headers[] = 'From: "' . $fromName . '" <' . $from . '>';
        $headers[] = 'Reply-To: ' . $from;
        $headers[] = 'Return-Path: ' . $from;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        if ($textBody === '') {
            $textBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody) ?? '');
        }

        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= '--' . $boundary . "--\r\n";

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        return @mail($to, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $from);
    }
