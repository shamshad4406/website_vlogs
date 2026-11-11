@extends('layouts.admin')
@section('title', 'New advertisement')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header title="New advertisement" :back="route('admin.advertisements.index')" back-label="Advertisements" />

    <form method="POST" action="{{ route('admin.advertisements.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.advertisements._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.advertisements.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create advertisement</button>
        </div>
    </form>
</div>
@endsection
