@php
    $selected = old('permissions', isset($role) ? $role->permissions->pluck('id')->all() : []);
@endphp

<div class="panel space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="field-label">Role name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $role->name ?? '') }}"
                   required maxlength="60" class="field" placeholder="Moderator">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="description" class="field-label">Description</label>
            <input id="description" type="text" name="description"
                   value="{{ old('description', $role->description ?? '') }}" maxlength="255" class="field"
                   placeholder="Reviews uploads, comments and reports.">
        </div>
    </div>

    @if (isset($isProtected) && $isProtected)
        <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
            This is a core role. You can rename it and change its permissions, but its internal slug
            (<code>{{ $role->slug }}</code>) is fixed because authorization depends on it.
        </p>
    @endif
</div>

<div class="panel mt-6">
    <h2 class="mb-4 section-title">Permissions</h2>

    <div class="space-y-6">
        @foreach ($permissionGroups as $group => $permissions)
            <fieldset>
                <legend class="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-500 dark:text-ink-400">
                    {{ $group }}
                </legend>

                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($permissions as $permission)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 text-sm transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:has-[:checked]:bg-brand-500/10">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                   @checked(in_array($permission->id, $selected))
                                   class="mt-0.5 rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                            <span>
                                <span class="block font-medium">{{ $permission->description }}</span>
                                <code class="text-xs text-ink-400">{{ $permission->slug }}</code>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
</div>
