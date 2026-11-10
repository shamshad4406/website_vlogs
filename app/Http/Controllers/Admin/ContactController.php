<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactEnquiry;

class ContactController extends Controller
{
    public function index()
    {
        $enquiries = ContactEnquiry::latest()->paginate(20);
        return view('admin.contact.index', compact('enquiries'));
    }

    public function show(ContactEnquiry $enquiry)
    {
        $enquiry->update(['status' => 'read']);
        return view('admin.contact.show', compact('enquiry'));
    }

    public function destroy(ContactEnquiry $enquiry)
    {
        $enquiry->delete();
        return redirect()->route('admin.contact.index')->with('success', 'Enquiry deleted successfully.');
    }
}
