<?php

namespace App\Http\Middleware;

use Closure;

class AdminRoot
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->user()->role('adminroot')) {
            return route('login');
        }
        return $next($request);
    }
}
