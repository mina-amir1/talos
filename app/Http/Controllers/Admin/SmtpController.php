<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\TalosSmtpSetting;
use App\Services\SmtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SmtpController extends Controller
{
    public function __construct(private SmtpService $smtp) {}

    public function index()
    {
        return view('talos.settings.smtp', ['smtp' => $this->smtp->settings()]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'host'       => 'nullable|string|max:255',
            'port'       => 'required|integer|min:1|max:65535',
            'encryption' => 'required|in:tls,ssl,none',
            'username'   => 'nullable|string|max:255',
            'from_name'  => 'required|string|max:128',
            'from_email' => 'required|email|max:255',
        ]);

        $cfg = TalosSmtpSetting::first() ?? new TalosSmtpSetting();

        $cfg->host       = $request->input('host', '');
        $cfg->port       = (int) $request->input('port', 587);
        $cfg->encryption = $request->input('encryption', 'tls');
        $cfg->username   = $request->input('username', '');
        $cfg->from_name  = $request->input('from_name', 'Talos CMS');
        $cfg->from_email = $request->input('from_email', '');
        $cfg->is_active  = $request->boolean('is_active');

        if ($request->filled('password')) {
            $cfg->password = $request->input('password');
        }

        $cfg->save();

        return back()->with('success', 'SMTP settings saved.');
    }

    public function testConnection(Request $request)
    {
        $password = trim((string) $request->input('password', ''));

        if ($password === '') {
            try {
                $password = trim((string) ($this->smtp->settings()?->password ?? ''));
            } catch (\Throwable) {
                $password = '';
            }
        }

        if ($password === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'No password provided. Enter one in the form or save your settings with a password first.',
            ]);
        }

        $result = $this->smtp->testConnection([
            'host'       => trim((string) $request->input('host', '')),
            'port'       => (int) $request->input('port', 587),
            'encryption' => $request->input('encryption', 'tls'),
            'username'   => trim((string) $request->input('username', '')),
            'password'   => $password,
        ]);

        return response()->json($result);
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate(['to' => 'required|email']);

        if (! $this->smtp->configure()) {
            return back()->with('error', 'SMTP is not configured or enabled.');
        }

        Mail::purge('smtp');

        try {
            Mail::to($request->input('to'))->send(new TestMail());

            return back()->with('success', 'Test email sent to ' . $request->input('to'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
    }
}
