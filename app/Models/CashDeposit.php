<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDeposit extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'cash_deposit';

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class)->withDefault();
    }
}
