<h2 class="section-title text-danger">Delete your account</h2>
<p class="mb-5 mt-1 text-sm leading-relaxed text-ink-500 dark:text-ink-400">
    This removes your profile, comments, likes, saves, history and playlists permanently. If you are a
    creator, your vlogs go with it. Download your data first if you want a copy.
</p>

<div x-data="{ open: false }">
    <button type="button" @click="open = true" class="btn-secondary text-danger">
        <x-icon name="trash" class="h-4 w-4" /> Delete my account
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink-950/60" @click="open = false"></div>

        <div x-show="open" x-transition
             class="relative w-full max-w-lg rounded-card bg-white p-6 shadow-lift dark:bg-ink-900">
            <h3 class="font-display text-lg font-bold">Delete your account permanently?</h3>
            <p class="mt-2 text-sm text-ink-600 dark:text-ink-300">
                This cannot be undone. Enter your password to confirm.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-5 space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <x-input-label for="delete_password" value="Password" class="sr-only" />
                    <x-text-input id="delete_password" name="password" type="password"
                                  placeholder="Your password" autocomplete="current-password" />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="confirm" value="1" required
                           class="mt-0.5 rounded border-ink-300 text-danger focus:ring-red-500">
                    I understand this deletes my account and all of my content permanently.
                </label>
                <x-input-error :messages="$errors->userDeletion->get('confirm')" />

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" @click="open = false" class="btn-secondary">Cancel</button>
                    <x-danger-button>Delete my account</x-danger-button>
                </div>
            </form>
        </div>
    </div>
</div>
