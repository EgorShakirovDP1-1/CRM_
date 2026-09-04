<?php

namespace App\Http\Middleware;

use App\Models\OrganizationUser;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $requestedId = $request->header('X-Organization-Id')
            ?: ($request->hasSession() ? $request->session()->get('organization_id') : null);
        $memberships = OrganizationUser::query()
            ->where('user_id', $user->id)
            ->where('status', 'active');
        $membership = $requestedId
            ? (clone $memberships)->where('organization_id', $requestedId)->first()
            : $memberships->oldest('joined_at')->first();

        if ($membership === null) {
            abort(403, 'You do not belong to an active organization.');
        }
        if ($request->hasSession()) {
            $request->session()->put('organization_id', $membership->organization_id);
        }
        $this->context->set($membership);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("select set_config('app.current_organization_id', ?, false)", [$membership->organization_id]);
        }

        try {
            return $next($request);
        } finally {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("select set_config('app.current_organization_id', '', false)");
            }
            $this->context->clear();
        }
    }
}
