<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

/**
 * CheckRoleAgent.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class CheckRoleAgent
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
            return redirect('auth/login');
        }
        if ($request->user() != null && $request->user()->role == 'agent' || $request->user()->role == 'admin') {
            return $next($request);
        }

        return redirect('/')->with('fails', 'You are not Autherised');
    }
}
