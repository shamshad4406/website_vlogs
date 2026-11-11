<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $enquiries = ContactEnquiry::query()
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(fn ($i) => $i->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = ContactEnquiry::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.contact.index', compact('enquiries', 'counts', 'status'));
    }

    public function show(ContactEnquiry $enquiry)
    {
        if ($enquiry->status === 'new') {
            $enquiry->update(['status' => 'read']);
        }

        return view('admin.contact.show', compact('enquiry'));
    }

    public function update(Request $request, ContactEnquiry $enquiry)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,read,replied,closed'],
            'admin_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $enquiry->update($data);

        ActivityLogger::log('enquiry.update', $enquiry, 'Set enquiry to '.$data['status']);

        return back()->with('success', 'Enquiry updated.');
    }

    public function destroy(ContactEnquiry $enquiry)
    {
        $enquiry->delete();

        ActivityLogger::log('enquiry.delete', null, 'Deleted an enquiry');

        return redirect()->route('admin.contact.index')->with('success', 'Enquiry deleted.');
    }
}
