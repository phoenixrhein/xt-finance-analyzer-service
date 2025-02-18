<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
