<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property float $amount
 * @property string $currency
 * @property string $bank_account_iban
 * @property string $note
 */
class Transactions extends Model
{
    public const UPDATED_AT = null;

    /**
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bank_account_iban',
        'transaction_date',
        'exchange_date',
        'transaction_type',
        'reason_for_payment',
        'creditor_id',
        'mandate_reference',
        'customer_reference',
        'collector_reference',
        'debit_original_amount',
        'reimbursement_of_expenses_return_debit',
        'beneficiary_payee',
        'creditor_iban',
        'creditor_bic',
        'amount',
        'currency',
        'hash_identifier',
        'comment'
    ];

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_iban', 'iban');
    }

    /**
     *
     * @return HasOne
     */
    public function transactionAdjustment(): HasOne
    {
        return $this->hasOne(TransactionAdjustment::class, 'transaction_id', 'id');
    }

    /**
     *
     * @return HasMany
     */
    public function transactionSplit(): HasMany
    {
        return $this->hasMany(TransactionSplit::class, 'transaction_id', 'id');
    }

    /**
     *
     * @return BelongsToMany
     */
    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class, 'rule_transaction')
                    ->withPivot('bank_account_id');
    }

    /**
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($transaction) {
            if ($transaction->transactionAdjustment) {
                $transaction->transactionAdjustment->delete();
            }
            if ($transaction->transactionSplit) {
                $transaction->transactionSplit->delete();
            }
        });
    }
}
