<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $bank_account_id
 * @property string $type
 * @property string $value
 * @property string|null $comment
 */
class IgnoreList extends Model
{
    use SoftDeletes;

    public const TYPE_IBAN = 'iban';

    /**
     *
     * @var string
     */
    protected $table = 'ignore_list';

    /**
         *
     * @var array<int, string>
     */
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
