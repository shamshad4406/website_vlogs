@extends('layouts.admin')

@section('content')
<h1>Edit Advertisement</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.advertisements.update', $advertisement) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="{{ $advertisement->title }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control">
                @if($advertisement->image)
                <img src="{{ asset('storage/' . $advertisement->image) }}" class="img-thumbnail mt-2" style="max-height: 100px;">
                @endif
            </div>
            <div class="mb-3">
                <label class="form-label">Link</label>
                <input type="url" name="link" class="form-control" value="{{ $advertisement->link }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <select name="position" class="form-select" required>
                    <option value="homepage" {{ $advertisement->position == 'homepage' ? 'selected' : '' }}>Homepage</option>
                    <option value="sidebar" {{ $advertisement->position == 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                    <option value="video_page" {{ $advertisement->position == 'video_page' ? 'selected' : '' }}>Video Page</option>
                    <option value="category_page" {{ $advertisement->position == 'category_page' ? 'selected' : '' }}>Category Page</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $advertisement->start_date->format('Y-m-d') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $advertisement->end_date ? $advertisement->end_date->format('Y-m-d') : '' }}">
            </div>
            <button type="submit" class="btn btn-primary">Update Advertisement</button>
            <a href="{{ route('admin.advertisements.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
