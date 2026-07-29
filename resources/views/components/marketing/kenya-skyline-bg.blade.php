{{-- Original artwork (not a photo) evoking the Kenyan market: a savannah sunset,
     an acacia tree silhouette, and a Nairobi-style skyline. Kept as inline SVG so it
     stays crisp at any size, loads instantly, and needs no external image asset.
     Trees/skyline are pinned to the far edges at low opacity - background texture,
     not foreground shapes that could collide with the headline/copy on top. --}}
<svg class="absolute inset-0 -z-10 h-full w-full" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="skyGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#ecfdf5" />
            <stop offset="45%" stop-color="#fef3c7" />
            <stop offset="100%" stop-color="#fde68a" />
        </linearGradient>
        <radialGradient id="sunGradient" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="#fbbf24" />
            <stop offset="100%" stop-color="#f59e0b" stop-opacity="0" />
        </radialGradient>
    </defs>

    <rect width="1200" height="700" fill="url(#skyGradient)" />
    <circle cx="1030" cy="120" r="130" fill="url(#sunGradient)" opacity="0.7" />

    <!-- Nairobi-style skyline silhouette, far right edge only -->
    <g fill="#065f46" opacity="0.14">
        <rect x="960" y="440" width="40" height="140" />
        <rect x="1004" y="400" width="28" height="180" />
        <rect x="1038" y="460" width="46" height="120" />
        <rect x="1090" y="415" width="30" height="165" />
        <rect x="1126" y="450" width="56" height="130" />
    </g>

    <!-- Acacia tree silhouette (iconic East African savannah motif), pinned low and
         to the right edge (clear of the headline/CTA column on the left), built from
         simple flattened ellipses so the "table-top" canopy shape reads clearly
         instead of an ad-hoc bezier blob. -->
    <g transform="translate(1140,660) scale(0.75)" opacity="0.35">
        <rect x="-4" y="-10" width="10" height="130" fill="#064e3b" rx="2" />
        <path d="M -18 30 L 12 -6 L 42 30 Z" fill="#064e3b" />
        <ellipse cx="4" cy="-18" rx="95" ry="16" fill="#064e3b" />
        <ellipse cx="-55" cy="-8" rx="42" ry="11" fill="#064e3b" />
        <ellipse cx="70" cy="-6" rx="48" ry="12" fill="#064e3b" />
    </g>

    <!-- Foreground ground haze -->
    <rect x="0" y="650" width="1200" height="50" fill="#f5f5f4" opacity="0.4" />
</svg>
