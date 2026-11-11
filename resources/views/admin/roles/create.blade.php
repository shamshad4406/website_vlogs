@extends('layouts.admin')
@section('title', 'New role')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header title="New role" :back="route('admin.roles.index')" back-label="Roles" />

    <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf
        @include('admin.roles._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create role</button>
        </div>
    </form>
</div>
@endsection
