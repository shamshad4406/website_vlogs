@extends('layouts.admin')
@section('title', 'Site settings')

@section('content')
<div x-data="{ tab: '{{ array_key_first($schema) }}' }">
    <x-page-header title="Site settings" subtitle="These values are used across the public site immediately." />

    <div class="mb-6 flex gap-1 overflow-x-auto border-b border-ink-200 no-scrollbar dark:border-white/10">
        @foreach ($schema as $group => $fields)
            <button type="button" @click="tab = '{{ $group }}'"
                    class="shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium capitalize transition"
                    :class="tab === '{{ $group }}'
                        ? 'border-brand-700 text-brand-800 dark:border-brand-400 dark:text-brand-200'
                        : 'border-transparent text-ink-500 hover:text-ink-800 dark:text-ink-400'">
                {{ $group }}
            </button>
        @endforeach
    </div>

    @foreach ($schema as $group => $fields)
        <form x-show="tab === '{{ $group }}'" x-cloak method="POST" action="{{ route('admin.settings.update') }}"
              enctype="multipart/form-data" class="panel space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="group" value="{{ $group }}">

            @foreach ($fields as $key => $field)
                @php $value = $values[$key] ?? ''; @endphp

                <div>
                    @if ($field['type'] === 'boolean')
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked($value === '1')
                                   class="mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-medium">{{ $field['label'] }}</span>
                                @isset($field['help'])
                                    <span class="block text-xs text-ink-500 dark:text-ink-400">{{ $field['help'] }}</span>
                                @endisset
                            </span>
                        </label>

                    @elseif ($field['type'] === 'image')
                        <label for="{{ $key }}" class="field-label">{{ $field['label'] }}</label>
                        @if ($value)
                            <img src="{{ media_url($value) }}" alt="" class="mb-2 max-h-24 rounded-lg bg-ink-100 object-contain p-2 dark:bg-white/5">
                        @endif
                        <input id="{{ $key }}" type="file" name="{{ $key }}_file" accept="image/*"
                               class="field text-sm file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                        @isset($field['help'])
                            <p class="field-help">{{ $field['help'] }}</p>
                        @endisset

                    @elseif ($field['type'] === 'textarea')
                        <label for="{{ $key }}" class="field-label">{{ $field['label'] }}</label>
                        <textarea id="{{ $key }}" name="{{ $key }}" rows="3" class="field">{{ old($key, $value) }}</textarea>
                        @isset($field['help'])
                            <p class="field-help">{{ $field['help'] }}</p>
                        @endisset

                    @else
                        <label for="{{ $key }}" class="field-label">{{ $field['label'] }}</label>
                        <input id="{{ $key }}" type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text')) }}"
                               name="{{ $key }}" value="{{ old($key, $value) }}" class="field"
                               @if ($field['type'] === 'number') step="any" @endif>
                        @isset($field['help'])
                            <p class="field-help">{{ $field['help'] }}</p>
                        @endisset
                    @endif

                    <x-input-error :messages="$errors->get($key)" class="mt-2" />
                </div>
            @endforeach

            @if ($group === 'legal')
                <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    The grievance officer details are required by the Indian IT intermediary rules and are shown
                    on the contact page. Have a lawyer confirm what your jurisdiction requires.
                </p>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Save {{ $group }} settings</button>
            </div>
        </form>
    @endforeach
</div>
@endsection
