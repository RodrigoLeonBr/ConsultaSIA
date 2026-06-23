<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if user must change password and is not already on change password route
        if (isset($user->must_change_password) && $user->must_change_password && 
            !$request->routeIs('password.change') && 
            !$request->routeIs('password.update') &&
            !$request->routeIs('logout')) {
            
            return redirect()->route('password.change')->with('warning', 
                'You must change your password before continuing.');
        }

        return $next($request);
    }
}