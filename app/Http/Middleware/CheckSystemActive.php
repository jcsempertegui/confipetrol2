<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSystemActive
{
    public function handle(Request $request, Closure $next)
    {
        if (file_exists(storage_path('app/system_disabled.lock')) && auth()->check() && auth()->id() !== 1) {
            return response()->view('errors.maintenance', [], 503);
        }

        return $next($request);
    }
}
