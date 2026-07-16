<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();

                try {
                    Log::create([
                        'user_id' => $user->id, 'actor_login' => $user->login, 'modulo' => 'ACCESO',
                        'accion' => 'RESTABLECER_CONTRASENA', 'descripcion' => 'Contraseña restablecida mediante enlace de recuperación',
                        'modelo_id' => $user->id, 'valores_nuevos' => ['contraseña' => 'actualizada'], 'ip' => $request->ip(),
                    ]);
                } catch (\Throwable $exception) {
                    file_put_contents(storage_path('logs/audit-fallback.log'), json_encode([
                        'timestamp' => now()->toIso8601String(), 'user_id' => $user->id, 'actor_login' => $user->login,
                        'module' => 'ACCESO', 'action' => 'RESTABLECER_CONTRASENA', 'ip' => $request->ip(),
                        'database_error' => $exception->getMessage(),
                    ], JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
                }

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
