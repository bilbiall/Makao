<?php

namespace App\Livewire\AdminApp;

use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use Livewire\Component;

/**
 * Renders darajaapis.md inside the app itself - a landlord/admin stuck setting up
 * M-Pesa shouldn't have to go dig a markdown file out of the repo to read it. The
 * file stays the single source of truth; this just displays it.
 */
class MpesaGuide extends Component
{
    public function render()
    {
        $path = base_path('darajaapis.md');

        $markdown = File::exists($path)
            ? File::get($path)
            : "# Guide not found\n\n`darajaapis.md` is missing from this install.";

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return view('livewire.admin-app.mpesa-guide', [
            'html' => (string) $converter->convert($markdown),
        ])->layout('components.layouts.app', ['title' => 'M-Pesa Setup Guide']);
    }
}
