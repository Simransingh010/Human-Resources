<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request and ensure JSON responses for API routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        
        try {
            $response = $next($request);
            
            // If response is not JSON and we're on an API route, convert it
            if ($request->is('api/*') && !$response->headers->get('Content-Type')) {
                $response->headers->set('Content-Type', 'application/json');
            }
            
            return $response;
        } catch (\Throwable $e) {
            \Log::error('API Exception caught in middleware: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => $e->getMessage() ?: 'An unexpected error occurred',
                'error_code' => 'INTERNAL_ERROR',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
