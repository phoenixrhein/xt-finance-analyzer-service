<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IgnoreList extends Model
{
    use SoftDeletes;

    protected $table = 'ignore_list';

    protected $fillable = [
        'bank_account_id',
        'type',
        'value',
        'comment'
    ];

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class)->withDefault();
    }
}
