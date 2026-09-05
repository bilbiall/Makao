<?php

namespace App\Services;

use App\Helpers\AppHelper;

/**
 * Deterministic, hallucination-proof answers to "how does this work" style
 * questions about the platform itself (not a house search) - e.g. a
 * prospective tenant asking how admission works, or a property manager asking
 * how this helps them. Content is sourced directly from the platform's own
 * published marketing copy (resources/views/components/marketing/how-it-works.blade.php
 * and feature-grid.blade.php) and verified app behavior (the viewing-request ->
 * admit flow in PropertyListingController/ViewingRequestResource) - not
 * generated. The chat assistant has already shown that a configured free LLM
 * will happily invent or garble specifics, so these answers are fixed text,
 * matched by topic, never model-generated.
 */
class PlatformFaqService
{
    /**
     * @return string|null the matched answer, or null if this doesn't look
     * like a platform/process question at all - the caller should treat the
     * message as a normal house-search query instead.
     */
    public function answer(string $text): ?string
    {
        $topic = $this->matchTopic($text);

        return $topic ? $this->answers()[$topic] : null;
    }

    protected function matchTopic(string $text): ?string
    {
        return match (true) {
            (bool) preg_match('/\b(become a tenant|get admitted|admission process|application process|move.?in process|how (do|can) i apply)\b/i', $text) => 'tenant_admission',
            (bool) preg_match('/\b(property managers?|caretakers?|landlords?|property owners?|manage (my|our) propert|list (my|our) propert|how (do|does) (this|it|makao|renty|you) (serve|work|help)|why (use|choose) (makao|renty|this))\b/i', $text) => 'owner_benefits',
            (bool) preg_match('/\b(subscription|monthly plan|pricing plans?|free trial|how much (does|would) it cost to (list|join|sign ?up|use))\b/i', $text) => 'pricing',
            (bool) preg_match('/\b(pay rent|how (do|does) (rent )?payments? work|m-?pesa|pay online|payment methods?)\b/i', $text) => 'payments',
            (bool) preg_match('/\b(what is (makao|renty|this (site|platform|app))|about (makao|renty|this (site|platform|app)))\b/i', $text) => 'general',
            default => null,
        };
    }

    protected function answers(): array
    {
        $appName = AppHelper::getAppName(null);

        return [
            'tenant_admission' => "Getting a place through {$appName} works like this: sign up as a \"looking for a house\" account, browse listings, and tap \"Request a viewing\" on the one you want. The property owner or manager reviews your request - if they admit you, you're set up as a tenant right away with your own portal login (sent by SMS), where you can see invoices and pay rent. If it doesn't work out, they'll let you know so you can keep looking.",
            'owner_benefits' => "For property owners, managers, and caretakers, {$appName} replaces spreadsheets and WhatsApp chasing with one dashboard: manage every property and unit, admit tenants (they get instant portal access), collect rent automatically via M-Pesa or card with payments reconciled against invoices, send automatic SMS/email notifications, track maintenance issues to resolution, run a structured notice-to-vacate process, and see a full activity log of who did what. Managers and caretakers can be scoped to just the properties they're assigned to.",
            'pricing' => "{$appName} offers a few plans depending on your portfolio size, plus a free trial to start - no card required. Exact pricing depends on the plan, so the Pricing page has the current numbers.",
            'payments' => "Tenants pay rent through their own portal by M-Pesa STK push or card - payments reconcile automatically against their invoice, and both the tenant and the property owner/manager get notified.",
            'general' => "{$appName} is a platform for finding a place to rent or stay in Kenya, and for property owners, managers, and caretakers to run their rentals - tenants, invoices, payments, and maintenance all in one place.",
        ];
    }
}
