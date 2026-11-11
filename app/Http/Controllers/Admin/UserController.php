<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request)
    {
        $users = User::with('role')
            ->withCount(['vlogs', 'comments'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(fn ($i) => $i->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $request->string('role'))))
            ->when($request->string('state')->toString() === 'banned', fn ($q) => $q->whereNotNull('banned_at'))
            ->when($request->string('state')->toString() === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'filters' => $request->only(['q', 'role', 'state']),
            'counts' => [
                'all' => User::count(),
                'banned' => User::whereNotNull('banned_at')->count(),
                'unverified' => User::whereNull('email_verified_at')->count(),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')],
            'username' => ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', Rule::unique('users')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'mark_verified' => ['nullable', 'boolean'],
        ], [
            'username.regex' => 'Use lowercase letters, numbers and underscores only.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'username' => $data['username'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'email_verified_at' => ! empty($data['mark_verified']) ? now() : null,
        ]);

        ActivityLogger::log('user.create', $user, 'Created account for '.$user->email);

        return redirect()->route('admin.users.show', $user)->with('success', 'User created.');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load(['role', 'creatorProfile', 'creatorApplication'])
            ->loadCount(['vlogs', 'comments', 'followers', 'following', 'vlogReports']);

        $recentVlogs = $user->vlogs()->latest()->take(5)->get();
        $recentComments = $user->comments()->with('vlog:id,title,slug')->latest()->take(5)->get();
        $strikes = $user->strikes()->latest()->take(5)->get();

        return view('admin.users.show', compact('user', 'recentVlogs', 'recentComments', 'strikes'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'username' => ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', Rule::unique('users')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'bio' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'username.regex' => 'Use lowercase letters, numbers and underscores only.',
        ]);

        $before = $user->only(['name', 'email', 'username', 'role_id', 'is_active']);

        $user->fill([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'username' => $data['username'] ?? null,
            'role_id' => $data['role_id'],
            'bio' => $data['bio'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        ActivityLogger::log('user.update', $user, 'Updated '.$user->email, $before, $user->only(array_keys($before)));

        return back()->with('success', 'User updated.');
    }

    public function ban(Request $request, User $user)
    {
        $this->authorize('ban', $user);

        $data = $request->validate([
            'ban_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $user->update([
            'banned_at' => now(),
            'ban_reason' => $data['ban_reason'],
            'is_active' => false,
        ]);

        ActivityLogger::log('user.ban', $user, 'Banned '.$user->email, [], ['reason' => $data['ban_reason']]);

        return back()->with('success', 'Account banned. The user is signed out on their next request.');
    }

    public function unban(User $user)
    {
        $this->authorize('ban', $user);

        $user->update(['banned_at' => null, 'ban_reason' => null, 'is_active' => true]);

        ActivityLogger::log('user.unban', $user, 'Lifted ban on '.$user->email);

        return back()->with('success', 'Ban lifted.');
    }

    /**
     * Manually confirm an email address. Needed while real mail delivery is
     * not configured, and for support cases where the link never arrives.
     */
    public function verifyEmail(User $user)
    {
        $this->authorize('update', $user);

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'That email is already verified.');
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        ActivityLogger::log('user.verify_email', $user, 'Manually verified '.$user->email);

        return back()->with('success', 'Email marked as verified.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $email = $user->email;
        $user->delete();

        ActivityLogger::log('user.delete', null, 'Deleted account '.$email);

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
