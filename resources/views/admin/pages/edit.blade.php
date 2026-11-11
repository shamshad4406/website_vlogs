@extends('layouts.admin')
@section('title', 'Edit page')

@section('content')
    <x-page-header :title="'Edit '.$page->title" :back="route('admin.pages.index')" back-label="Pages">
        <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="btn-secondary btn-sm">
            <x-icon name="external" class="h-4 w-4" /> View page
        </a>
    </x-page-header>

    <form method="POST" action="{{ route('admin.pages.update', $page) }}">
        @csrf
        @method('PUT')
        @include('admin.pages._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.pages.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save page</button>
        </div>
    </form>
@endsection
