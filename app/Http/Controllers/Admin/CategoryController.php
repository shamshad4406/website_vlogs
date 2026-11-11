<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['vlogs' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name']);

        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('categories', 'public');
        }

        unset($data['image_file']);

        $category = Category::create($data);
        $this->flush();

        ActivityLogger::log('category.create', $category, 'Created category '.$category->name);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->rules($request, $category->id);
        $data['slug'] = $data['slug'] ?: $category->slug;

        if ($request->hasFile('image_file')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $data['image'] = $request->file('image_file')->store('categories', 'public');
        }

        unset($data['image_file']);

        $category->update($data);
        $this->flush();

        ActivityLogger::log('category.update', $category, 'Updated category '.$category->name);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $count = $category->vlogs()->count();

        if ($count > 0) {
            return back()->with('error', "This category still holds {$count} vlog(s). Move them first, or deactivate the category instead.");
        }

        $name = $category->name;
        $category->delete();
        $this->flush();

        ActivityLogger::log('category.delete', null, 'Deleted category '.$name);

        return back()->with('success', 'Category deleted.');
    }

    protected function rules(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', Rule::unique('categories', 'slug')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], [
            'slug.regex' => 'The slug may use lowercase letters, numbers and hyphens only.',
            'color.regex' => 'Use a hex colour such as #4338CA.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function flush(): void
    {
        Cache::forget('categories.active');
        Cache::forget('categories.with_counts');
        Cache::forget('home.blocks');
    }
}
