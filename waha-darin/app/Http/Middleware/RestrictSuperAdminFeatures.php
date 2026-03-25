<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictSuperAdminFeatures
{
    public function handle(Request $request, Closure $next)
    {
        // Only enforce these restrictions in the admin area.
        if (!$request->is('admin') && !$request->is('admin/*')) {
            return $next($request);
        }

        if ($this->requiresSuperAdmin($request)) {
            $user = $request->user();
            // If not authenticated yet, let Voyager/auth middleware handle redirects.
            if (!$user) {
                return $next($request);
            }
            $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

            if (!$isSuperAdmin) {
                // Prefer a friendly Voyager-styled page for browser requests.
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'Only Super Admin is allowed for this action/page.',
                    ], 403);
                }

                // For bulk delete, return the same message page (instead of a blank 403).
                return response()->view('voyager::errors.superadmin-only', [], 403);
            }
        }

        return $next($request);
    }

    private function requiresSuperAdmin(Request $request): bool
    {
        // Restrict "Manage Plans" (Voyager BREAD)
        if ($request->is('admin/plans') || $request->is('admin/plans/*')) {
            return true;
        }

        // Restrict "Bank Account" (Voyager BREAD) – subscription checkout bank details
        if ($request->is('admin/bank_account_details') || $request->is('admin/bank_account_details/*')) {
            return true;
        }

        // Restrict "Import Books" tool
        if ($request->is('admin/book-import') || $request->is('admin/book-import/*')) {
            return true;
        }

        // Restrict Voyager bulk delete (DELETE /admin/{slug}/0 with ids in request)
        if ($request->isMethod('delete') && $request->filled('ids')) {
            $segments = $request->segments(); // e.g. ['admin', 'books', '0']
            if (count($segments) >= 3 && end($segments) === '0') {
                return true;
            }
        }

        return false;
    }
}

