<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($this->context->allows($permission), 403, __('crm.forbidden'));

        return $next($request);
    }
}
