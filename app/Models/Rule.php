<?php

namespace de\xovatec\financeAnalyzer\Models;

use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * @property string $name
 * @property int $bank_account_id
 * @property RuleTargetType $target_type
 */
class Rule extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'rule';

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'condition_link_id', 'bank_account_id', 'target_type'];

    protected $casts = [
        'target_type' => RuleTargetType::class,
    ];

    /**
     *
     * @return HasOne
     */
    public function actions(): HasOne
    {
        return $this->hasOne(Action::class);
    }

    /**
     *
     * @return BelongsTo
     */
    public function conditionLink(): BelongsTo
    {
        return $this->belongsTo(ConditionLink::class);
    }

    /**
     *
     * @return BelongsToMany
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transactions::class, 'rule_transaction')
                    ->withPivot('bank_account_id');
    }

    public function transactionSplits(): BelongsToMany
    {
        return $this->belongsToMany(TransactionSplit::class, 'rule_transaction_split')
            ->withPivot('bank_account_id');
    }

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($rule) {
            DB::table('rule_transaction_split')->where('rule_id', $rule->id)->delete();
            if (!is_null($rule->actions)) {
                if ($rule->isForceDeleting()) {
                    $rule->actions->forceDelete();
                } else {
                    $rule->actions->delete();
                }
            }

            if (!is_null($rule->conditionLink)) {
                if ($rule->isForceDeleting()) {
                    $rule->conditionLink->forceDelete();
                } else {
                    $rule->conditionLink->delete();
                }
            }
        });
    }
}
