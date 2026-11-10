@extends('layouts.admin')

@section('content')
<h1>Vlog Report Details</h1>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Reporter:</strong> {{ $report->user->name }}</p>
                <p><strong>Vlog:</strong> {{ $report->vlog->title }}</p>
                <p><strong>Reason:</strong> {{ $report->reason }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> {{ $report->status }}</p>
                <p><strong>Date:</strong> {{ $report->created_at->format('Y-m-d H:i') }}</p>
                @if($report->reviewed_by)
                <p><strong>Reviewed By:</strong> {{ $report->reviewer->name }}</p>
                <p><strong>Reviewed At:</strong> {{ $report->reviewed_at->format('Y-m-d H:i') }}</p>
                @endif
            </div>
        </div>
        @if($report->description)
        <hr>
        <p><strong>Description:</strong></p>
        <p>{{ $report->description }}</p>
        @endif
    </div>
</div>

@if($report->status == 'pending')
<div class="card mt-3">
    <div class="card-body">
        <form action="{{ route('admin.vlog-reports.resolve', $report) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-success">Resolve Report</button>
        </form>
        <form action="{{ route('admin.vlog-reports.dismiss', $report) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-secondary">Dismiss Report</button>
        </form>
    </div>
</div>
@endif

<div class="mt-3">
    <a href="{{ route('admin.vlog-reports.index') }}" class="btn btn-secondary">Back to Reports</a>
</div>
@endsection
