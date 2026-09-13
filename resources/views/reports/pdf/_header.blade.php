<div class="header">
    <h1>{{ $landlord }} &middot; {{ $title }}</h1>
    <div class="meta">
        @isset($range)
            Period: {{ $range }} &middot;
        @endisset
        Generated {{ $generated_at->format('d M Y, H:i') }}
    </div>
</div>
