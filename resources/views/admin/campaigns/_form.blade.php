@php $item = $campaign ?? null; @endphp

<div class="panel space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="advertiser_id" class="field-label">Advertiser</label>
            <select id="advertiser_id" name="advertiser_id" required class="field">
                <option value="">Choose one</option>
                @foreach ($advertisers as $advertiser)
                    <option value="{{ $advertiser->id }}" @selected(old('advertiser_id', $item->advertiser_id ?? '') == $advertiser->id)>
                        {{ $advertiser->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('advertiser_id')" class="mt-2" />
        </div>

        <div>
            <label for="name" class="field-label">Campaign name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $item->name ?? '') }}"
                   required maxlength="160" class="field">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="budget" class="field-label">Budget</label>
            <input id="budget" type="number" name="budget" step="0.01" min="0"
                   value="{{ old('budget', $item->budget ?? 0) }}" required class="field">
        </div>

        <div>
            <label for="currency" class="field-label">Currency</label>
            <input id="currency" type="text" name="currency" maxlength="3" minlength="3" required
                   value="{{ old('currency', $item->currency ?? setting('currency', 'INR')) }}" class="field uppercase">
        </div>

        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" required class="field">
                @foreach (['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $item->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="starts_on" class="field-label">Starts on</label>
            <input id="starts_on" type="date" name="starts_on" required
                   value="{{ old('starts_on', ($item->starts_on ?? now())->format('Y-m-d')) }}" class="field">
        </div>

        <div>
            <label for="ends_on" class="field-label">Ends on (optional)</label>
            <input id="ends_on" type="date" name="ends_on"
                   value="{{ old('ends_on', $item?->ends_on?->format('Y-m-d')) }}" class="field">
            <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
        </div>
    </div>
</div>
