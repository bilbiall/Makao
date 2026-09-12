<div class="space-y-4">
    <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3 text-xs text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
        Full setup walkthrough for M-Pesa STK push and C2B - where to get your keys, what to enter, and how to test.
        Keys themselves are entered on the
        <a href="{{ route('app.admin.mpesa-channels') }}" class="font-semibold underline">M-Pesa Channels</a>
        page, not here.
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 dark:bg-slate-900 dark:border-slate-800">
        <div class="mpesa-guide-content">
            {!! $html !!}
        </div>
    </div>

<style>
    .mpesa-guide-content { font-size: 0.875rem; line-height: 1.65; color: #334155; }
    html.dark .mpesa-guide-content { color: #cbd5e1; }

    .mpesa-guide-content h1 { font-size: 1.3rem; font-weight: 700; margin: 0 0 0.75rem; color: #0f172a; }
    .mpesa-guide-content h2 { font-size: 1.05rem; font-weight: 700; margin: 1.75rem 0 0.6rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #0f172a; }
    .mpesa-guide-content h2:first-child { margin-top: 0; padding-top: 0; border-top: none; }
    .mpesa-guide-content h3 { font-size: 0.95rem; font-weight: 600; margin: 1.25rem 0 0.5rem; color: #0f172a; }
    html.dark .mpesa-guide-content h1,
    html.dark .mpesa-guide-content h2,
    html.dark .mpesa-guide-content h3 { color: #f1f5f9; }
    html.dark .mpesa-guide-content h2 { border-top-color: #1e293b; }

    .mpesa-guide-content p { margin: 0 0 0.75rem; }
    .mpesa-guide-content ul, .mpesa-guide-content ol { margin: 0 0 0.75rem 1.25rem; }
    .mpesa-guide-content ul { list-style: disc; }
    .mpesa-guide-content ol { list-style: decimal; }
    .mpesa-guide-content li { margin-bottom: 0.3rem; }
    .mpesa-guide-content li > p { margin-bottom: 0.3rem; }

    .mpesa-guide-content code { background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 0.25rem; font-size: 0.85em; font-family: ui-monospace, monospace; }
    html.dark .mpesa-guide-content code { background: #1e293b; color: #e2e8f0; }
    .mpesa-guide-content pre { background: #0f172a; color: #e2e8f0; padding: 0.85rem; border-radius: 0.6rem; overflow-x: auto; margin: 0 0 0.85rem; }
    .mpesa-guide-content pre code { background: transparent; padding: 0; color: inherit; }

    .mpesa-guide-content table { width: 100%; border-collapse: collapse; margin: 0 0 0.85rem; font-size: 0.85em; display: block; overflow-x: auto; }
    .mpesa-guide-content th, .mpesa-guide-content td { border: 1px solid #e2e8f0; padding: 0.45rem 0.65rem; text-align: left; }
    html.dark .mpesa-guide-content th, html.dark .mpesa-guide-content td { border-color: #334155; }
    .mpesa-guide-content th { background: #f8fafc; font-weight: 600; }
    html.dark .mpesa-guide-content th { background: #1e293b; }

    .mpesa-guide-content a { color: #059669; text-decoration: underline; }
    html.dark .mpesa-guide-content a { color: #34d399; }
    .mpesa-guide-content blockquote { border-left: 3px solid #10b981; padding-left: 0.85rem; color: #64748b; margin: 0 0 0.85rem; }
    html.dark .mpesa-guide-content blockquote { color: #94a3b8; }
    .mpesa-guide-content strong { font-weight: 600; color: inherit; }
    .mpesa-guide-content hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.25rem 0; }
    html.dark .mpesa-guide-content hr { border-top-color: #1e293b; }
</style>
</div>
