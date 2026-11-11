<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Subscription could not be completed.',
        ]);

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => mb_strtolower($data['email'])]);

        if ($subscriber->exists && $subscriber->is_active) {
            return back()->with('info', 'You are already subscribed. Thanks for reading.');
        }

        $subscriber->fill([
            'name' => $data['name'] ?? $subscriber->name,
            'is_active' => true,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return back()->with('success', 'Subscribed. Look out for the weekly best-of email.');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        $subscriber->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return view('frontend.pages.unsubscribed', compact('subscriber'));
    }
}
