@extends('layouts.admin')
@section('title', 'Edit SEO entry')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="'SEO: '.$seo->page" :back="route('admin.seo.index')" back-label="SEO manager" />
    <form method="POST" action="{{ route('admin.seo.update', $seo) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.seo._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.seo.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save entry</button>
        </div>
    </form>
</div>
@endsection
