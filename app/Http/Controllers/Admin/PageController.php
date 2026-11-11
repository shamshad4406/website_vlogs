<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    /** Pages the platform expects to exist for launch. */
    public const REQUIRED = [
        'terms' => 'Terms of Service',
        'privacy' => 'Privacy Policy',
        'community-guidelines' => 'Community Guidelines',
        'copyright' => 'Copyright and Takedown Policy',
        'cookies' => 'Cookie Notice',
        'about' => 'About',
        'faq' => 'FAQ',
    ];

    public function index()
    {
        $pages = StaticPage::orderBy('sort_order')->orderBy('title')->get();

        $missing = array_diff(array_keys(self::REQUIRED), $pages->pluck('slug')->all());

        return view('admin.pages.index', compact('pages', 'missing'))->with('required', self::REQUIRED);
    }

    public function create(Request $request)
    {
        return view('admin.pages.create', [
            'prefillSlug' => $request->string('slug')->toString(),
            'prefillTitle' => self::REQUIRED[$request->string('slug')->toString()] ?? '',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $page = StaticPage::create($data);

        Cache::forget('footer_pages');
        ActivityLogger::log('page.create', $page, 'Created page '.$page->slug);

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(StaticPage $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, StaticPage $page)
    {
        $page->update($this->rules($request, $page->id));

        Cache::forget('footer_pages');
        ActivityLogger::log('page.update', $page, 'Updated page '.$page->slug);

        return back()->with('success', 'Page updated.');
    }

    public function destroy(StaticPage $page)
    {
        if (array_key_exists($page->slug, self::REQUIRED)) {
            return back()->with('error', 'This page is linked from the footer and required for launch. Deactivate it instead of deleting it.');
        }

        $slug = $page->slug;
        $page->delete();

        Cache::forget('footer_pages');
        ActivityLogger::log('page.delete', null, 'Deleted page '.$slug);

        return back()->with('success', 'Page deleted.');
    }

    protected function rules(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9\-]+$/', Rule::unique('static_pages', 'slug')->ignore($ignoreId)],
            'content' => ['required', 'string', 'max:200000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'show_in_footer' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'The slug may use lowercase letters, numbers and hyphens only.',
        ]);

        // Unticked checkboxes are absent from the payload, so make them explicit.
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
