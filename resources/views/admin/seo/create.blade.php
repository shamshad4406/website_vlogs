@extends('layouts.admin')
@section('title', 'New SEO entry')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header title="New SEO entry" :back="route('admin.seo.index')" back-label="SEO manager" />
    <form method="POST" action="{{ route('admin.seo.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.seo._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.seo.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save entry</button>
        </div>
    </form>
</div>
@endsection
