<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $note
 * @property int $transaction_id
 * @property string $transaction_date
 * @property int $bank_account_id
 * @property string|null $transactions_transaction_date
*/
class TransactionAdjustment extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'transaction_adjustment';

    /**
     *
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transactions::class, 'transaction_id', 'id');
    }
}
