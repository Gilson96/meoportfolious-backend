<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

abstract class ResolveSession
{
    protected string $header;

    protected string $modelClass;

    protected string $label;

    public function handle(Request $request, Closure $next): Response
    {
        $session = $this->findActiveSession($request, $this->header, $this->modelClass, $this->label);

        app()->instance($this->modelClass, $session);

        return $next($request);
    }

    protected function findActiveSession(Request $request, string $header, string $modelClass, string $label): Model
    {
        $sessionId = $request->header($header);

        if (! is_string($sessionId) || ! Str::isUuid($sessionId)) {
            abort(Response::HTTP_UNAUTHORIZED, "Missing or invalid {$label} session.");
        }

        $session = $modelClass::find($sessionId);

        if ($session === null) {
            abort(Response::HTTP_UNAUTHORIZED, ucfirst($label).' session not found.');
        }

        if ($session->hasExpired()) {
            abort(Response::HTTP_UNAUTHORIZED, ucfirst($label).' session has expired.');
        }

        return $session;
    }
}
