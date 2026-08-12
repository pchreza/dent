<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureNotInstalled
{
    public function __construct(private readonly InstallationState $installationState) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installationState->isInstalled()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
