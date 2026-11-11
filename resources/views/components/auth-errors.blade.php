{{--
    A summary at the top of an auth form, for when several fields failed at
    once. A lone error is left to the message under its own input, so it is
    never printed twice on the same screen.
--}}
@if ($errors->count() > 1)
    <div role="alert"
         class="mt-6 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
        <p class="flex items-center gap-2 font-semibold">
            <x-icon name="alert" class="h-4 w-4 shrink-0" />
            There are {{ $errors->count() }} problems with this form
        </p>

        <ul class="mt-1.5 list-inside list-disc space-y-0.5 pl-6">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
