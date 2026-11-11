@props([
    'name' => 'password',
    'id' => null,
    'autocomplete' => 'current-password',
    'meter' => false,
    'autofocus' => false,
])

@php($fieldId = $id ?? $name)

<div
    x-data="{
        shown: false,
        value: '',
        caps: false,
        init() {
            // Pick up anything the browser filled in before Alpine started.
            this.$nextTick(() => { this.value = this.$refs.input.value; });
        },
        get score() {
            const v = this.value;
            if (!v) return 0;
            let s = 0;
            if (v.length >= 8) s++;
            if (v.length >= 12) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            return Math.min(4, s);
        },
        get label() {
            return ['Too short', 'Weak', 'Fair', 'Good', 'Strong'][this.score];
        },
    }"
    class="relative"
>
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="password"
        autocomplete="{{ $autocomplete }}"
        @if ($autofocus) autofocus @endif
        x-ref="input"
        {{-- One-way: Alpine reads the field but never writes to it, so a
             browser password autofill is not wiped when Alpine boots. --}}
        @input="value = $event.target.value"
        @change="value = $event.target.value"
        @keyup="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
        :type="shown ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'field pr-11', 'required' => true]) }}
    >

    <button
        type="button"
        @click="shown = !shown; $refs.input.focus()"
        :aria-label="shown ? 'Hide password' : 'Show password'"
        :aria-pressed="shown"
        class="absolute right-1 top-1 grid h-9 w-9 place-items-center rounded-md text-ink-400 transition hover:bg-ink-100 hover:text-ink-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:hover:bg-white/10 dark:hover:text-white"
        tabindex="-1"
    >
        <span x-show="!shown"><x-icon name="eye" class="h-4 w-4" /></span>
        <span x-show="shown" x-cloak><x-icon name="eye-off" class="h-4 w-4" /></span>
    </button>

    <p x-show="caps" x-cloak class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
        <x-icon name="alert" class="h-3.5 w-3.5" /> Caps Lock is on.
    </p>

    @if ($meter)
        <div x-show="value.length > 0" x-cloak class="mt-2.5">
            <div class="flex gap-1" aria-hidden="true">
                <template x-for="i in 4" :key="i">
                    <span
                        class="h-1 flex-1 rounded-full transition-colors duration-200"
                        :class="i <= score
                            ? ['bg-rose-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-500'][score - 1]
                            : 'bg-ink-200 dark:bg-white/10'"
                    ></span>
                </template>
            </div>
            <p class="mt-1.5 text-xs text-ink-500 dark:text-ink-400">
                Strength: <span class="font-semibold text-ink-700 dark:text-ink-200" x-text="label"></span>
            </p>
        </div>
    @endif
</div>
