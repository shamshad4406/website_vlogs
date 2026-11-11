@extends('layouts.admin')
@section('title', 'Edit category')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header :title="'Edit '.$category->name" :back="route('admin.categories.index')" back-label="Categories" />

    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.categories._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save category</button>
        </div>
    </form>
</div>
@endsection
