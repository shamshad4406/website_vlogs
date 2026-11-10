@extends('layouts.admin')

@section('content')
<h1>Edit Tag</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.tags.update', $tag) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ $tag->name }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-control" value="{{ $tag->slug }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Tag</button>
            <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
