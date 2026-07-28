<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class EmailHelper
{
    /**
     * Send a raw email using SMTP settings stored in the Settings payload.
     */
    public static function send(string $to, string $subject, string $body): void
    {
        $settings = Setting::singleton();
        $payload = $settings->payload ?? [];

        $smtp = $payload['smtp'] ?? [];

        // Configure SMTP dynamically
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $smtp['host'] ?? env('MAIL_HOST'),
            'mail.mailers.smtp.port' => $smtp['port'] ?? env('MAIL_PORT'),
            'mail.mailers.smtp.encryption' => ($smtp['encryption'] ?? env('MAIL_ENCRYPTION', 'tls')) === 'none'
                ? null
                : ($smtp['encryption'] ?? env('MAIL_ENCRYPTION', 'tls')),
            'mail.mailers.smtp.username' => $smtp['username'] ?? env('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => $smtp['password'] ?? env('MAIL_PASSWORD'),
            'mail.from.address' => $smtp['from_email'] ?? env('MAIL_FROM_ADDRESS'),
            'mail.from.name' => $smtp['from_name'] ?? env('MAIL_FROM_NAME', config('app.name')),
        ]);

        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        Mail::raw($body, function ($message) use ($to, $subject, $fromEmail, $fromName) {
            $message->to($to)->subject($subject);
            if ($fromEmail) {
                $message->from($fromEmail, $fromName ?? $fromEmail);
            }
        });
    }
}
