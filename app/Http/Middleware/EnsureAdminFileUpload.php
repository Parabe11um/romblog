<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminFileUpload
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $panel = Filament::getPanel('admin');

        $user = $request->user($panel->getAuthGuard());

        abort_unless(
            $user instanceof FilamentUser
                && $user->canAccessPanel($panel),
            403
        );

        return $next($request);
    }
}
