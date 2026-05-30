<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AuthenticatedSessionController extends Controller
{
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

            if (!empty($idsToDelete)) {
                DB::table('sessions')->whereIn('id', $idsToDelete)->delete();
                $message = "Atención: Se ha cerrado sesión en {$deletedCount} dispositivo(s) antiguo(s) por límite de seguridad.";
            }
        }
        // --- FIN LOGICA ---

        session(['branch_user_id' => $user->branch_id]);

        Log::create([
            'user_id'        => $user->id,
            'modulo'         => 'ACCESO',
            'accion'         => 'INICIO_SESION',
            'descripcion'    => 'Inicio de sesión: ' . $user->login,
            'ip'             => $request->ip(),
            'valores_nuevos' => json_encode(['user_agent' => $request->header('User-Agent')]),
        ]);

        if ($message) {
            return redirect()->route('home')->with('warning', $message);
        }

        return redirect()->route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::user()) {
            Log::create([
                'user_id'     => Auth::user()->id,
                'modulo'      => 'ACCESO',
                'accion'      => 'CIERRE_SESION',
                'descripcion' => 'Cierre de sesión: ' . Auth::user()->login,
                'ip'          => $request->ip(),
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
