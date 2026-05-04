<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountDetail extends Model
{
    protected $table = 'bank_account_details';

    protected $fillable = ['name', 'account_number', 'iban', 'swift_code'];

    /**
     * Get the single bank account details row used for subscription checkout.
     * Returns null if no admin-managed row exists.
     *
     * @return self|null
     */
    public static function forCheckout()
    {
        $row = self::query()
            ->where(function ($query) {
                $query->whereNotNull('iban')
                    ->where('iban', '!=', '');
            })
            ->orWhere(function ($query) {
                $query->whereNotNull('account_number')
                    ->where('account_number', '!=', '');
            })
            ->orWhere(function ($query) {
                $query->whereNotNull('swift_code')
                    ->where('swift_code', '!=', '');
            })
            ->orderBy('id')
            ->first();

        if ($row) {
            return $row;
        }
        return null;
    }
}
