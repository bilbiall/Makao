<x-filament::page>
    <form wire:submit.prevent="send">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament-support::button type="submit" form="form">
                Send
            </x-filament-support::button>
        </div>
    </form>
</x-filament::page>
