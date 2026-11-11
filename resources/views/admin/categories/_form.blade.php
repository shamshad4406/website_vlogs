<div class="panel space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="field-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $category->name ?? '') }}"
                   required maxlength="60" class="field">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="slug" class="field-label">Slug</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}"
                   pattern="[a-z0-9\-]+" maxlength="60" class="field"
                   placeholder="Leave blank to generate from the name">
            <p class="field-help">Changing this breaks existing links.</p>
            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
        </div>
    </div>

    <div>
        <label for="description" class="field-label">Description</label>
        <textarea id="description" name="description" rows="3" maxlength="1000"
                  class="field">{{ old('description', $category->description ?? '') }}</textarea>
        <p class="field-help">Shown on the category page and used as the default meta description.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="icon" class="field-label">Icon</label>
            <select id="icon" name="icon" class="field">
                <option value="">None</option>
                @foreach (['map-pin', 'utensils', 'sparkles', 'cpu', 'gamepad', 'book-open', 'film', 'activity', 'globe', 'grid', 'video', 'heart'] as $iconName)
                    <option value="{{ $iconName }}" @selected(old('icon', $category->icon ?? '') === $iconName)>{{ $iconName }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="color" class="field-label">Colour</label>
            <input id="color" type="color" name="color" value="{{ old('color', $category->color ?? '#4338CA') }}"
                   class="field h-10 p-1">
            <x-input-error :messages="$errors->get('color')" class="mt-2" />
        </div>

        <div>
            <label for="sort_order" class="field-label">Sort order</label>
            <input id="sort_order" type="number" name="sort_order" min="0" max="999"
                   value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="field">
        </div>
    </div>

    <div>
        <label for="image_file" class="field-label">Cover image (optional)</label>
        @if (isset($category) && $category->image_url)
            <img src="{{ $category->image_url }}" alt="" class="mb-2 h-24 rounded-lg object-cover">
        @endif
        <input id="image_file" type="file" name="image_file" accept="image/*"
               class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="meta_title" class="field-label">SEO title</label>
            <input id="meta_title" type="text" name="meta_title"
                   value="{{ old('meta_title', $category->meta_title ?? '') }}" maxlength="70" class="field">
        </div>
        <div>
            <label for="meta_description" class="field-label">SEO description</label>
            <input id="meta_description" type="text" name="meta_description"
                   value="{{ old('meta_description', $category->meta_description ?? '') }}" maxlength="170" class="field">
        </div>
    </div>

    <label class="flex items-center gap-3 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))
               class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
        Show this category on the site
    </label>
</div>
