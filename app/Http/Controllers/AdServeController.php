<?php

namespace App\Http\Controllers;

use App\Models\AdClick;
use App\Models\Advertisement;
use Illuminate\Http\Request;

/**
 * Records an ad click and forwards the visitor to the advertiser.
 * The destination comes from the stored advertisement, never from the
 * request, so this cannot be used as an open redirect.
 */
class AdServeController extends Controller
{
    public function __invoke(Request $request, Advertisement $advertisement)
    {
        AdClick::create([
            'advertisement_id' => $advertisement->id,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'page' => substr((string) $request->headers->get('referer'), 0, 120),
            'clicked_at' => now(),
        ]);

        $advertisement->increment('clicks_count');

        if ($advertisement->campaign) {
            $advertisement->campaign->increment('spent', 0);
        }

        return redirect()->away($advertisement->link, 302, [
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
