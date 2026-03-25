<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountDetail extends Model
{
    protected $table = 'bank_account_details';

    protected $fillable = ['name', 'account_number', 'iban', 'swift_code'];

    /**
     * Get the single bank account details row used for subscription checkout.
     * Creates a default row if none exists.
     *
     * @return self|null
     */
    public static function forCheckout()
    {
        $row = self::first();
        if ($row) {
            return $row;
        }
        return null;
    }
}
