@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Advertisements</h1>
    <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary">
        <i class="bi bi-plus"></i> Add Advertisement
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Impressions</th>
                    <th>Clicks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ads as $ad)
                <tr>
                    <td>{{ $ad->title }}</td>
                    <td>{{ $ad->position }}</td>
                    <td>
                        <span class="badge bg-{{ $ad->status == 'active' ? 'success' : 'secondary' }}">
                            {{ $ad->status }}
                        </span>
                    </td>
                    <td>{{ $ad->start_date->format('Y-m-d') }}</td>
                    <td>{{ $ad->end_date ? $ad->end_date->format('Y-m-d') : '-' }}</td>
                    <td>{{ $ad->impressions_count }}</td>
                    <td>{{ $ad->clicks_count }}</td>
                    <td>
                        <a href="{{ route('admin.advertisements.edit', $ad) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.advertisements.destroy', $ad) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $ads->links() }}
    </div>
</div>
@endsection
