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
     * Used only when the super admin has no subscription row: /cart needs a positive number for the UI.
     * When a subscription exists, quota always comes from the plan (e.g. 2 books per month), not this value.
     */
    'borrow_books_quota' => (int) env('SUPER_ADMIN_BORROW_QUOTA', 24),

    /*
     * Optional: ensure a real subscriptions row for the super admin (pricing /api/user, admin manage-subscriptions).
     * Default plan id 3 = "صديق الكتاب" (24 books/year, 2 per month).
     */
    'dev_subscription_plan_id' => (int) env('SUPER_ADMIN_PLAN_ID', 3),
];

