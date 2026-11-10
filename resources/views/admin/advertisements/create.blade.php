@extends('layouts.admin')

@section('content')
<h1>Create Advertisement</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.advertisements.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Link</label>
                <input type="url" name="link" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <select name="position" class="form-select" required>
                    <option value="homepage">Homepage</option>
                    <option value="sidebar">Sidebar</option>
                    <option value="video_page">Video Page</option>
                    <option value="category_page">Category Page</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Create Advertisement</button>
            <a href="{{ route('admin.advertisements.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
