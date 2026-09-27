<?php

namespace SolverCircle\LogViewer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasskeyIsAuthorized
{
    /**
     * Handle an incoming request and ensure passkey authorization if configured.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $passkey = Config::get('log-viewer.passkey');

        // If no passkey is configured, bypass protection
        if (empty($passkey)) {
            return $next($request);
        }

        // Allow passkey authentication route
        if ($request->routeIs('log-viewer.auth')) {
            return $next($request);
        }

        $isAuthorized = $request->session()->get('log_viewer_passkey_authorized') === true;
        $headerPasskey = $request->header('X-Log-Viewer-Passkey');
        $queryPasskey = $request->query('passkey');

        if ($isAuthorized || ($headerPasskey && $headerPasskey === $passkey) || ($queryPasskey && $queryPasskey === $passkey)) {
            return $next($request);
        }

        if ($request->wantsJson() || $request->expectsJson() || $request->is('*/api/*')) {
            return response()->json([
                'error' => 'Unauthorized. Valid passkey required.',
                'passkey_required' => true,
            ], 401);
        }

        return response()->view('log-viewer::auth', [
            'route_prefix' => Config::get('log-viewer.route_prefix', 'log-viewer'),
            'theme' => Config::get('log-viewer.theme', 'auto'),
        ], 401);
    }
}
