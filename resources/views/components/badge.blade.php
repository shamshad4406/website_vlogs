@props(['tone' => 'muted', 'icon' => null])

<span {{ $attributes->merge(['class' => 'badge-'.$tone]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-3.5 w-3.5" />
    @endif
    {{ $slot }}
</span>
