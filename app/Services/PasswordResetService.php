<?php

namespace App\Services;

use App\Jobs\SendTalosEmail;
use App\Mail\PasswordResetMail;
use App\Models\TalosUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetService
{
    const EXPIRES_MINUTES = 60;

    public function __construct(private SmtpService $smtp) {}

    public function send(string $email): bool
    {
        $user = TalosUser::where('email', $email)->first();

        if (! $user) {
            return true; // silently succeed to avoid enumeration
        }

        $cfg = $this->smtp->settings();
        if (! $cfg || ! $cfg->is_active || ! $cfg->host) {
            return false;
        }

        $token = Str::random(64);

        DB::table('talos_password_resets')
            ->where('email', $email)
            ->delete();

        DB::table('talos_password_resets')->insert([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'created_at' => now(),
        ]);

        $prefix   = config('talos.admin_prefix', 'talos');
        $resetUrl = url("/{$prefix}/reset-password/{$token}?email=" . urlencode($email));

        SendTalosEmail::dispatch($email, new PasswordResetMail($user->full_name, $resetUrl));

        return true;
    }

    public function validate(string $email, string $token): bool
    {
        $record = DB::table('talos_password_resets')->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        if (! hash_equals($record->token, hash('sha256', $token))) {
            return false;
        }

        if (now()->diffInMinutes($record->created_at) > self::EXPIRES_MINUTES) {
            return false;
        }

        return true;
    }

    public function clear(string $email): void
    {
        DB::table('talos_password_resets')->where('email', $email)->delete();
    }
}
