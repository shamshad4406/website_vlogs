@extends('layouts.frontend')

@section('content')
    <x-page-header title="Explore vlogs"
                   subtitle="Every published vlog. Filter by category, or sort by what people are actually watching.">
        <div class="flex gap-1 rounded-full bg-ink-100 p-1 text-sm dark:bg-white/10">
            @foreach ([['', 'Newest'], ['popular', 'Most viewed'], ['liked', 'Most liked']] as [$value, $label])
                <a href="{{ request()->fullUrlWithQuery(['sort' => $value ?: null]) }}"
                   class="rounded-full px-3 py-1.5 font-medium transition
                          {{ $sort === $value ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-900 dark:text-white' : 'text-ink-500 hover:text-ink-800 dark:text-ink-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </x-page-header>

    @if ($vlogs->isEmpty())
        <x-empty-state icon="compass" title="Nothing matches that yet"
                       action="Clear filters" :action-url="route('vlogs.index')">
            Try another category, or come back after the next batch of uploads is approved.
        </x-empty-state>
    @else
        <x-vlog-grid :vlogs="$vlogs" />
        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
