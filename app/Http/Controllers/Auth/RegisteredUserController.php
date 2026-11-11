<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190', 'unique:'.User::class],
            'username' => [
                'nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/',
                Rule::unique(User::class, 'username'),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'username.regex' => 'Usernames use lowercase letters, numbers and underscores only.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            // Without a role the account is neither viewer, creator nor staff:
            // role checks fail and it shows as "None" in the admin user list.
            'role_id' => Role::where('slug', 'user')->value('id'),
            // Channel pages, mentions and the creator application all key off
            // a username, so claim one now instead of leaving it blank.
            'username' => $validated['username'] ?? $this->uniqueUsername($validated['name'], $validated['email']),
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect($user->homeRoute())
            ->with('success', 'Welcome to '.setting('site_name', config('app.name')).'. Check your email to verify your address.');
    }

    /**
     * Build a free username from the display name, falling back to the email
     * local part and then to a short random suffix.
     */
    protected function uniqueUsername(string $name, string $email): string
    {
        $base = Str::of($name)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        if (strlen($base) < 3) {
            $base = Str::of(Str::before($email, '@'))->lower()->ascii()
                ->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
        }

        $base = substr($base ?: 'viewer', 0, 24) ?: 'viewer';
        $candidate = $base;

        for ($i = 0; User::where('username', $candidate)->exists(); $i++) {
            $candidate = $base.'_'.Str::lower(Str::random(4));

            if ($i > 20) {
                $candidate = 'viewer_'.Str::lower(Str::random(10));
                break;
            }
        }

        return $candidate;
    }
}
