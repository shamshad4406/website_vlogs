<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vlog;
use App\Models\User;
use App\Models\Category;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q');
        $category = $request->get('category');

        $vlogs = Vlog::where('status', 'published')
            ->with(['creator', 'category']);

        if ($query) {
            $vlogs->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        }

        if ($category) {
            $vlogs->where('category_id', $category);
        }

        $vlogs = $vlogs->latest('published_at')->paginate(12);

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.search', compact('vlogs', 'categories', 'query', 'category'));
    }
}
