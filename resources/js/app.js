import './bootstrap';

import Alpine from 'alpinejs';

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
