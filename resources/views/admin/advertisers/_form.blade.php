@php $item = $advertiser ?? null; @endphp

<div class="panel space-y-5">
    <div>
        <label for="name" class="field-label">Company name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $item->name ?? '') }}"
               required maxlength="160" class="field">
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="contact_email" class="field-label">Contact email</label>
            <input id="contact_email" type="email" name="contact_email"
                   value="{{ old('contact_email', $item->contact_email ?? '') }}" required maxlength="190" class="field">
            <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
        </div>

        <div>
            <label for="contact_phone" class="field-label">Phone (optional)</label>
            <input id="contact_phone" type="text" name="contact_phone"
                   value="{{ old('contact_phone', $item->contact_phone ?? '') }}" maxlength="40" class="field">
        </div>
    </div>

    <div>
        <label for="website" class="field-label">Website</label>
        <input id="website" type="url" name="website" value="{{ old('website', $item->website ?? '') }}"
               maxlength="255" class="field">
    </div>

    <label class="flex items-center gap-3 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))
               class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
        Active advertiser
    </label>
</div>
