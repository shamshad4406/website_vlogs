<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PlatformNotification;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email templates plus a broadcast tool for announcements.
 *
 * Broadcasts go out in chunks and never fail the request if one address
 * bounces. With MAIL_MAILER=log they are written to the log instead of sent,
 * which is the safe default until SMTP is configured.
 */
class EmailToolController extends Controller
{
    public function index()
    {
        return view('admin.email-tools.index', [
            'templates' => EmailTemplate::orderBy('name')->get(),
            'mailer' => config('mail.default'),
            'audiences' => [
                'all_users' => 'All users ('.User::whereNull('banned_at')->count().')',
                'creators' => 'Creators only ('.User::whereHas('creatorProfile')->count().')',
                'viewers' => 'Viewers only ('.User::whereDoesntHave('creatorProfile')->count().')',
                'newsletter' => 'Newsletter subscribers ('.NewsletterSubscriber::where('is_active', true)->count().')',
            ],
        ]);
    }

    public function edit(EmailTemplate $template)
    {
        return view('admin.email-tools.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        ActivityLogger::log('email.template', $template, 'Updated template '.$template->key);

        return back()->with('success', 'Template saved.');
    }

    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:all_users,creators,viewers,newsletter'],
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'min:20', 'max:20000'],
            'link' => ['nullable', 'url', 'max:255'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Tick the confirmation box before sending to a whole audience.',
        ]);

        $sent = 0;
        $failed = 0;

        $send = function (string $email, ?string $name) use ($data, &$sent, &$failed) {
            try {
                Mail::to($email)->send(new PlatformNotification(
                    $data['subject'],
                    $data['body'],
                    $data['link'] ?? null
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Broadcast email failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        };

        if ($data['audience'] === 'newsletter') {
            NewsletterSubscriber::where('is_active', true)
                ->chunkById(200, fn ($rows) => $rows->each(fn ($r) => $send($r->email, $r->name)));
        } else {
            User::query()
                ->whereNull('banned_at')
                ->when($data['audience'] === 'creators', fn ($q) => $q->whereHas('creatorProfile'))
                ->when($data['audience'] === 'viewers', fn ($q) => $q->whereDoesntHave('creatorProfile'))
                ->chunkById(200, fn ($rows) => $rows->each(fn ($u) => $send($u->email, $u->name)));
        }

        ActivityLogger::log('email.broadcast', null, "Broadcast to {$data['audience']}: {$sent} sent, {$failed} failed", [], [
            'subject' => $data['subject'],
        ]);

        $note = config('mail.default') === 'log'
            ? ' Mail is set to the log driver, so these were written to storage/logs instead of sent.'
            : '';

        return back()->with('success', "Broadcast finished: {$sent} sent, {$failed} failed.".$note);
    }
}
