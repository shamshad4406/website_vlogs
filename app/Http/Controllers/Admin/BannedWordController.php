<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannedWord;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BannedWordController extends Controller
{
    public function index()
    {
        return view('admin.banned-words', [
            'words' => BannedWord::orderBy('word')->paginate(50),
            'counts' => [
                'flag' => BannedWord::where('action', 'flag')->count(),
                'block' => BannedWord::where('action', 'block')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'words' => ['required', 'string', 'max:4000'],
            'action' => ['required', 'in:flag,block'],
        ]);

        $added = 0;

        foreach (preg_split('/[\n,]+/', $data['words']) as $raw) {
            $word = trim(mb_strtolower($raw));

            if ($word === '' || mb_strlen($word) > 120) {
                continue;
            }

            $created = BannedWord::firstOrCreate(['word' => $word], ['action' => $data['action']]);

            if ($created->wasRecentlyCreated) {
                $added++;
            }
        }

        Cache::forget('banned_words');
        ActivityLogger::log('moderation.banned_words', null, "Added {$added} banned word(s)");

        return back()->with('success', $added.' word(s) added to the filter.');
    }

    public function destroy(BannedWord $bannedWord)
    {
        $bannedWord->delete();
        Cache::forget('banned_words');

        ActivityLogger::log('moderation.banned_words', null, 'Removed a banned word');

        return back()->with('success', 'Word removed.');
    }
}
