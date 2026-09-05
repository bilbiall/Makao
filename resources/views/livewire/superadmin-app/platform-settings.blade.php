@php
    $tabs = ['appearance' => 'Appearance', 'general' => 'General', 'ai' => 'AI Search', 'sms' => 'SMS', 'email' => 'Email', 'billing' => 'Subscription Billing'];
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
    $paletteSwatches = [
        'green' => ['label' => 'Green (default)', 'hex' => '#059669', 'description' => 'The current color - emerald green.'],
        'blue' => ['label' => 'Blue', 'hex' => '#2563eb', 'description' => 'A cool, trustworthy blue.'],
        'gold' => ['label' => 'Gold', 'hex' => '#d97706', 'description' => 'A warm amber/gold.'],
        'red' => ['label' => 'Red', 'hex' => '#dc2626', 'description' => 'A bold red (distinct from the rose used for errors/delete actions).'],
    ];
@endphp
<div class="space-y-5">
    @if (session('platform-settings-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('platform-settings-saved') }}
        </div>
    @endif
    @if (session('platform-settings-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('platform-settings-error') }}
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
        @if ($activeTab === 'appearance')
            <p class="text-xs text-slate-500 dark:text-slate-400">Re-skins the whole site - marketing pages, the mobile app-shell for every role, and every Filament panel. Takes effect on next page load.</p>

            <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
                <label class="{{ $labelClass }}">Site logo</label>
                <div class="flex items-center gap-3">
                    @if ($logoUpload)
                        <img src="{{ $logoUpload->temporaryUrl() }}" alt="" class="h-10 w-auto max-w-[8rem] object-contain">
                    @elseif (! empty($data['logo_path']))
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($data['logo_path']) }}" alt="" class="h-10 w-auto max-w-[8rem] object-contain">
                    @endif
                    <input type="file" accept="image/*" wire:model="logoUpload" class="{{ $inputClass }}">
                    @if (! empty($data['logo_path']))
                        <button type="button" wire:click="removeLogo" wire:confirm="Remove the site logo and go back to the text logo?" class="text-xs text-rose-600 dark:text-rose-400 whitespace-nowrap">Remove</button>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Replaces the "R Renty" text logo everywhere (marketing site, app header, Filament panels). Also used as the browser tab favicon, unless you set a dedicated one below. Leave blank to keep the text logo.</p>
                <p wire:loading wire:target="logoUpload" class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Uploading...</p>
                @error('logoUpload') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
                <label class="{{ $labelClass }}">Favicon (browser tab icon)</label>
                <div class="flex items-center gap-3">
                    @if ($faviconUpload)
                        <img src="{{ $faviconUpload->temporaryUrl() }}" alt="" class="h-10 w-10 object-contain border border-slate-200 dark:border-slate-700 rounded">
                    @elseif (! empty($data['favicon_path']))
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($data['favicon_path']) }}" alt="" class="h-10 w-10 object-contain border border-slate-200 dark:border-slate-700 rounded">
                    @endif
                    <input type="file" accept="image/*" wire:model="faviconUpload" class="{{ $inputClass }}">
                    @if (! empty($data['favicon_path']))
                        <button type="button" wire:click="removeFavicon" wire:confirm="Remove the dedicated favicon? The logo above will be used instead, if set." class="text-xs text-rose-600 dark:text-rose-400 whitespace-nowrap">Remove</button>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Optional - any image works here (it doesn't need a transparent background, and can be totally different from the logo above). If left blank, the site logo is used instead.</p>
                <p wire:loading wire:target="faviconUpload" class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Uploading...</p>
                @error('faviconUpload') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                @foreach ($paletteSwatches as $key => $swatch)
                    <label @class([
                        'flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition',
                        'border-emerald-600 ring-1 ring-emerald-600 dark:border-emerald-500 dark:ring-emerald-500' => ($data['brand_palette'] ?? 'green') === $key,
                        'border-slate-200 dark:border-slate-700' => ($data['brand_palette'] ?? 'green') !== $key,
                    ])>
                        <input type="radio" wire:model="data.brand_palette" value="{{ $key }}" class="sr-only">
                        <span class="h-8 w-8 rounded-full flex-shrink-0 border border-black/10" style="background-color: {{ $swatch['hex'] }}"></span>
                        <span>
                            <span class="block text-sm font-medium text-slate-800 dark:text-slate-200">{{ $swatch['label'] }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $swatch['description'] }}</span>
                        </span>
                    </label>
                @endforeach
                @error('data.brand_palette') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
        @elseif ($activeTab === 'general')
            <div>
                <label class="{{ $labelClass }}">Application Name</label>
                <input type="text" wire:model="data.app_name" class="{{ $inputClass }}">
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Default display name for any landlord who hasn't set their own.</p>
                @error('data.app_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Analytics</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Applies to the public marketing site only, not the authenticated panels.</p>
                <label class="{{ $labelClass }}">Google Analytics Measurement ID</label>
                <input type="text" wire:model="data.google_analytics_id" placeholder="G-XXXXXXXXXX" class="{{ $inputClass }}">
                @error('data.google_analytics_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Support</p>
                <label class="{{ $labelClass }}">Platform support email</label>
                <input type="email" wire:model="data.platform_support_email" class="{{ $inputClass }}">
                @error('data.platform_support_email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
        @elseif ($activeTab === 'ai')
            <p class="text-xs text-slate-500 dark:text-slate-400">Powers natural-language search and the site-wide chat assistant. Get a free API key at openrouter.ai/keys - pick a model ending in ":free" (e.g. the default below) to use this at zero cost.</p>

            <div>
                <label class="{{ $labelClass }}">OpenRouter API Key</label>
                <input type="password" wire:model="data.openrouter_api_key" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Model</label>
                <input type="text" wire:model="data.openrouter_model" placeholder="meta-llama/llama-3.1-8b-instruct:free" class="{{ $inputClass }}">
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Must match a model slug from openrouter.ai/models exactly. Free models end in ":free".</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 pt-1">
                <input type="checkbox" wire:model="data.ai_search_enabled" class="rounded border-slate-300 dark:border-slate-600">
                AI chat assistant enabled
            </label>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 -mt-1">Turns the chat bubble and natural-language search off site-wide without deleting the API key above.</p>

            <div class="pt-2">
                <label class="{{ $labelClass }}">Assistant avatar image</label>
                <div class="flex items-center gap-3">
                    @if ($aiAvatarUpload)
                        <img src="{{ $aiAvatarUpload->temporaryUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                    @elseif (! empty($data['ai_avatar_path']))
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($data['ai_avatar_path']) }}" alt="" class="h-9 w-9 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                    @endif
                    <input type="file" accept="image/*" wire:model="aiAvatarUpload" class="{{ $inputClass }}">
                    @if (! empty($data['ai_avatar_path']))
                        <button type="button" wire:click="removeAiAvatar" wire:confirm="Remove the assistant avatar?" class="text-xs text-rose-600 dark:text-rose-400 whitespace-nowrap">Remove</button>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Shown next to the assistant's replies in the chat bubble. Leave blank to use the default sparkle icon.</p>
                <p wire:loading wire:target="aiAvatarUpload" class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Uploading...</p>
                @error('aiAvatarUpload') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Test connection</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Sends a tiny request using whatever is currently typed above, not the last saved values.</p>
                <button type="button" wire:click="testOpenRouter" wire:loading.attr="disabled" wire:target="testOpenRouter" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-60">
                    <span wire:loading.remove wire:target="testOpenRouter">Test Connection</span>
                    <span wire:loading wire:target="testOpenRouter">Testing...</span>
                </button>
            </div>
        @elseif ($activeTab === 'sms')
            <p class="text-xs text-slate-500 dark:text-slate-400">Used by any landlord who hasn't set their own SMS gateway in their Settings.</p>

            <div>
                <label class="{{ $labelClass }}">SMS API URL</label>
                <input type="url" wire:model="data.sms_url" class="{{ $inputClass }}">
                @error('data.sms_url') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
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
        @elseif ($activeTab === 'email')
            <p class="text-xs text-slate-500 dark:text-slate-400">Used by any landlord who hasn't set their own SMTP in their own Settings &gt; Email tab. A Gmail account works well here (use an App Password, not the account password - Google Account &gt; Security &gt; 2-Step Verification &gt; App Passwords).</p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $labelClass }}">SMTP Host</label>
                    <input type="text" wire:model="data.smtp.host" placeholder="smtp.gmail.com" class="{{ $inputClass }}">
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
                    <input type="text" wire:model="data.smtp.username" placeholder="you@gmail.com" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">SMTP Password</label>
                    <input type="password" wire:model="data.smtp.password" class="{{ $inputClass }}">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">For Gmail, a 16-character App Password, not your normal Google password.</p>
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
        @elseif ($activeTab === 'billing')
            <p class="text-xs text-slate-500 dark:text-slate-400">These credentials collect payment FROM landlords FOR their own Renty subscription - completely separate from a landlord's own M-Pesa/Pesapal, which each business sets individually in their own Settings &gt; Payments tab to collect rent from their tenants.</p>

            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 pt-2 border-t border-slate-100 dark:border-slate-800">Pesapal</p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $labelClass }}">Consumer Key</label>
                    <input type="text" wire:model="data.subscription_pesapal.consumer_key" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Consumer Secret</label>
                    <input type="password" wire:model="data.subscription_pesapal.consumer_secret" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Webhook Secret</label>
                    <input type="password" wire:model="data.subscription_pesapal.webhook_secret" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">IPN ID</label>
                    <input type="text" wire:model="data.subscription_pesapal.ipn_id" class="{{ $inputClass }}">
                </div>
                <div class="col-span-2">
                    <label class="{{ $labelClass }}">Callback URL</label>
                    <input type="url" wire:model="data.subscription_pesapal.callback_url" class="{{ $inputClass }}">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" wire:model="data.subscription_pesapal.sandbox" class="rounded border-slate-300 dark:border-slate-600">
                    Use Pesapal Sandbox
                </label>
                <div>
                    <label class="{{ $labelClass }}">Currency</label>
                    <input type="text" wire:model="data.subscription_pesapal.currency" class="{{ $inputClass }}">
                </div>
            </div>

            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 pt-2 border-t border-slate-100 dark:border-slate-800">M-Pesa (Daraja API)</p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $labelClass }}">Daraja API Key</label>
                    <input type="text" wire:model="data.subscription_mpesa.consumer_key" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Daraja API Secret</label>
                    <input type="password" wire:model="data.subscription_mpesa.consumer_secret" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Business Short Code</label>
                    <input type="text" wire:model="data.subscription_mpesa.business_shortcode" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Online Passkey</label>
                    <input type="password" wire:model="data.subscription_mpesa.passkey" class="{{ $inputClass }}">
                </div>
                <div class="col-span-2">
                    <label class="{{ $labelClass }}">Callback URL</label>
                    <input type="url" wire:model="data.subscription_mpesa.callback_url" class="{{ $inputClass }}">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" wire:model="data.subscription_mpesa.sandbox" class="rounded border-slate-300 dark:border-slate-600">
                    Use Sandbox (Daraja Test)
                </label>
                <div>
                    <label class="{{ $labelClass }}">Currency</label>
                    <input type="text" wire:model="data.subscription_mpesa.currency" class="{{ $inputClass }}">
                </div>
            </div>
        @endif

        <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>
