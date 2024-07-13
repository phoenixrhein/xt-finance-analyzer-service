<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IgnoreList extends Model
{
    use SoftDeletes;

    public const TYPE_IBAN = 'iban';

    protected $table = 'ignore_list';

    protected $fillable = [
        'bank_account_id',
        'type',
        'value',
        'comment'
    ];

        /**
     *
     * @var array
     */
    public static function getRules(): array
    {
        return [
            'bank_account_id' => 'required|numeric|exists:de\xovatec\financeAnalyzer\Models\BankAccount,id'
        ];
    }

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class)->withDefault();
    }
}
