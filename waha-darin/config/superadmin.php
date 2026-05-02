<?php

return [
    /*
     * How to pick the "Super Admin":
     * - Prefer setting SUPER_ADMIN_EMAIL in your .env (most stable)
     * - Or set SUPER_ADMIN_ID (defaults to 1)
     */
    'email' => env('SUPER_ADMIN_EMAIL', ''),
    'id' => (int) env('SUPER_ADMIN_ID', 1),

    /*
     * Borrow slots when the super admin has no subscription. 0 = same behaviour as any user without a plan.
     */
    'borrow_books_quota' => (int) env('SUPER_ADMIN_BORROW_QUOTA', 0),
];

