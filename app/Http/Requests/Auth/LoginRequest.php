<?php

namespace App\Http\Requests\Auth;

use App\Models\Log;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => trim((string) $this->input('login')),
        ]);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = ['login' => $this->input('login'), 'password' => $this->input('password')];

        if (! Auth::validate($credentials)) {
            RateLimiter::hit($this->throttleKey());
            try {
                Log::create([
                    'user_id' => User::where('login', $this->input('login'))->value('id'),
                    'actor_login' => $this->input('login'), 'modulo' => 'ACCESO', 'accion' => 'INTENTO_FALLIDO',
                    'descripcion' => 'Intento de inicio de sesión con credenciales incorrectas', 'ip' => $this->ip(),
                    'valores_nuevos' => ['navegador' => Str::limit((string) $this->userAgent(), 500, '')],
                ]);
            } catch (\Throwable $exception) {
                file_put_contents(storage_path('logs/audit-fallback.log'), json_encode([
                    'timestamp' => now()->toIso8601String(), 'actor_login' => $this->input('login'),
                    'module' => 'ACCESO', 'action' => 'INTENTO_FALLIDO', 'ip' => $this->ip(),
                    'database_error' => $exception->getMessage(),
                ], JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Obtenemos el usuario autenticado
        $user = User::where('login', $this->input('login'))->first();

        if ($user && $user->status == 0) {
            throw ValidationException::withMessages([
                'login' => 'Tu cuenta está inactiva. Contacta con el administrador.',
            ]);
        }

        // Ahora sí, intentamos autenticar al usuario
        Auth::attempt($credentials, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
