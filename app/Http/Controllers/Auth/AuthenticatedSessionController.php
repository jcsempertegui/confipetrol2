<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Log; 
use Illuminate\View\View; 
use Illuminate\Support\Facades\DB; // IMPORTANTE

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // --- INICIO LOGICA DE SEGURIDAD DE SESIONES ---
        $user = Auth::user();
        $limit = $user->max_sessions ?? 1; 
        $currentSessionId = $request->session()->getId();

        // Buscar sesiones anteriores
        $activeSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId) 
            ->orderBy('last_activity', 'desc')
            ->get();

        $sessionsToKeep = $limit - 1;
        if ($sessionsToKeep < 0) $sessionsToKeep = 0;

        $message = null; // Variable para el mensaje de alerta

        if ($activeSessions->count() > $sessionsToKeep) {
            $sessionsToDelete = $activeSessions->slice($sessionsToKeep);
            $deletedCount = $sessionsToDelete->count(); // Contamos cuántas vamos a borrar
            
            $idsToDelete = $sessionsToDelete->pluck('id')->toArray();
            if (!empty($idsToDelete)) {
                DB::table('sessions')->whereIn('id', $idsToDelete)->delete();
                
                // Preparamos el mensaje para el usuario que entra
                $message = "Atención: Se ha cerrado sesión en {$deletedCount} dispositivo(s) antiguo(s) por límite de seguridad.";
            }
        }
        // --- FIN LOGICA DE SEGURIDAD ---

        session(['branch_user_id' => Auth::user()->branch_id]);
            
        Log::create([
            'user_id' => Auth::user()->id,
            'evento' => 'Inicio de sesión',
            'ip' => $request->ip(),
            'detalle' => $request->header('User-Agent'),
        ]);

        // Redirigimos. Si hubo eliminaciones, agregamos el mensaje flash 'warning'
        if ($message) {
            return redirect()->route('home')->with('warning', $message);
        }

        return redirect()->route('home');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::user()) {
             Log::create([
                'user_id' => Auth::user()->id,
                'evento' => 'Cierre de sesión',
                'ip' => $request->ip(),
                'detalle' => $request->header('User-Agent'),
            ]);
        }
       
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}