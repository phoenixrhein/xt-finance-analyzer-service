<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $transaction_id
 * @property string $note
 * @property float $amount
 * @property string $transaction_date
 */
class TransactionSplit extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'transaction_split';

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = ['transaction_id', 'amount', 'note'];

    /**
     *
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transactions::class, 'transaction_id', 'id');
    }
}
