@php $item = $page ?? null; @endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="panel space-y-5 lg:col-span-2">
        <div>
            <label for="title" class="field-label">Title</label>
            <input id="title" type="text" name="title"
                   value="{{ old('title', $item->title ?? ($prefillTitle ?? '')) }}"
                   required maxlength="160" class="field">
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <label for="content" class="field-label">Content</label>
            <textarea id="content" name="content" rows="24" required maxlength="200000"
                      class="field font-mono text-sm">{{ old('content', $item->content ?? '') }}</textarea>
            <p class="field-help">
                Markdown is supported: <code>## Heading</code>, <code>**bold**</code>, <code>- list</code>,
                <code>[link](https://...)</code>. HTML in the text is escaped.
            </p>
            <x-input-error :messages="$errors->get('content')" class="mt-2" />
        </div>
    </div>

    <aside class="space-y-5">
        <div class="panel space-y-4">
            <div>
                <label for="slug" class="field-label">URL slug</label>
                <input id="slug" type="text" name="slug"
                       value="{{ old('slug', $item->slug ?? ($prefillSlug ?? '')) }}"
                       pattern="[a-z0-9\-]+" maxlength="160" class="field"
                       placeholder="Generated from the title">
                <p class="field-help">Changing this breaks existing links.</p>
                <x-input-error :messages="$errors->get('slug')" class="mt-2" />
            </div>

            <div>
                <label for="sort_order" class="field-label">Footer order</label>
                <input id="sort_order" type="number" name="sort_order" min="0" max="999"
                       value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="field">
            </div>

            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="show_in_footer" value="1"
                       @checked(old('show_in_footer', $item->show_in_footer ?? true))
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Link from the footer
            </label>

            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Published
            </label>
        </div>

        <div class="panel space-y-4">
            <h2 class="section-title">SEO</h2>

            <div>
                <label for="meta_title" class="field-label">Meta title</label>
                <input id="meta_title" type="text" name="meta_title"
                       value="{{ old('meta_title', $item->meta_title ?? '') }}" maxlength="70" class="field">
            </div>

            <div>
                <label for="meta_description" class="field-label">Meta description</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="170"
                          class="field">{{ old('meta_description', $item->meta_description ?? '') }}</textarea>
            </div>
        </div>
    </aside>
</div>
