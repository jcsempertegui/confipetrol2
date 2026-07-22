<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use AuditLog;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // --- LOGICA DE SEGURIDAD DE SESIONES ---
        $user = Auth::user();
        $limit = $user->max_sessions ?? 1;
        $currentSessionId = $request->session()->getId();

        $activeSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->orderBy('last_activity', 'desc')
            ->get();

        $sessionsToKeep = max(0, $limit - 1);
        $message = null;

        if ($activeSessions->count() > $sessionsToKeep) {
            $sessionsToDelete = $activeSessions->slice($sessionsToKeep);
            $deletedCount = $sessionsToDelete->count();
            $idsToDelete = $sessionsToDelete->pluck('id')->toArray();

            if (! empty($idsToDelete)) {
                DB::table('sessions')->whereIn('id', $idsToDelete)->delete();
                $message = "Atención: Se ha cerrado sesión en {$deletedCount} dispositivo(s) antiguo(s) por límite de seguridad.";
            }
        }
        // --- FIN LOGICA ---

        $this->logActivity(
            'ACCESO',
            'INICIO_SESION',
            'Inicio de sesión: '.$user->login,
            $user->id,
            null,
            [
                'navegador' => Str::limit((string) $request->userAgent(), 500, ''),
                'huella_sesión' => hash('sha256', $currentSessionId),
            ]
        );

        if ($message) {
            return redirect()->route('home')->with('warning', $message);
        }

        return redirect()->route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::user()) {
            $this->logActivity(
                'ACCESO',
                'CIERRE_SESION',
                'Cierre de sesión: '.Auth::user()->login,
                Auth::id()
            );
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
