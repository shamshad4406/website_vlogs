@extends('layouts.admin')

@section('content')
<h1>Contact Enquiries</h1>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enquiries as $enquiry)
                <tr>
                    <td>{{ $enquiry->name }}</td>
                    <td>{{ $enquiry->email }}</td>
                    <td>{{ $enquiry->subject }}</td>
                    <td>
                        <span class="badge bg-{{ $enquiry->status == 'new' ? 'primary' : ($enquiry->status == 'read' ? 'info' : 'secondary') }}">
                            {{ $enquiry->status }}
                        </span>
                    </td>
                    <td>{{ $enquiry->created_at->format('Y-m-d') }}</td>
                    <td>
                        <a href="{{ route('admin.contact.show', $enquiry) }}" class="btn btn-sm btn-info">View</a>
                        <form action="{{ route('admin.contact.destroy', $enquiry) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $enquiries->links() }}
    </div>
</div>
@endsection
