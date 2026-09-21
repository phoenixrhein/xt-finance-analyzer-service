<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use de\xovatec\financeAnalyzer\Models\Transactions;

/**
 * @property string $note
 * @property int $bank_account_id
 * @property int|null $transaction_id
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

    /**
     *
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transactions::class, 'transaction_id', 'id');
    }
}
