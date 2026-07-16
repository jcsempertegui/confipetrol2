<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $inactiveAccount = (int) $user->status !== 1;
        $inactiveRole = $user->roles()->where('status', false)->exists();

        if ($inactiveAccount || $inactiveRole) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => $inactiveAccount
                    ? 'Tu cuenta fue desactivada. Contacta con el administrador.'
                    : 'El rol asignado a tu cuenta está inactivo. Contacta con el administrador.',
            ]);
        }

        return $next($request);
    }
}
