@extends('layouts.admin')
@section('title', 'Edit advertiser')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="'Edit '.$advertiser->name" :back="route('admin.advertisers.index')" back-label="Advertisers" />
    <form method="POST" action="{{ route('admin.advertisers.update', $advertiser) }}">
        @csrf
        @method('PUT')
        @include('admin.advertisers._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.advertisers.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save advertiser</button>
        </div>
    </form>
</div>
@endsection
