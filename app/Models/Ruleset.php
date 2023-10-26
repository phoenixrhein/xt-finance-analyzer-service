<?php

namespace de\xovatec\financeAnalyzer\Models;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ruleset extends Model
{
    use HasFactory, SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'ruleset';

    /**
     *
     * @var array
     */
    protected $fillable = ['name', 'type', 'condition_id'];

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
     * @return HasOne
     */
    public function condition(): HasOne
    {
        return $this->hasOne(ConditionLink::class, 'id', 'condition_id');
    }

    /**
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($ruleset) {
            if (!is_null($ruleset->actions)) {
                if ($ruleset->isForceDeleting()) {
                    $ruleset->actions->forceDelete();
                } else {
                    $ruleset->actions->delete();
                }
            }

            if (!is_null($ruleset->condition)) {
                if ($ruleset->isForceDeleting()) {
                    $ruleset->condition->forceDelete();
                } else {
                    $ruleset->condition->delete();
                }
            }
        });
    }
}
