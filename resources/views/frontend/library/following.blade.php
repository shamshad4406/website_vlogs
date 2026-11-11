@extends('layouts.frontend')

@section('content')
    <x-page-header title="Creators you follow" :back="route('library.index')" back-label="Library"
                   subtitle="{{ $creators->total() }} {{ Str::plural('channel', $creators->total()) }}. Their uploads appear on your home feed." />

    @if ($creators->isEmpty())
        <x-empty-state icon="users" title="Not following anyone yet"
                       action="Find creators" :action-url="route('creators.index')">
            Following a creator puts their new vlogs at the top of your home page.
        </x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($creators as $creator)
                <div class="relative">
                    <x-creator-card :creator="$creator" />
                    <div class="absolute right-3 top-3">
                        <x-follow-button :creator="$creator" :following="true" size="sm" />
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">{{ $creators->links() }}</div>
    @endif
@endsection
