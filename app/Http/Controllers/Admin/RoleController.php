<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Roles and permissions screen. Puts the existing roles / permissions /
 * role_permissions tables to work so staff roles such as Moderator,
 * Support and Finance can be given exactly the access they need.
 */
class RoleController extends Controller
{
    /** Roles that must keep working for the platform to function. */
    public const PROTECTED_SLUGS = ['admin', 'creator', 'user'];

    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissionGroups' => $this->groupedPermissions(),
            'protected' => self::PROTECTED_SLUGS,
        ]);
    }

    public function create()
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->groupedPermissions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles')],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);
        $this->flush();

        ActivityLogger::log('role.create', $role, 'Created role '.$role->name);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->groupedPermissions(),
            'isProtected' => in_array($role->slug, self::PROTECTED_SLUGS, true),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $before = ['name' => $role->name, 'permissions' => $role->permissions->pluck('slug')->all()];

        // The slug drives authorization, so it is never renamed for core roles.
        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);
        $this->flush();

        ActivityLogger::log('role.update', $role, 'Updated role '.$role->name, $before, [
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('slug')->all(),
        ]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->slug, self::PROTECTED_SLUGS, true)) {
            return back()->with('error', 'The '.$role->name.' role is part of the platform and cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Move the '.$role->users()->count().' account(s) using this role first.');
        }

        $name = $role->name;
        $role->permissions()->detach();
        $role->delete();
        $this->flush();

        ActivityLogger::log('role.delete', null, 'Deleted role '.$name);

        return back()->with('success', 'Role deleted.');
    }

    /** @return array<string, Collection> */
    protected function groupedPermissions(): array
    {
        return Permission::orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => Str::headline(Str::before($p->slug, '.') ?: 'general'))
            ->all();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 2;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function flush(): void
    {
        Role::pluck('id')->each(fn ($id) => Cache::forget("role.{$id}.permissions"));
    }
}
