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
        // Routes only super-admins may access
        $superAdminPaths = [
            'admin/users',
            'admin/users/*',
            'admin/roles',
            'admin/roles/*',
            'admin/plans',
            'admin/plans/*',
            'admin/bank_account_details',
            'admin/bank_account_details/*',
            'admin/book-import',
            'admin/book-import/*',
            'admin/database',
            'admin/database/*',
            'admin/bread',
            'admin/bread/*',
            'admin/compass',
            'admin/compass/*',
            'admin/hooks',
            'admin/menus',
            'admin/menus/*',
            'admin/media',
            'admin/media/*',
            'admin/pages',
            'admin/pages/*',
            'admin/posts',
            'admin/posts/*',
            'admin/settings',
            'admin/settings/*',
        ];

        foreach ($superAdminPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        // Restrict Voyager bulk delete
        if ($request->isMethod('delete') && $request->filled('ids')) {
            $segments = $request->segments();
            if (count($segments) >= 3 && end($segments) === '0') {
                return true;
            }
        }

        return false;
    }
}

