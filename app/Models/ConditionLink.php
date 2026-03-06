<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $condition_type
 */
class ConditionLink extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'condition_link';

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'foreign_id',
        'condition_type',
        'condition_link_id',
        'link_operator'
    ];

    /**
     *
     * @return BelongsTo
     */
    public function conditionGroup(): BelongsTo
    {
        return $this->belongsTo(ConditionLink::class, 'foreign_id');
    }

    /**
     *
     * @return BelongsTo
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(Condition::class, 'foreign_id');
    }

    /**
     *
     * @return BelongsTo
     */
    public function linkedCondition(): BelongsTo
    {
        return $this->belongsTo(ConditionLink::class, 'condition_link_id');
    }

    /**
     *
     * @return HasOne
     */
    public function rule(): HasOne
    {
        return $this->hasOne(Rule::class);
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
            if ($conditionLink->condition_type == 'condition' && !is_null($conditionLink->condition)) {
                if ($conditionLink->isForceDeleting()) {
                    $conditionLink->condition->forceDelete();
                } else {
                    $conditionLink->condition->delete();
                }
            }
            if ($conditionLink->condition_type == 'group' && !is_null($conditionLink->conditionGroup)) {
                if ($conditionLink->isForceDeleting()) {
                    $conditionLink->conditionGroup->forceDelete();
                } else {
                    $conditionLink->conditionGroup->delete();
                }
            }
            if ($conditionLink->condition_link_id != null && !is_null($conditionLink->linkedCondition)) {
                if ($conditionLink->isForceDeleting()) {
                    $conditionLink->linkedCondition->forceDelete();
                } else {
                    $conditionLink->linkedCondition->delete();
                }
            }
        });
    }
}
