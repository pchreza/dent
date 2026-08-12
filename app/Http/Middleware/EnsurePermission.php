<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    public function __construct(private readonly AuthorizationService $authorization) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->authorization->allows($user, $permission)) {
            abort(403, 'شما مجوز انجام این عملیات را ندارید.');
        }

        return $next($request);
    }
}
