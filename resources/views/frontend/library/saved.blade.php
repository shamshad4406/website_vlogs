@extends('layouts.frontend')

@section('content')
    <x-page-header title="Saved for later" :back="route('library.index')" back-label="Library"
                   subtitle="{{ $vlogs->total() }} {{ Str::plural('vlog', $vlogs->total()) }} saved." />

    @if ($vlogs->isEmpty())
        <x-empty-state icon="bookmark" title="Nothing saved yet"
                       action="Browse vlogs" :action-url="route('vlogs.index')">
            Use the save button on any vlog to come back to it later.
        </x-empty-state>
    @else
        <x-vlog-grid :vlogs="$vlogs" />
        <div class="mt-8">{{ $vlogs->links() }}</div>
    @endif
@endsection
