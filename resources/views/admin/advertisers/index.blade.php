@extends('layouts.admin')
@section('title', 'Advertisers')

@section('content')
    <x-page-header title="Advertisers" subtitle="The companies buying placements.">
        <a href="{{ route('admin.advertisers.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New advertiser
        </a>
    </x-page-header>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Name</th><th>Contact</th><th class="text-right">Campaigns</th><th>Active</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($advertisers as $advertiser)
                    <tr>
                        <td class="font-medium">{{ $advertiser->name }}</td>
                        <td class="text-sm">
                            <span class="block">{{ $advertiser->contact_email }}</span>
                            @if ($advertiser->contact_phone)
                                <span class="block text-xs text-ink-400">{{ $advertiser->contact_phone }}</span>
                            @endif
                        </td>
                        <td class="text-right tabular-nums">{{ $advertiser->campaigns_count }}</td>
                        <td>
                            <x-badge :tone="$advertiser->is_active ? 'success' : 'muted'">
                                {{ $advertiser->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.advertisers.edit', $advertiser) }}" class="btn-ghost btn-sm">Edit</a>
                            <x-confirm-form :action="route('admin.advertisers.destroy', $advertiser)" method="DELETE"
                                            message="Delete this advertiser?" label="Delete" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-ink-500">No advertisers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $advertisers->links() }}</div>
@endsection
