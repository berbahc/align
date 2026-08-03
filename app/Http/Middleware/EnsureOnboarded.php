<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Leitet neu registrierte Nutzer einmalig ins Onboarding.
 *
 * Ohne diese Weiche landet eine frische Registrierung direkt auf dem leeren
 * Dashboard. Greift nur, solange `onboarded_at` nicht gesetzt ist — und das
 * wird auch beim Überspringen gesetzt, damit niemand in einer Schleife hängt.
 */
class EnsureOnboarded
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->onboarded_at === null) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
