<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosUser;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('talos_user_id')) {
            return redirect()->route('talos.dashboard');
        }

        return view('talos.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = TalosUser::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        session([
            'talos_user_id'     => $user->id,
            'talos_user_name'   => $user->full_name,
            'talos_user_email'  => $user->email,
            'talos_super_admin' => $user->is_super_admin,
        ]);

        return redirect()->route('talos.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['talos_user_id', 'talos_user_name', 'talos_user_email', 'talos_super_admin']);

        return redirect()->route('talos.login');
    }

    // ── Forgot password ────────────────────────────────────────────────────

    public function showForgotPassword()
    {
        if (session('talos_user_id')) {
            return redirect()->route('talos.dashboard');
        }

        return view('talos.auth.forgot-password');
    }

    public function sendResetLink(Request $request, PasswordResetService $resetService)
    {
        $request->validate(['email' => 'required|email']);

        $sent = $resetService->send($request->email);

        if (! $sent) {
            return back()->withErrors(['email' => 'Email sending is not configured. Please contact an administrator.']);
        }

        return back()->with('success', 'If an account with that email exists, a reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('talos.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function updatePassword(Request $request, PasswordResetService $resetService)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $resetService->validate($request->email, $request->token)) {
            return back()->withErrors(['password' => 'This password reset link is invalid or has expired.']);
        }

        $user = TalosUser::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors(['password' => 'No account found for this email.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $resetService->clear($request->email);

        return redirect()->route('talos.login')->with('success', 'Password updated. You can now sign in.');
    }
}
