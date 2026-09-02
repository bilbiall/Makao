<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Support\CurrentLandlord;
use Illuminate\Support\Facades\Mail;

class EmailHelper
{
    /**
     * Send a raw email using SMTP settings stored in the Settings payload.
     * $landlordId defaults to the current authenticated user's landlord when omitted.
     */
    public static function send(string $to, string $subject, string $body, ?int $landlordId = null): void
    {
        static::configureMailer($landlordId);

        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        Mail::raw($body, function ($message) use ($to, $subject, $fromEmail, $fromName) {
            $message->to($to)->subject($subject);
            if ($fromEmail) {
                $message->from($fromEmail, $fromName ?? $fromEmail);
            }
        });
    }

    /**
     * Points the "smtp" mailer at this landlord's SMTP settings (or the platform-wide
     * fallback) for the rest of the request - same config used by send() above, but
     * exposed separately so anything that sends mail through Laravel's own channels
     * (e.g. a Notification's toMail(), which goes through mail.default rather than a
     * direct Mail::raw() call) still goes out via the right account. User::
     * sendEmailVerificationNotification() is the first caller of this.
     */
    public static function configureMailer(?int $landlordId = null): void
    {
        // Falls back to the platform-wide SMTP account (Platform Settings > Email) when
        // this landlord hasn't set their own - a Gmail App Password works fine there,
        // so a business without its own mail server still gets working email.
        $resolvedLandlordId = $landlordId ?? CurrentLandlord::id();
        $smtp = [
            'host' => Setting::effective($resolvedLandlordId, 'smtp.host'),
            'port' => Setting::effective($resolvedLandlordId, 'smtp.port'),
            'encryption' => Setting::effective($resolvedLandlordId, 'smtp.encryption'),
            'username' => Setting::effective($resolvedLandlordId, 'smtp.username'),
            'password' => Setting::effective($resolvedLandlordId, 'smtp.password'),
            'from_email' => Setting::effective($resolvedLandlordId, 'smtp.from_email'),
            'from_name' => Setting::effective($resolvedLandlordId, 'smtp.from_name'),
        ];

        // Laravel 12's MailManager::createSmtpTransport() does NOT read an "encryption"
        // key at all - it only reads "scheme" (smtp|smtps) and "auto_tls". The old code
        // here set "encryption", which Laravel silently ignored, so the TLS/SSL/None
        // picker in Settings never actually had any effect - the scheme was always
        // guessed purely from the port number (465 => implicit TLS, anything else =>
        // opportunistic STARTTLS). Map the stored choice to what Laravel actually reads:
        //   ssl  -> scheme "smtps" (implicit TLS from connection start, typically :465)
        //   tls  -> scheme "smtp", auto_tls enabled (STARTTLS negotiated, typically :587)
        //   none -> scheme "smtp", auto_tls disabled (no encryption attempted at all)
        $encryption = $smtp['encryption'] ?? env('MAIL_ENCRYPTION', 'tls');

        // Configure SMTP dynamically
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.auto_tls' => $encryption !== 'none',
            'mail.mailers.smtp.host' => $smtp['host'] ?? env('MAIL_HOST'),
            'mail.mailers.smtp.port' => $smtp['port'] ?? env('MAIL_PORT'),
            'mail.mailers.smtp.username' => $smtp['username'] ?? env('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => $smtp['password'] ?? env('MAIL_PASSWORD'),
            'mail.from.address' => $smtp['from_email'] ?? env('MAIL_FROM_ADDRESS'),
            'mail.from.name' => $smtp['from_name'] ?? env('MAIL_FROM_NAME', config('app.name')),
        ]);

        // Laravel's MailManager caches a resolved mailer instance by name for the rest
        // of the process. Purge it first so a config change here always takes effect,
        // even if something earlier in the same request already resolved the "smtp"
        // mailer with different settings (e.g. a different landlord's credentials).
        Mail::purge('smtp');
    }
}
