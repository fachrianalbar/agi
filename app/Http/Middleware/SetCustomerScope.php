<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetCustomerScope
{
    /**
     * Set the customer scope from the authenticated user's session.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check() && ! session()->has('customer_scope')) {
            session()->put('customer_scope', Auth::user()->customer_id);
        }

        return $next($request);
    }
}
