<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $note
 * @property int $bank_account_id
 * @property string $cash_booking_date
 * @property float $amount
 * @property string $currency
 */
class CashTransaction extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'cash_transaction';

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class)->withDefault();
    }
}
