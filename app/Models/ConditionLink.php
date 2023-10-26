<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConditionLink extends Model
{
    use HasFactory, SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'condition_link';

    /**
     *
     * @var array
     */
    protected $fillable = [
        'condition_foreign_id',
        'condition_type',
        'linked_condition_id',
        'link_operator'
    ];

    /**
     *
     * @return BelongsTo
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'condition_foreign_id');
    }

    /**
     *
     * @return BelongsTo
     */
    public function ruleset(): BelongsTo
    {
        return $this->belongsTo(Ruleset::class, 'condition_foreign_id');
    }

    /**
     *
     * @return void
     */
    public function linkedCondition()
    {
        return $this->belongsTo(ConditionLink::class, 'linked_condition_id');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($conditionLink) {
            if ($conditionLink->condition_type == 'rule' && !is_null($conditionLink->rule)) {
                if ($conditionLink->isForceDeleting()) {
                    $conditionLink->rule->forceDelete();
                } else {
                    $conditionLink->rule->delete();
                }
            }
            if ($conditionLink->condition_type == 'ruleset' && !is_null($conditionLink->ruleset)) {
                if ($conditionLink->isForceDeleting()) {
                    $conditionLink->ruleset->forceDelete();
                } else {
                    $conditionLink->ruleset->delete();
                }
            }
            if ($conditionLink->linked_condition_id != null && !is_null($conditionLink->linkedCondition)) {
                if ($conditionLink->isForceDeleting()) {
                    $conditionLink->linkedCondition->forceDelete();
                } else {
                    $conditionLink->linkedCondition->delete();
                }
            }
        });
    }
}
