<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = "Bearer " . Cache::get('verify_token');
        $token1 = $request->header('Authorization');
        if($token == $token1)
            return $next($request);
        else
            return response()->json(['message' => "Not Authorized"],402);
    }
}
