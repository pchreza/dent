<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSystemAdmin() !== true) {
            abort(403, 'این بخش فقط برای سوپرادمین قابل دسترسی است.');
        }

        return $next($request);
    }
}
