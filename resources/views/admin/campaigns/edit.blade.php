@extends('layouts.admin')
@section('title', 'Edit campaign')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="'Edit '.$campaign->name" :back="route('admin.campaigns.index')" back-label="Campaigns" />
    <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
        @csrf
        @method('PUT')
        @include('admin.campaigns._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.campaigns.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save campaign</button>
        </div>
    </form>
</div>
@endsection
