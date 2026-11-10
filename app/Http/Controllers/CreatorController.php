<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vlog;
use App\Models\Follow;
use Illuminate\Support\Facades\Auth;

class CreatorController extends Controller
{
    public function show($username)
    {
        $creator = User::where('username', $username)
            ->with('creatorProfile')
            ->firstOrFail();

        $vlogs = Vlog::where('creator_id', $creator->id)
            ->where('status', 'published')
            ->with(['category'])
            ->latest('published_at')
            ->paginate(12);

        $isFollowing = false;
        if (Auth::check()) {
            $isFollowing = Follow::where('follower_id', Auth::id())
                ->where('creator_id', $creator->id)
                ->exists();
        }

        return view('frontend.creators.show', compact('creator', 'vlogs', 'isFollowing'));
    }

    public function follow(Request $request, $id)
    {
        $creator = User::findOrFail($id);

        $existingFollow = Follow::where('follower_id', Auth::id())
            ->where('creator_id', $creator->id)
            ->first();

        if ($existingFollow) {
            $existingFollow->delete();
            return response()->json(['following' => false]);
        } else {
            Follow::create([
                'follower_id' => Auth::id(),
                'creator_id' => $creator->id,
            ]);
            return response()->json(['following' => true]);
        }
    }
}
