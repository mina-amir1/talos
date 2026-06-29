<?php

namespace App\Jobs;

use App\Services\SmtpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SendTalosEmail implements ShouldQueue
{
    use Queueable;

    public int   $tries   = 3;
    public int   $timeout = 30;
    public array $backoff  = [60, 300]; // 1 min, then 5 min

    public function __construct(
        public readonly string   $to,
        public readonly Mailable $mailable,
    ) {}

    public function handle(SmtpService $smtp): void
    {
        if (! $smtp->configure()) {
            // SMTP is disabled or unconfigured — drop silently, don't retry
            $this->delete();
            return;
        }

        // Force the MailManager to rebuild the transport with the fresh config.
        // Without this, a long-running worker reuses the cached transport from
        // its first job and ignores credential changes made while it's running.
        Mail::purge('smtp');

        Mail::to($this->to)->send($this->mailable);
    }
}
