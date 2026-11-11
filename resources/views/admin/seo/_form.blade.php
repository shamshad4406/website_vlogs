@php $item = $seo ?? null; @endphp

<div class="panel space-y-5">
    <div>
        <label for="page" class="field-label">Page key</label>
        <input id="page" type="text" name="page" value="{{ old('page', $item->page ?? ($prefill ?? '')) }}"
               required maxlength="120" class="field" list="known-pages" placeholder="home">
        <datalist id="known-pages">
            @foreach ($knownPages as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </datalist>
        <p class="field-help">
            Use one of the known keys, or <code>category.travel</code> / <code>tag.street-food</code>
            for a specific category or tag page.
        </p>
        <x-input-error :messages="$errors->get('page')" class="mt-2" />
    </div>

    <div>
        <label for="title" class="field-label">Title</label>
        <input id="title" type="text" name="title" value="{{ old('title', $item->title ?? '') }}"
               maxlength="70" class="field">
        <p class="field-help">Under 70 characters. The site name is appended automatically.</p>
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <label for="description" class="field-label">Meta description</label>
        <textarea id="description" name="description" rows="3" maxlength="170"
                  class="field">{{ old('description', $item->description ?? '') }}</textarea>
        <p class="field-help">Under 170 characters. This is the snippet people read in search results.</p>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <label for="keywords" class="field-label">Keywords (optional)</label>
        <input id="keywords" type="text" name="keywords" value="{{ old('keywords', $item->keywords ?? '') }}"
               maxlength="255" class="field" placeholder="travel vlogs, india, budget travel">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="canonical_url" class="field-label">Canonical URL (optional)</label>
            <input id="canonical_url" type="url" name="canonical_url"
                   value="{{ old('canonical_url', $item->canonical_url ?? '') }}" maxlength="255" class="field">
            <x-input-error :messages="$errors->get('canonical_url')" class="mt-2" />
        </div>

        <div>
            <label for="og_type" class="field-label">Open Graph type</label>
            <select id="og_type" name="og_type" required class="field">
                @foreach (['website' => 'Website', 'article' => 'Article', 'video.other' => 'Video', 'profile' => 'Profile'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('og_type', $item->og_type ?? 'website') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="og_image_file" class="field-label">Social share image</label>
        @if ($item?->og_image_url)
            <img src="{{ $item->og_image_url }}" alt="" class="mb-2 max-h-32 rounded-lg object-cover">
        @endif
        <input id="og_image_file" type="file" name="og_image_file" accept="image/*"
               class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
        <p class="field-help">1200x630 is the safe size for every network.</p>
    </div>

    <label class="flex items-start gap-3 text-sm">
        <input type="checkbox" name="no_index" value="1" @checked(old('no_index', $item->no_index ?? false))
               class="mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
        <span>
            <span class="block font-medium">Keep this page out of search engines</span>
            <span class="block text-xs text-ink-500 dark:text-ink-400">Adds noindex, nofollow.</span>
        </span>
    </label>
</div>
