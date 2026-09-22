<?php

namespace de\xovatec\financeAnalyzer\Models;

use de\xovatec\financeAnalyzer\Enums\Cashflow;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $transaction_id
 * @property TransactionSplitType $type
 * @property string $note
 * @property float $amount
 * @property string $transaction_date
 * @property ?TransactionSplitType $type
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
    protected $fillable = ['transaction_id', 'type', 'amount', 'note'];

    /**
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => TransactionSplitType::class,
    ];

    /**
     *
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transactions::class, 'transaction_id', 'id');
    }

    /**
     *
     * @return Cashflow
     */
    public function getCashflow(): Cashflow
    {
        return $this->transaction->amount >= 0 ? Cashflow::in : Cashflow::out;
    }

    /**
     *
     * @param Transactions $transaction
     * @param float $amount
     * @param TransactionSplit|null $currentSplit
     * @return bool
     */
    public static function amountFitsTransaction(
        Transactions $transaction,
        float $amount,
        ?self $currentSplit = null
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $totalAmount = (float) self::where('transaction_id', $transaction->id)
            ->when(
                $currentSplit instanceof self,
                fn ($query) => $query->where('id', '!=', $currentSplit->id)
            )
            ->sum('amount');

        return round($totalAmount + $amount, 2) <= round(abs((float) $transaction->amount), 2);
    }
}
