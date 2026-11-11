<?php

namespace App\Http\Controllers;

use App\Models\ContactEnquiry;
use App\Models\SiteSetting;
use App\Services\SeoService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function create()
    {
        $this->seo->forPage('contact', [
            'title' => 'Contact us',
            'description' => 'Questions, partnerships, press or a problem with content: get in touch.',
            'canonical' => route('contact'),
        ]);

        return view('frontend.pages.contact', [
            'supportEmail' => SiteSetting::get('support_email', config('mail.from.address')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:20', 'max:4000'],
            // Honeypot: a real person never fills this in.
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Your message could not be sent.',
        ]);

        ContactEnquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'status' => 'new',
        ]);

        return back()->with('success', 'Thanks, we have your message. We usually reply within two working days.');
    }
}
