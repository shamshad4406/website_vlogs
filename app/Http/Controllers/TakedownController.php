<?php

namespace App\Http\Controllers;

use App\Models\TakedownRequest;
use App\Models\Vlog;
use App\Services\SeoService;
use Illuminate\Http\Request;

class TakedownController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function create()
    {
        $this->seo->forPage('takedown', [
            'title' => 'Copyright takedown request',
            'description' => 'Report content that infringes your copyright and ask for it to be removed.',
            'canonical' => route('takedown.create'),
        ]);

        return view('frontend.pages.takedown');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'claimant_name' => ['required', 'string', 'max:160'],
            'claimant_email' => ['required', 'email', 'max:190'],
            'claimant_organisation' => ['nullable', 'string', 'max:160'],
            'vlog_url' => ['required', 'url', 'max:500'],
            'work_description' => ['required', 'string', 'max:500'],
            'infringement_details' => ['required', 'string', 'min:30', 'max:4000'],
            'good_faith_statement' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'good_faith_statement.accepted' => 'You must confirm the statement of good faith.',
            'website.prohibited' => 'Your request could not be submitted.',
        ]);

        TakedownRequest::create([
            'vlog_id' => $this->resolveVlogId($data['vlog_url']),
            'claimant_name' => $data['claimant_name'],
            'claimant_email' => $data['claimant_email'],
            'claimant_organisation' => $data['claimant_organisation'] ?? null,
            'work_description' => $data['work_description'],
            'infringement_details' => $data['infringement_details']."\n\nReported URL: ".$data['vlog_url'],
            'good_faith_statement' => true,
            'status' => 'pending',
        ]);

        return back()->with(
            'success',
            'Your request has been logged. Our team reviews copyright notices within 48 hours and will reply by email.'
        );
    }

    /** Pull the vlog out of the submitted URL when we can, so review is faster. */
    protected function resolveVlogId(string $url): ?int
    {
        if (preg_match('~/vlogs/([A-Za-z0-9\-_]+)~', $url, $m)) {
            return Vlog::resolveSlug($m[1])?->id;
        }

        return null;
    }
}
