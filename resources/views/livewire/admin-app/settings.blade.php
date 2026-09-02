@php
    // SMS API keys/SMTP setup is technical, platform-facing configuration left to
    // admin/superadmin - General (business info, set up during onboarding) stays
    // open to the property owner too, minus the app_name field itself (see below).
    $tabs = $this->isAdminRole()
        ? ['general' => 'General', 'sms' => 'SMS', 'templates' => 'Templates', 'email' => 'Email', 'billing' => 'Billing', 'payments' => 'Payments']
        : ['general' => 'General', 'templates' => 'Templates', 'billing' => 'Billing', 'payments' => 'Payments'];
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-5">
    @if (session('settings-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('settings-saved') }}
        </div>
    @endif
    @if (session('settings-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('settings-error') }}
        </div>
    @endif

    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('activeTab', '{{ $key }}')"
                @class([
                    'flex-shrink-0 rounded-full px-4 py-2 text-xs font-semibold whitespace-nowrap',
                    'bg-emerald-600 text-white' => $activeTab === $key,
                    'bg-white border border-slate-200 text-slate-600 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300' => $activeTab !== $key,
                ])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-4 dark:bg-slate-900 dark:border-slate-800">
        @if ($activeTab === 'general')
            @if ($this->isAdminRole())
                <div>
                    <label class="{{ $labelClass }}">Application Name</label>
                    <input type="text" wire:model="data.app_name" class="{{ $inputClass }}">
                </div>
            @endif
            <div>
                <label class="{{ $labelClass }}">Company/Business Name</label>
                <input type="text" wire:model="data.company_name" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Support Email</label>
                <input type="email" wire:model="data.support_email" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Support Phone</label>
                <input type="text" wire:model="data.support_phone" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Company Address</label>
                <textarea wire:model="data.company_address" rows="3" class="{{ $inputClass }}"></textarea>
            </div>
            <div>
                <label class="{{ $labelClass }}">Timezone</label>
                <select wire:model="data.timezone" class="{{ $inputClass }}">
                    <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                    <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                    <option value="Africa/Johannesburg">Africa/Johannesburg (SAST)</option>
                    <option value="UTC">UTC</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Currency</label>
                <select wire:model="data.currency" class="{{ $inputClass }}">
                    <option value="KES">KES - Kenyan Shilling</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="EUR">EUR - Euro</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Terms &amp; Conditions</label>
                <textarea wire:model="data.terms_conditions" rows="5" class="{{ $inputClass }}"></textarea>
            </div>
        @elseif ($activeTab === 'sms' && $this->isAdminRole())
            <div>
                <label class="{{ $labelClass }}">SMS API URL</label>
                <input type="url" wire:model="data.sms_url" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">SMS API Key</label>
                <input type="password" wire:model="data.sms_api_key" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">SMS Partner ID</label>
                <input type="text" wire:model="data.sms_partner_id" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">SMS Sender ID</label>
                <input type="text" wire:model="data.sms_sender_id" class="{{ $inputClass }}">
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Send a test SMS</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Verifies whatever's currently typed above, not the last saved values.</p>
                <input type="tel" wire:model="testSmsPhone" placeholder="e.g. 0712345678" class="{{ $inputClass }} mt-0">
                <button type="button" wire:click="sendTestSms" wire:loading.attr="disabled" wire:target="sendTestSms" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-60">
                    <span wire:loading.remove wire:target="sendTestSms">Send test SMS</span>
                    <span wire:loading wire:target="sendTestSms">Sending...</span>
                </button>
            </div>
        @elseif ($activeTab === 'templates')
            @php
                $templateFields = [
                    'template_invoice' => ['Invoice Notification Template', 'Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}, {property_name}'],
                    'template_payment' => ['Payment Confirmation Template', 'Variables: {tenant_name}, {amount_paid}, {invoice_number}, {balance}, {app_name}, {property_name}'],
                    'template_payment_reminder' => ['Payment Reminder Template', 'Variables: {tenant_name}, {amount}, {due_date}'],
                    'template_mass_reminder' => ['Mass Reminder Template', 'Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}, {app_name}, {property_name}'],
                    'template_issue_notification' => ['Issue Notification Template', 'Variables: {tenant_name}, {issue_title}, {issue_description}'],
                    'template_tenant_welcome' => ['Tenant Welcome Template', 'Variables: {tenant_name}, {app_name}, {house_name}, {rent_amount}, {property_name}'],
                    'template_notice_approved' => ['Notice Approved Template', 'Variables: {tenant_name}, {balance}, {approval_date}, {vacate_date}, {property_name}'],
                    'template_notice_denied' => ['Notice Denied Template', 'Variables: {tenant_name}, {balance}, {vacate_date}, {property_name}'],
                    'template_password_reset_sms' => ['Password Reset SMS Template', 'Variables: {tenant_name}, {reset_code}, {app_name}'],
                    'template_new_user_sms' => ['New User Registration SMS Template', 'Variables: {user_name}, {email}, {password}, {role}, {site_url}, {app_name}'],
                ];
            @endphp
            @foreach ($templateFields as $field => [$label, $help])
                <div>
                    <label class="{{ $labelClass }}">{{ $label }}</label>
                    <textarea wire:model="data.{{ $field }}" rows="4" class="{{ $inputClass }}"></textarea>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{{ $help }}</p>
                </div>
            @endforeach
        @elseif ($activeTab === 'email' && $this->isAdminRole())
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">SMTP</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="{{ $labelClass }}">SMTP Host</label>
                    <input type="text" wire:model="data.smtp.host" placeholder="smtp.example.com" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">SMTP Port</label>
                    <input type="number" wire:model="data.smtp.port" placeholder="587" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Encryption</label>
                    <select wire:model="data.smtp.encryption" class="{{ $inputClass }}">
                        <option value="tls">TLS (typically port 587)</option>
                        <option value="ssl">SSL (typically port 465)</option>
                        <option value="none">None</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">SMTP Username</label>
                    <input type="text" wire:model="data.smtp.username" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">SMTP Password</label>
                    <input type="password" wire:model="data.smtp.password" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">From Email</label>
                    <input type="email" wire:model="data.smtp.from_email" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">From Name</label>
                    <input type="text" wire:model="data.smtp.from_name" class="{{ $inputClass }}">
                </div>
            </div>

            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 pt-2 border-t border-slate-100 dark:border-slate-800">Email Templates</p>
            @php
                $emailFields = [
                    'email_template_message' => ['Chat Message Email Template', 'Variables: {tenant_name}, {sender_name}, {message_body}, {app_name}'],
                    'email_template_notice_approved' => ['Notice Approved Email Template', 'Variables: {tenant_name}, {house_name}, {vacate_date}, {balance}, {app_name}'],
                    'email_template_notice_denied' => ['Notice Denied Email Template', 'Variables: {tenant_name}, {house_name}, {vacate_date}, {reason}, {app_name}'],
                    'email_template_password_reset' => ['Password Reset Email Template', 'Variables: {tenant_name}, {reset_url}, {reset_code}, {app_name}'],
                    'email_template_new_user' => ['New User Registration Email Template', 'Variables: {user_name}, {email}, {password}, {role}, {site_url}, {app_name}'],
                ];
            @endphp
            @foreach ($emailFields as $field => [$label, $help])
                <div>
                    <label class="{{ $labelClass }}">{{ $label }}</label>
                    <textarea wire:model="data.{{ $field }}" rows="4" class="{{ $inputClass }}"></textarea>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{{ $help }}</p>
                </div>
            @endforeach
        @elseif ($activeTab === 'billing')
            <div>
                <label class="{{ $labelClass }}">Auto Invoice Date (Monthly)</label>
                <input type="date" wire:model="data.auto_invoice_date" class="{{ $inputClass }}">
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Day of month when invoices are automatically sent. Leave empty to disable.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" wire:model="data.auto_invoice_enabled" class="rounded border-slate-300 dark:border-slate-600">
                Enable Automatic Invoicing
            </label>
            @if ($this->isAdminRole())
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 space-y-2">
                    <p class="font-semibold text-slate-700 dark:text-slate-300">Cronjob required for automatic invoicing:</p>
                    <code class="block bg-white border border-slate-200 rounded px-2 py-1 text-[11px] dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300">* * * * * cd /path/to/app && php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
                    <p>In cPanel: Advanced &rarr; Cron Jobs &rarr; Every Minute, with the command above pointing at your app's path.</p>
                </div>
            @else
                <p class="text-xs text-slate-400 dark:text-slate-500">Once enabled, our team handles the server-side scheduling for you.</p>
            @endif
        @elseif ($activeTab === 'payments')
            @php $hasGateway = $this->hasPaymentGatewayCredentials(); @endphp

            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Manual payment details</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Shown to tenants paying you directly - bank account, paybill, or till number.</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Bank name</label>
                        <input type="text" wire:model="data.manual_payment.bank_name" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Account name</label>
                        <input type="text" wire:model="data.manual_payment.account_name" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Account number</label>
                        <input type="text" wire:model="data.manual_payment.account_number" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Paybill number</label>
                        <input type="text" wire:model="data.manual_payment.paybill_number" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Till number</label>
                        <input type="text" wire:model="data.manual_payment.till_number" class="{{ $inputClass }}">
                    </div>
                    <div class="col-span-2">
                        <label class="{{ $labelClass }}">Other instructions</label>
                        <textarea wire:model="data.manual_payment.instructions" rows="2" class="{{ $inputClass }}" placeholder="e.g. use your house number as the M-Pesa reference"></textarea>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Collection Method</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Choose how rent payments are collected from tenants.</p>
                <div class="space-y-2">
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3 cursor-pointer">
                        <input type="radio" wire:model="data.payment_mode" value="manual" class="mt-1">
                        <span>
                            <span class="block text-sm font-medium text-slate-800 dark:text-slate-200">Manual</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Tenants pay you directly (details above) and you record it yourself.</span>
                        </span>
                    </label>
                    @if ($hasGateway || $this->isAdminRole())
                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-3 cursor-pointer">
                            <input type="radio" wire:model="data.payment_mode" value="automatic" class="mt-1">
                            <span>
                                <span class="block text-sm font-medium text-slate-800 dark:text-slate-200">Automatic</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">Tenants pay in-app via M-Pesa STK push / Pesapal.</span>
                            </span>
                        </label>
                    @endif
                </div>
            </div>

            @if ($this->isAdminRole())
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 pt-2 border-t border-slate-100 dark:border-slate-800">Pesapal</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Consumer Key</label>
                        <input type="text" wire:model="data.pesapal.consumer_key" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Consumer Secret</label>
                        <input type="password" wire:model="data.pesapal.consumer_secret" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Webhook Secret</label>
                        <input type="password" wire:model="data.pesapal.webhook_secret" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">IPN ID</label>
                        <input type="text" wire:model="data.pesapal.ipn_id" class="{{ $inputClass }}">
                    </div>
                    <div class="col-span-2">
                        <label class="{{ $labelClass }}">Callback URL</label>
                        <input type="url" wire:model="data.pesapal.callback_url" class="{{ $inputClass }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="data.pesapal.sandbox" class="rounded border-slate-300 dark:border-slate-600">
                        Use Pesapal Sandbox
                    </label>
                    <div>
                        <label class="{{ $labelClass }}">Currency</label>
                        <input type="text" wire:model="data.pesapal.currency" class="{{ $inputClass }}">
                    </div>
                </div>

                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 pt-2 border-t border-slate-100 dark:border-slate-800">M-Pesa (Daraja API)</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Daraja API Key</label>
                        <input type="text" wire:model="data.mpesa.consumer_key" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Daraja API Secret</label>
                        <input type="password" wire:model="data.mpesa.consumer_secret" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Business Short Code</label>
                        <input type="text" wire:model="data.mpesa.business_shortcode" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Online Passkey</label>
                        <input type="password" wire:model="data.mpesa.passkey" class="{{ $inputClass }}">
                    </div>
                    <div class="col-span-2">
                        <label class="{{ $labelClass }}">Callback URL</label>
                        <input type="url" wire:model="data.mpesa.callback_url" class="{{ $inputClass }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="data.mpesa.sandbox" class="rounded border-slate-300 dark:border-slate-600">
                        Use Sandbox (Daraja Test)
                    </label>
                    <div>
                        <label class="{{ $labelClass }}">Currency</label>
                        <input type="text" wire:model="data.mpesa.currency" class="{{ $inputClass }}">
                    </div>
                </div>

                @if ($this->pendingGatewayRequest)
                    @php $req = $this->pendingGatewayRequest; @endphp
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400 space-y-1">
                        <p class="font-semibold">Property owner requested {{ \App\Helpers\PaymentGatewayRequestHelper::methodLabel($req['method']) }}</p>
                        @if (!empty($req['note']))
                            <p>"{{ $req['note'] }}"</p>
                        @endif
                        <p class="text-amber-600 dark:text-amber-500">Requested {{ \Carbon\Carbon::parse($req['requested_at'])->diffForHumans() }}. Fill in the credentials above, set Collection Method to Automatic, then Save to mark this fulfilled.</p>
                    </div>
                @endif
            @elseif (!$hasGateway)
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    @if ($this->pendingGatewayRequest)
                        @php $req = $this->pendingGatewayRequest; @endphp
                        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400">
                            <p class="font-semibold">Request sent - {{ \App\Helpers\PaymentGatewayRequestHelper::methodLabel($req['method']) }}</p>
                            <p class="text-xs text-amber-600 dark:text-amber-500 mt-1">Submitted {{ \Carbon\Carbon::parse($req['requested_at'])->diffForHumans() }}. Our team will set this up and notify you once it's ready.</p>
                        </div>
                    @else
                        <div class="rounded-lg bg-slate-50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 p-3 space-y-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Want to accept M-Pesa/Pesapal in-app?</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">This needs real M-Pesa/Pesapal business credentials - request it and our team sets it up for you.</p>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Which gateway?</label>
                                <select wire:model="gatewayRequestMethod" class="{{ $inputClass }}">
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="pesapal">Pesapal</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Note (optional)</label>
                                <textarea wire:model="gatewayRequestNote" rows="2" class="{{ $inputClass }}" placeholder="Anything we should know, e.g. your existing till/paybill number"></textarea>
                            </div>
                            <button wire:click="requestAutomaticPaymentSetup" type="button" wire:loading.attr="disabled" wire:target="requestAutomaticPaymentSetup" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                                <span wire:loading.remove wire:target="requestAutomaticPaymentSetup">Request automatic payment setup</span>
                                <span wire:loading wire:target="requestAutomaticPaymentSetup">Sending request...</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        @endif

        <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>
