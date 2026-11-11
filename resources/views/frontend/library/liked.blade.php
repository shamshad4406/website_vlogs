@extends('layouts.frontend')

@section('content')
    <x-page-header title="Liked vlogs" :back="route('library.index')" back-label="Library"
                   subtitle="{{ $vlogs->total() }} {{ Str::plural('vlog', $vlogs->total()) }} you have liked." />

    @if ($vlogs->isEmpty())
        <x-empty-state icon="heart" title="Nothing liked yet"
                       action="Browse vlogs" :action-url="route('vlogs.index')">
            Tap the heart on a vlog and it lands here.
        </x-empty-state>
    @else
        <x-vlog-grid :vlogs="$vlogs" />
        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
