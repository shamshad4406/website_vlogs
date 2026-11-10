@extends('layouts.admin')

@section('content')
<h1>Comment Reports</h1>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Comment</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                <tr>
                    <td>{{ $report->user->name }}</td>
                    <td>{{ Str::limit($report->comment->content, 30) }}</td>
                    <td>{{ $report->reason }}</td>
                    <td>
                        <span class="badge bg-{{ $report->status == 'pending' ? 'warning' : ($report->status == 'resolved' ? 'success' : 'secondary') }}">
                            {{ $report->status }}
                        </span>
                    </td>
                    <td>
                        @if($report->status == 'pending')
                        <form action="{{ route('admin.comment-reports.resolve', $report) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Resolve</button>
                        </form>
                        <form action="{{ route('admin.comment-reports.dismiss', $report) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">Dismiss</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $reports->links() }}
    </div>
</div>
@endsection
