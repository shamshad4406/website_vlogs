@extends('layouts.admin')

@section('content')
<h1>Contact Enquiry Details</h1>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> {{ $enquiry->name }}</p>
                <p><strong>Email:</strong> {{ $enquiry->email }}</p>
                <p><strong>Subject:</strong> {{ $enquiry->subject }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> {{ $enquiry->status }}</p>
                <p><strong>Date:</strong> {{ $enquiry->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
        <hr>
        <p><strong>Message:</strong></p>
        <p>{{ $enquiry->message }}</p>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.contact.index') }}" class="btn btn-secondary">Back to Enquiries</a>
</div>
@endsection
