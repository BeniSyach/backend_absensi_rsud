<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockPostmanRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('User-Agent') && str_contains($request->header('User-Agent'), 'Postman')) {
            return response()->json(['message' => 'Access from Postman is not allowed'], 403);
        }

        return $next($request);
    }
}
