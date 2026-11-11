@props([
    'action',
    'method' => 'POST',
    'message' => 'Are you sure?',
    'label' => 'Delete',
    'icon' => null,
    'class' => 'btn-ghost btn-sm text-danger hover:bg-red-50 dark:hover:bg-red-500/10',
])

<form method="POST" action="{{ $action }}" class="inline"
      onsubmit="return confirm(@js($message))">
    @csrf
    @if (! in_array(strtoupper($method), ['POST', 'GET']))
        @method($method)
    @endif
    {{ $slot }}
    <button type="submit" class="{{ $class }}">
        @if ($icon)
            <x-icon :name="$icon" class="h-4 w-4" />
        @endif
        {{ $label }}
    </button>
</form>
