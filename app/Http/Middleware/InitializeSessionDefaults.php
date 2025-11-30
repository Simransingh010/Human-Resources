<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InitializeSessionDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Initialize firm_id
            if (!session()->has('firm_id')) {
                $firm = $user->firms()->where('is_inactive', false)->first();
                session(['firm_id' => $firm?->id]);
            }
            
            // Initialize panel_id
            if (!session()->has('panel_id')) {
                $panel = $user->panels()->where('is_inactive', false)->where('panel_type', '2')->first();
                session(['panel_id' => $panel?->id]);
            }
            
            // Initialize default wire
            if (!session()->has('defaultwire')) {
                session(['defaultwire' => 'panel.dashboard']);
            }
        }
        
        return $next($request);
    }
}
