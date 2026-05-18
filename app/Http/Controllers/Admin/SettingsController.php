<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosApiToken;
use App\Models\TalosRole;
use App\Models\TalosUser;
use App\Services\LocaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    // ── Locales ───────────────────────────────────────────────────────────

    public function locales(LocaleService $localeService)
    {
        $locales        = $localeService->all();
        $defaultLocale  = $localeService->default();

        return view('talos.settings.locales', compact('locales', 'defaultLocale'));
    }

    public function storeLocale(Request $request, LocaleService $localeService)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/'],
        ]);

        $localeService->add($request->input('code'));

        return back()->with('success', 'Locale "' . $request->input('code') . '" added.');
    }

    public function destroyLocale(string $code, LocaleService $localeService)
    {
        if ($code === $localeService->default()) {
            return back()->withErrors(['error' => 'Cannot remove the default locale.']);
        }

        $localeService->remove($code);

        return back()->with('success', 'Locale "' . $code . '" removed.');
    }

    // ── Roles ─────────────────────────────────────────────────────────────

    public function roles()
    {
        $roles = TalosRole::withCount('users')->get();

        return view('talos.settings.roles', compact('roles'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:64|unique:talos_roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $role = TalosRole::create($request->only('name', 'description', 'permissions'));

        return redirect()->route('talos.settings.roles')->with('success', 'Role created.');
    }

    public function updateRole(Request $request, int $id)
    {
        $role = TalosRole::findOrFail($id);

        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        $role->update(['permissions' => $request->permissions ?? []]);

        return response()->json(['success' => true]);
    }

    public function destroyRole(int $id)
    {
        $role = TalosRole::findOrFail($id);
        $role->delete();

        return redirect()->route('talos.settings.roles')->with('success', 'Role deleted.');
    }

    // ── Admin Users ────────────────────────────────────────────────────────

    public function users()
    {
        $users = TalosUser::with('role')->get();
        $roles = TalosRole::all();

        return view('talos.settings.users', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:64',
            'lastname'  => 'required|string|max:64',
            'email'     => 'required|email|unique:talos_users,email',
            'password'  => 'required|string|min:8|confirmed',
            'role_id'   => 'nullable|exists:talos_roles,id',
        ]);

        TalosUser::create([
            'firstname' => $request->firstname,
            'lastname'  => $request->lastname,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role_id'   => $request->role_id,
            'is_active' => true,
        ]);

        return redirect()->route('talos.settings.users')->with('success', 'User created.');
    }

    public function destroyUser(int $id)
    {
        $user = TalosUser::findOrFail($id);

        if ($user->is_super_admin) {
            return back()->withErrors(['error' => 'Cannot delete a super admin.']);
        }

        $user->delete();

        return redirect()->route('talos.settings.users')->with('success', 'User deleted.');
    }

    // ── API Tokens ─────────────────────────────────────────────────────────

    public function apiTokens()
    {
        $tokens       = TalosApiToken::with('creator')->latest()->get();
        $contentTypes = app(\App\Services\ContentTypeService::class)->all();

        return view('talos.settings.api-tokens', compact('tokens', 'contentTypes'));
    }

    public function storeApiToken(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:128',
            'type'        => 'required|in:full-access,read-only,custom',
            'expires_at'  => 'nullable|date|after:now',
            'permissions' => 'nullable|array',
        ]);

        $permissions = null;

        if ($request->type === 'custom') {
            $permissions = [];
            foreach ($request->input('permissions', []) as $uid => $ops) {
                $allowed = array_keys(array_filter((array) $ops));
                if (! empty($allowed)) {
                    $permissions[$uid] = $allowed;
                }
            }
        }

        $raw = Str::random(64);

        TalosApiToken::create([
            'name'        => $request->name,
            'type'        => $request->type,
            'token'       => hash('sha256', $raw),
            'permissions' => $permissions,
            'expires_at'  => $request->expires_at,
            'created_by'  => session('talos_user_id'),
        ]);

        return redirect()
            ->route('talos.settings.api-tokens')
            ->with('new_token', $raw)
            ->with('success', 'API token created. Copy it now — it will not be shown again.');
    }

    public function destroyApiToken(int $id)
    {
        TalosApiToken::findOrFail($id)->delete();

        return redirect()->route('talos.settings.api-tokens')->with('success', 'Token revoked.');
    }
}
