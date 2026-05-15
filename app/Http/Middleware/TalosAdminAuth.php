<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TalosAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('talos_user_id')) {
            return redirect()->route('talos.login');
        }

        return $next($request);
    }
}
