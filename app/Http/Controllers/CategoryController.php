<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Vlog;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.categories.index', compact('categories'));
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $vlogs = Vlog::where('category_id', $category->id)
            ->where('status', 'published')
            ->with(['creator', 'category'])
            ->latest('published_at')
            ->paginate(12);

        return view('frontend.categories.show', compact('category', 'vlogs'));
    }
}
