@php $ad = $advertisement ?? null; @endphp

<div class="panel space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="title" class="field-label">Internal title</label>
            <input id="title" type="text" name="title" value="{{ old('title', $ad->title ?? '') }}"
                   required maxlength="160" class="field">
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <label for="alt_text" class="field-label">Alt text</label>
            <input id="alt_text" type="text" name="alt_text" value="{{ old('alt_text', $ad->alt_text ?? '') }}"
                   maxlength="160" class="field" placeholder="What the image shows, for screen readers">
        </div>
    </div>

    <div>
        <label for="link" class="field-label">Destination URL</label>
        <input id="link" type="url" name="link" value="{{ old('link', $ad->link ?? '') }}"
               required maxlength="500" class="field" placeholder="https://advertiser.example">
        <p class="field-help">Clicks go through a tracking redirect, so this URL is never taken from the request.</p>
        <x-input-error :messages="$errors->get('link')" class="mt-2" />
    </div>

    <div>
        <label for="image" class="field-label">Creative</label>
        @if ($ad?->image_url)
            <img src="{{ $ad->image_url }}" alt="" class="mb-2 max-h-32 rounded-lg object-cover">
        @endif
        <input id="image" type="file" name="image" accept="image/*" @if (! $ad) required @endif
               class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
        <p class="field-help">Under 2 MB. Wide formats work best for homepage and video-page slots.</p>
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="position" class="field-label">Position</label>
            <select id="position" name="position" required class="field">
                @foreach ($positions as $position)
                    <option value="{{ $position }}" @selected(old('position', $ad->position ?? '') === $position)>
                        {{ Str::headline($position) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="weight" class="field-label">Weight</label>
            <input id="weight" type="number" name="weight" min="1" max="10"
                   value="{{ old('weight', $ad->weight ?? 1) }}" class="field">
            <p class="field-help">Higher shows more often.</p>
        </div>

        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" required class="field">
                @foreach (['active' => 'Active', 'inactive' => 'Paused', 'expired' => 'Expired'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $ad->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="start_date" class="field-label">Starts</label>
            <input id="start_date" type="datetime-local" name="start_date" required
                   value="{{ old('start_date', ($ad->start_date ?? now())->format('Y-m-d\TH:i')) }}" class="field">
        </div>

        <div>
            <label for="end_date" class="field-label">Ends (optional)</label>
            <input id="end_date" type="datetime-local" name="end_date"
                   value="{{ old('end_date', $ad?->end_date?->format('Y-m-d\TH:i')) }}" class="field">
            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
        </div>

        <div>
            <label for="campaign_id" class="field-label">Campaign</label>
            <select id="campaign_id" name="campaign_id" class="field">
                <option value="">None</option>
                @foreach ($campaigns as $campaign)
                    <option value="{{ $campaign->id }}" @selected(old('campaign_id', $ad->campaign_id ?? '') == $campaign->id)>
                        {{ $campaign->name }} ({{ $campaign->advertiser?->name }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @isset($advertisement)
        <div class="grid gap-4 border-t border-ink-100 pt-4 text-sm dark:border-white/10 sm:grid-cols-3">
            <p><span class="text-ink-500">Impressions:</span> <strong class="tabular-nums">{{ number_format($advertisement->impressions_count) }}</strong></p>
            <p><span class="text-ink-500">Clicks:</span> <strong class="tabular-nums">{{ number_format($advertisement->clicks_count) }}</strong></p>
            <p><span class="text-ink-500">CTR:</span> <strong class="tabular-nums">{{ $advertisement->ctr }}%</strong></p>
        </div>
    @endisset
</div>
