<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Support\CurrentLandlord;

class SmsTemplateHelper
{
    /**
     * Get a template by key and render it with variables.
     * $landlordId defaults to the current authenticated user's landlord when omitted.
     *
     * @param string $templateKey The template key (e.g., 'template_invoice')
     * @param array $variables Key-value pairs to replace in template
     * @return string The rendered message
     */
    public static function render(string $templateKey, array $variables = [], ?int $landlordId = null): string
    {
        $landlordId ??= CurrentLandlord::id();
        $settings = Setting::forLandlord($landlordId);
        $payload = $settings->payload ?? [];

        $template = $payload[$templateKey] ?? self::getDefaultTemplate($templateKey);

        if (empty($template)) {
            throw new \RuntimeException("Template '$templateKey' not configured.");
        }

        return self::interpolate($template, $variables, $landlordId);
    }

    /**
     * Replace {variable} placeholders with values
     */
    private static function interpolate(string $template, array $variables, ?int $landlordId): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        // Replace app_name with value from settings
        $template = str_replace('{app_name}', AppHelper::getAppName($landlordId), $template);

        return $template;
    }

    /**
     * Get default templates if none are configured. Public so the Settings forms
     * (App\Filament\Concerns\HasLandlordSettingsSchema, App\Livewire\AdminApp\Settings)
     * can pre-fill an empty textarea with the exact same text that would actually be
     * sent - a template with no default shown here previously meant the field looked
     * blank/unconfigured in Settings even though it silently worked at send-time.
     */
    public static function getDefaultTemplate(string $templateKey): string
    {
        $defaults = [
            'template_invoice' => 'Hello {tenant_name}, your invoice ({invoice_number}) of KES {amount} is due by {due_date}.',
            'template_payment' => 'Hi {tenant_name}, we\'ve received your payment of KES {amount_paid} for Invoice #{invoice_number}. Your remaining balance is KES {balance}. Thank you. - {app_name}',
            'template_payment_reminder' => 'Hi {tenant_name}, reminder: your payment of KES {amount} is due by {due_date}.',
            'template_issue_notification' => 'Hi {tenant_name}, issue reported: {issue_title}. Description: {issue_description}',
            'template_mass_reminder' => 'Hi {tenant_name}, this is a reminder for Invoice {invoice_number}: KES {amount} due by {due_date}. Thank you, {app_name}.',
            'template_tenant_welcome' => 'Hello {tenant_name}, welcome to {app_name}. You were admitted to {house_name} with a monthly rent of KES {rent_amount}',
            'template_tenant_invite' => 'Hi {tenant_name}, {property_name} has added you as a tenant. Visit {join_url} to create an account (or log in), then enter your code {code} to see your invoices and bills. - {app_name}',
            'template_notice_approved' => 'Hi {tenant_name}, your vacate notice has been approved. Balance: KES {balance}. Approval date: {approval_date}. Vacate date: {vacate_date}.',
            'template_notice_denied' => 'Hi {tenant_name}, your vacate notice has been denied. Balance: KES {balance}. Date requested: {vacate_date}.',
            'template_password_reset_sms' => 'Hi {tenant_name}, use this code to reset your password: {reset_code}. - {app_name}',
            'template_new_user_sms' => 'Hi {user_name}, your {role} account has been created. Email: {email} | Password: {password} | Login: {site_url} - {app_name}',
        ];

        return $defaults[$templateKey] ?? '';
    }
}