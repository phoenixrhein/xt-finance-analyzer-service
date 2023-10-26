<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rule extends Model
{
    use HasFactory, SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'rule';

    /**
     *
     * @var array
     */
    protected $fillable = [
        'field_identifier',
        'compare_operator',
        'value',
    ];

    /**
     *
     * @return HasOne
     */
    public function conditionLink(): HasOne
    {
        return $this->hasOne(ConditionLink::class, 'condition_foreign_id')->where('condition_type', 'rule');
    }
}
