<?php

namespace App\Helpers;

use App\Models\Setting;

class EmailTemplateHelper
{
    /**
     * Render an email template with replacements and sensible defaults.
     */
    public static function render(string $key, array $replacements = []): string
    {
        $settings = Setting::singleton();
        $payload = $settings->payload ?? [];

        $defaults = [
            'message' => "Hi {tenant_name},\n\nYou have a new message from {sender_name}:\n{message_body}\n\nRegards, {app_name}",
            'invoice' => "Hi {tenant_name}, invoice {invoice_number} of KES {amount} is due by {due_date}.\n\nRegards, {app_name}",
            'payment' => "Hi {tenant_name}, we received your payment of KES {amount_paid} for Invoice {invoice_number}. Remaining balance: KES {balance}.\n\nThank you, {app_name}",
            'notice_approved' => "Hi {tenant_name}, your notice to vacate {house_name} on {vacate_date} has been approved. Balance: KES {balance}.\n\nRegards, {app_name}",
            'notice_denied' => "Hi {tenant_name}, your notice to vacate {house_name} on {vacate_date} has been denied. {reason}\n\nRegards, {app_name}",
            'password_reset' => "Hi {tenant_name},\n\nYou requested a password reset. Click the link or use the code below:\n{reset_url}\nReset Code: {reset_code}\n\nIf you did not request this, please ignore this email.\n\nRegards, {app_name}",
            'new_user' => "Hi {user_name},\n\nYour {role} account has been successfully created!\n\nLogin Details:\nEmail: {email}\nPassword: {password}\n\nYou can login at: {site_url}\n\nPlease change your password after first login.\n\nRegards,\n{app_name}",
            'issue_update' => "Hi {tenant_name}, your issue '{issue_title}' status changed to {issue_status}.\n\nRegards, {app_name}",
        ];

        $template = $payload["email_template_{$key}"] ?? $defaults[$key] ?? '';

        foreach ($replacements as $search => $replace) {
            $template = str_replace('{' . $search . '}', (string) $replace, $template);
        }

        // Replace any app_name placeholder
        $template = str_replace('{app_name}', AppHelper::getAppName(), $template);

        return $template;
    }
}
