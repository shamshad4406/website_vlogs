<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes the admin audit trail. Every destructive or moderation action in the
 * admin panel should call this so admin_activity_logs is a real record.
 */
class ActivityLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $old = [],
        array $new = []
    ): ?AdminActivityLog {
        $admin = Auth::user();

        if (! $admin) {
            return null;
        }

        return AdminActivityLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'model_type' => $subject ? $subject::class : null,
            'model_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $old ? json_encode(self::scrub($old)) : null,
            'new_values' => $new ? json_encode(self::scrub($new)) : null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }

    /** Never write secrets into the audit trail. */
    protected static function scrub(array $values): array
    {
        $hidden = ['password', 'password_confirmation', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'details', 'tax_id'];

        foreach ($hidden as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }
}
