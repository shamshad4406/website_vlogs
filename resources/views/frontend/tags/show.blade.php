@extends('layouts.frontend')

@section('content')
    <x-page-header title="#{{ $tag->name }}"
                   subtitle="{{ $vlogs->total() }} {{ Str::plural('vlog', $vlogs->total()) }} tagged {{ $tag->name }}"
                   :back="route('vlogs.index')" back-label="All vlogs" />

    @if ($vlogs->isEmpty())
        <x-empty-state icon="tag" title="Nothing with this tag yet" />
    @else
        <x-vlog-grid :vlogs="$vlogs" />
        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
