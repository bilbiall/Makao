import './bootstrap';

import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

// Registered once, globally, regardless of which Alpine instance ends up running (see
// below) - dashboard charts across every role's app-shell reference window.Chart.
Chart.register(...registerables);
window.Chart = Chart;

// Livewire v3 bundles and auto-starts its own Alpine instance whenever a page includes
// @livewireScripts. Also starting a second, separately-imported Alpine on the same page
// causes two competing instances - Livewire's own component hydration then silently
// fails (its wire:id elements never get registered, so every wire:click/wire:model
// becomes inert with no console error beyond a stray "multiple instances of Alpine
// running" warning). The app-shell layout marks pages that already include Livewire via
// data-has-livewire on <body>; only start our own Alpine when that's absent (the
// marketing site, which has no Livewire component of its own but still needs Alpine
// for its mobile nav toggle/FAQ accordion).
if (!document.body.hasAttribute('data-has-livewire')) {
    window.Alpine = Alpine;
    Alpine.start();
}

// Global "is anything loading" signal for every Livewire request on the page
// (wire:click, wire:model.live, form saves, etc.) - a request with no visible
// feedback otherwise looks identical to a hung page. Livewire.hook('request', ...)
// fires for every component's round trip regardless of which one triggered it, so a
// single top-of-page progress bar (see components/layouts/app.blade.php) covers the
// whole app-shell without needing a wire:loading directive on every single button.
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ succeed, fail }) => {
        document.dispatchEvent(new CustomEvent('app:loading-start'));
        const done = () => document.dispatchEvent(new CustomEvent('app:loading-end'));
        succeed(done);
        fail(done);
    });
});
