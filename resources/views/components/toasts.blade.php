<div x-data="{ items: [] }"
     @toast.window="
        const id = Date.now() + Math.random();
        items.push({ id, ...$event.detail });
        setTimeout(() => (items = items.filter((i) => i.id !== id)), 4000);
     "
     class="pointer-events-none fixed bottom-20 right-4 z-[60] flex w-80 flex-col gap-2 lg:bottom-6"
     aria-live="polite">
    <template x-for="item in items" :key="item.id">
        <div x-transition
             class="pointer-events-auto flex items-start gap-3 rounded-lg px-4 py-3 text-sm shadow-lift"
             :class="item.type === 'error'
                ? 'bg-red-600 text-white'
                : (item.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-ink-900 text-white')">
            <p class="flex-1" x-text="item.message"></p>
            <button type="button" @click="items = items.filter((i) => i.id !== item.id)"
                    class="opacity-70 hover:opacity-100" aria-label="Dismiss">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
