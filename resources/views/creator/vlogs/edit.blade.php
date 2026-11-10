@extends('layouts.creator')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Vlog</h2>
    <a href="{{ route('creator.vlogs.index') }}" class="btn btn-outline-secondary">Back to My Vlogs</a>
</div>

<div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">
    <div class="card-body p-4">
        <form action="{{ route('creator.vlogs.update', $vlog) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Vlog Title *</label>
                        <input type="text" name="title" class="form-control" value="{{ $vlog->title }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description *</label>
                        <textarea name="description" class="form-control" rows="5" required>{{ $vlog->description }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $vlog->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-semibold">Visibility *</label>
                            <select name="visibility" class="form-select" required>
                                <option value="public" {{ $vlog->visibility == 'public' ? 'selected' : '' }}>Public</option>
                                <option value="private" {{ $vlog->visibility == 'private' ? 'selected' : '' }}>Private</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Location</label>
                        <input type="text" name="location" class="form-control" value="{{ $vlog->location }}" placeholder="Where was this vlog filmed?">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Tags</label>
                        <div class="row">
                            @foreach($tags as $tag)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="{{ $tag->id }}" id="tag{{ $tag->id }}" {{ $vlog->tags->contains($tag->id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tag{{ $tag->id }}">{{ $tag->name }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Current Thumbnail</label>
                        <img src="{{ asset('storage/' . $vlog->thumbnail) }}" alt="Current thumbnail" class="img-fluid rounded mb-2" style="max-height: 200px;">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Update Thumbnail</label>
                        <div class="border rounded p-4 text-center" style="border-style: dashed; border-color: #667eea; background: #f8f9fa;">
                            <i class="bi bi-image" style="font-size: 2rem; color: #667eea;"></i>
                            <p class="mb-2 mt-2 text-muted">Upload new thumbnail</p>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF (Max 10MB)</small>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Status:</strong> {{ ucfirst($vlog->status) }}
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="{{ route('creator.vlogs.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-creator">
                    <i class="bi bi-check-circle me-2"></i> Update Vlog
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
