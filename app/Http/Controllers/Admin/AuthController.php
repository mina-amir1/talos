<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosUser;
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
}
