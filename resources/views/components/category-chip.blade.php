@props(['category', 'active' => false, 'count' => null])

<a href="{{ route('categories.show', $category->slug) }}"
   class="chip {{ $active ? 'chip-active' : '' }}">
    @if ($category->icon)
        <x-icon :name="$category->icon" class="h-4 w-4" />
    @endif
    {{ $category->name }}
    @if (! is_null($count))
        <span class="tabular-nums opacity-60">{{ $count }}</span>
    @endif
</a>
