<span wire:poll.20s>
    @if ($count > 0)
        <span class="inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-semibold leading-none">
            {{ $count > 9 ? '9+' : $count }}
        </span>
    @endif
</span>
