<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SeoController extends Controller
{
    /** Page keys the application actually looks up. */
    public const KNOWN_PAGES = [
        'home' => 'Home page',
        'explore' => 'Explore / all vlogs',
        'categories' => 'Categories index',
        'creators' => 'Creators directory',
        'about' => 'About',
        'faq' => 'FAQ',
        'contact' => 'Contact',
        'takedown' => 'Copyright takedown',
    ];

    public function index()
    {
        $entries = SeoMeta::orderBy('page')->paginate(30);

        $missing = array_diff(array_keys(self::KNOWN_PAGES), SeoMeta::pluck('page')->all());

        return view('admin.seo.index', [
            'entries' => $entries,
            'knownPages' => self::KNOWN_PAGES,
            'missing' => $missing,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.seo.create', [
            'knownPages' => self::KNOWN_PAGES,
            'prefill' => $request->string('page')->toString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEntry($request);
        unset($data['og_image_file']);

        $data['og_image'] = $this->storeImage($request);

        $entry = SeoMeta::create($data);

        ActivityLogger::log('seo.create', $entry, 'Added SEO entry for '.$entry->page);

        return redirect()->route('admin.seo.index')->with('success', 'SEO entry saved.');
    }

    public function edit(SeoMeta $seo)
    {
        return view('admin.seo.edit', [
            'seo' => $seo,
            'knownPages' => self::KNOWN_PAGES,
        ]);
    }

    public function update(Request $request, SeoMeta $seo)
    {
        $data = $this->validateEntry($request, $seo->id);
        unset($data['og_image_file']);

        if ($image = $this->storeImage($request)) {
            if ($seo->og_image && ! str_starts_with($seo->og_image, 'http')) {
                Storage::disk('public')->delete($seo->og_image);
            }

            $data['og_image'] = $image;
        }

        $seo->update($data);

        ActivityLogger::log('seo.update', $seo, 'Updated SEO entry for '.$seo->page);

        return back()->with('success', 'SEO entry updated.');
    }

    public function destroy(SeoMeta $seo)
    {
        $page = $seo->page;

        if ($seo->og_image && ! str_starts_with($seo->og_image, 'http')) {
            Storage::disk('public')->delete($seo->og_image);
        }

        $seo->delete();

        ActivityLogger::log('seo.delete', null, 'Deleted SEO entry for '.$page);

        return back()->with('success', 'SEO entry deleted.');
    }

    protected function validateEntry(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'page' => ['required', 'string', 'max:120', Rule::unique('seo_meta', 'page')->ignore($ignoreId)],
            'title' => ['nullable', 'string', 'max:70'],
            'description' => ['nullable', 'string', 'max:170'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_type' => ['required', 'in:website,article,video.other,profile'],
            'no_index' => ['nullable', 'boolean'],
            'og_image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], [
            'title.max' => 'Keep the title under 70 characters so search engines do not cut it off.',
            'description.max' => 'Keep the description under 170 characters.',
        ]);
    }

    protected function storeImage(Request $request): ?string
    {
        return $request->hasFile('og_image_file')
            ? $request->file('og_image_file')->store('seo', 'public')
            : null;
    }
}
