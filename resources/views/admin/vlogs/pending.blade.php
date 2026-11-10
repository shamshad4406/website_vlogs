@extends('layouts.admin')

@section('content')
<h1>Pending Vlog Approvals</h1>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Creator</th>
                    <th>Category</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vlogs as $vlog)
                <tr>
                    <td>{{ Str::limit($vlog->title, 40) }}</td>
                    <td>{{ $vlog->creator->name }}</td>
                    <td>{{ $vlog->category->name }}</td>
                    <td>{{ $vlog->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.vlogs.show', $vlog) }}" class="btn btn-sm btn-info">View</a>
                        <form action="{{ route('admin.vlogs.approve', $vlog) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form action="{{ route('admin.vlogs.reject', $vlog) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $vlogs->links() }}
    </div>
</div>
@endsection
