@extends('layouts.admin')
@section('title', 'Creator applications')

@section('content')
    <x-page-header title="Creator applications"
                   subtitle="Oldest first. Every decision sends the applicant an email." />

    <x-tabs :items="[
        'pending' => ['label' => 'Pending', 'count' => $counts['pending'] ?? 0],
        'approved' => ['label' => 'Approved', 'count' => $counts['approved'] ?? 0],
        'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected'] ?? 0],
    ]" :current="$status" param="status" />

    @if ($applications->isEmpty())
        <x-empty-state icon="check-circle" title="Nothing here"
                       action="Back to creators" :action-url="route('admin.creators.index')">
            @if ($status === 'pending')
                Every application has been reviewed.
            @else
                No applications with this status.
            @endif
        </x-empty-state>
    @else
        <div class="space-y-5">
            @foreach ($applications as $application)
                <article class="card p-5" x-data="{ rejecting: false }">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex min-w-0 gap-3">
                            <img src="{{ $application->user?->avatar_url }}" alt=""
                                 class="h-11 w-11 shrink-0 rounded-full object-cover">
                            <div class="min-w-0">
                                <h2 class="font-semibold">{{ $application->channel_name }}</h2>
                                <p class="text-sm text-ink-500 dark:text-ink-400">
                                    @if ($application->user)
                                        <a href="{{ route('admin.users.show', $application->user) }}" class="hover:text-brand-700">
                                            {{ $application->user->name }}
                                        </a>
                                        · {{ $application->user->email }}
                                    @else
                                        Account deleted
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-ink-400">
                                    Applied {{ $application->created_at->diffForHumans() }}
                                    @if ($application->user && ! $application->user->hasVerifiedEmail())
                                        · <span class="font-semibold text-amber-600">email not verified</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <x-badge :tone="match($application->status) {
                            'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                        }">{{ ucfirst($application->status) }}</x-badge>
                    </div>

                    <dl class="mt-5 space-y-4 border-t border-ink-100 pt-4 text-sm dark:border-white/10">
                        <div>
                            <dt class="font-semibold">About them</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-ink-600 dark:text-ink-300">{{ $application->bio }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">Why they want to publish here</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-ink-600 dark:text-ink-300">{{ $application->reason }}</dd>
                        </div>
                        @if ($application->rejection_reason)
                            <div>
                                <dt class="font-semibold text-danger">Rejection reason</dt>
                                <dd class="mt-0.5 text-ink-600 dark:text-ink-300">{{ $application->rejection_reason }}</dd>
                            </div>
                        @endif
                        @if ($application->reviewed_at)
                            <div class="text-xs text-ink-400">
                                Reviewed {{ $application->reviewed_at->diffForHumans() }}
                                @if ($application->reviewer) by {{ $application->reviewer->name }} @endif
                            </div>
                        @endif
                    </dl>

                    @if ($application->status === 'pending')
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-4 dark:border-white/10">
                            <form method="POST" action="{{ route('admin.creators.approve-application', $application) }}"
                                  onsubmit="return confirm('Approve this application? The account becomes a creator immediately.')">
                                @csrf
                                <button type="submit" class="btn-primary btn-sm">
                                    <x-icon name="check" class="h-4 w-4" /> Approve
                                </button>
                            </form>

                            <button type="button" @click="rejecting = ! rejecting" class="btn-secondary btn-sm text-danger">
                                <x-icon name="x" class="h-4 w-4" /> Reject
                            </button>
                        </div>

                        <form x-show="rejecting" x-cloak method="POST"
                              action="{{ route('admin.creators.reject-application', $application) }}"
                              class="mt-4 space-y-2">
                            @csrf
                            <label for="reason-{{ $application->id }}" class="field-label">
                                What should they change before applying again?
                            </label>
                            <textarea id="reason-{{ $application->id }}" name="rejection_reason" rows="3" required
                                      minlength="10" maxlength="1000" class="field text-sm"></textarea>
                            <button type="submit" class="btn-danger btn-sm">Send rejection</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $applications->links() }}</div>
    @endif
@endsection
