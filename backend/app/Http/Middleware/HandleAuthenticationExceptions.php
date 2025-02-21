<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class HandleAuthenticationExceptions
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            return $this->errorResponse(
                'Unauthenticated',
                401
            );
        }
    }
}
