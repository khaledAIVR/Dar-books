<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Skip email verification (preview / staging only)
    |--------------------------------------------------------------------------
    |
    | When true, new users are created with email_verified_at set and no
    | verification email is sent. Use on Railway when MAIL_* is not configured.
    | Never enable on production unless you understand the security tradeoff.
    |
    */

    'skip_email_verification' => env('SKIP_EMAIL_VERIFICATION', false),

];
