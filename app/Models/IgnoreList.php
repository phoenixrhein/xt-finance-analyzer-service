<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
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
     * @return void
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
