<?php

namespace App\Http\Middleware;

use App\Models\Chapter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return Redirect::route('admin.auth.login')->with('error', 'YOU ARE NOT ALLOWED TO ACCESS THIS PAGE');
        } else {
            $user = Auth::user();

            $chapter = $request->query->get('chapter');
            if ($chapter != null && !$user->hasRole('super-admin')) {
                abort(404, 'PAGE REQUESTED WAS NOT FOUND');
            }

            if (!$user->hasRole('super-admin')) {
                abort(404, 'PAGE REQUESTED WAS NOT FOUND');
            }

        }
        return $next($request);
    }
}
