<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class DenyForDemo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user() != null) {
            if (Auth::user()->name == "demo") {
                return redirect("/stats/positions")->withFail("Эта страница недоступна для demo пользователя");
            }
        }

        return $next($request);
    }
}
