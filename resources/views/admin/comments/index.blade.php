@extends('layouts.admin')

@section('content')
<h1>Comments</h1>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Vlog</th>
                    <th>Content</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comments as $comment)
                <tr>
                    <td>{{ $comment->user->name }}</td>
                    <td>{{ Str::limit($comment->vlog->title, 30) }}</td>
                    <td>{{ Str::limit($comment->content, 50) }}</td>
                    <td>
                        <span class="badge bg-{{ $comment->is_approved ? 'success' : 'danger' }}">
                            {{ $comment->is_approved ? 'Approved' : 'Rejected' }}
                        </span>
                    </td>
                    <td>
                        @if(!$comment->is_approved)
                        <form action="{{ route('admin.comments.approve', $comment) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        @endif
                        <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $comments->links() }}
    </div>
</div>
@endsection
