<?php

namespace App\Http\Middleware;

use App\Models\TalosUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TalosAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session('talos_user_id');

        if (! $userId) {
            return redirect()->route('talos.login');
        }

        $user = TalosUser::with('role')->find($userId);

        if (! $user || ! $user->is_active) {
            session()->flush();
            return redirect()->route('talos.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // Make the authenticated user available in all views and controllers
        view()->share('talosUser', $user);
        $request->attributes->set('talos_user', $user);

        if ($user->is_super_admin) {
            return $next($request);
        }

        if (! $this->isAllowed($request, $user)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden.'], 403);
            }

            return redirect()->route('talos.dashboard')
                ->withErrors(['error' => 'You do not have permission to access this section.']);
        }

        return $next($request);
    }

    private function isAllowed(Request $request, TalosUser $user): bool
    {
        $routeName   = $request->route()->getName();
        $permissions = $user->role?->permissions ?? [];

        // Dashboard is always accessible
        if ($routeName === 'talos.dashboard') {
            return true;
        }

        // Content Manager — check per content-type and per action
        if (str_starts_with($routeName, 'talos.content.')) {
            $uid     = $request->route('uid');
            $allowed = $permissions['content-manager'][$uid] ?? [];
            $action  = $this->routeToAction($routeName);
            return in_array($action, $allowed);
        }

        // Other sections — simple boolean toggle
        $section = match (true) {
            str_starts_with($routeName, 'talos.content-type-builder.') => 'content-type-builder',
            str_starts_with($routeName, 'talos.components.')           => 'components',
            str_starts_with($routeName, 'talos.media.')                => 'media',
            str_starts_with($routeName, 'talos.settings.')             => 'settings',
            default                                                     => null,
        };

        if (! $section) {
            return true;
        }

        return (bool) ($permissions['sections'][$section] ?? false);
    }

    private function routeToAction(string $routeName): string
    {
        return match ($routeName) {
            'talos.content.index',
            'talos.content.edit'      => 'read',
            'talos.content.create',
            'talos.content.store',
            'talos.content.translate' => 'create',
            'talos.content.update'    => 'update',
            'talos.content.destroy'   => 'delete',
            'talos.content.publish',
            'talos.content.unpublish' => 'publish',
            default                   => 'read',
        };
    }
}
