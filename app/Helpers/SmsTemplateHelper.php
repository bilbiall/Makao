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
     * Get default templates if none are configured
     */
    private static function getDefaultTemplate(string $templateKey): string
    {
        $defaults = [
            'template_invoice' => 'Hello {tenant_name}, your invoice ({invoice_number}) of KES {amount} is due by {due_date}.',
            'template_payment' => 'Hi {tenant_name}, payment of KES {amount} received on {payment_date}. Thank you!',
            'template_payment_reminder' => 'Hi {tenant_name}, reminder: your payment of KES {amount} is due by {due_date}.',
            'template_issue_notification' => 'Hi {tenant_name}, issue reported: {issue_title}. Description: {issue_description}',
            'template_mass_reminder' => 'Hi {tenant_name}, reminder for Invoice {invoice_number}: KES {amount} due by {due_date}.',
            'template_new_user_sms' => 'Hi {user_name}, your {role} account has been created. Email: {email} | Password: {password} | Login: {site_url} - {app_name}',
        ];

        return $defaults[$templateKey] ?? '';
    }
}