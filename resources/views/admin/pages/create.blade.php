@extends('layouts.admin')
@section('title', 'New page')

@section('content')
    <x-page-header title="New page" :back="route('admin.pages.index')" back-label="Pages" />

    <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf
        @include('admin.pages._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.pages.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create page</button>
        </div>
    </form>
@endsection
