<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condition extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'condition';

    /**
     *
     * @var array<int, string>
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
        return $this->hasOne(ConditionLink::class, 'foreign_id')->where('condition_type', 'condition');
    }
}
