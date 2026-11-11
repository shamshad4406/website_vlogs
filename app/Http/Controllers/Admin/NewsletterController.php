<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $subscribers = NewsletterSubscriber::query()
            ->when($request->filled('q'), fn ($q) => $q->where('email', 'like', '%'.$request->string('q').'%'))
            ->when($request->string('state')->toString() === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->string('state')->toString() !== 'inactive', fn ($q) => $q->where('is_active', true))
            ->latest('subscribed_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.newsletter', [
            'subscribers' => $subscribers,
            'counts' => [
                'active' => NewsletterSubscriber::where('is_active', true)->count(),
                'inactive' => NewsletterSubscriber::where('is_active', false)->count(),
                'this_month' => NewsletterSubscriber::where('subscribed_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function export(): StreamedResponse
    {
        ActivityLogger::log('newsletter.export', null, 'Exported the active subscriber list');

        $filename = 'subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'subscribed_at']);

            NewsletterSubscriber::where('is_active', true)
                ->orderBy('subscribed_at')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [$row->email, $row->name, $row->subscribed_at?->toDateTimeString()]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        ActivityLogger::log('newsletter.delete', null, 'Removed a subscriber');

        return back()->with('success', 'Subscriber removed.');
    }
}
