<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * @var array
     */
    protected $fillable = ['name', 'condition_link_id'];

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

    /**
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($rule) {
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
