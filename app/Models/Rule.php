<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rule';
    
    protected $fillable = [
        'field_identifier',
        'compare_operator',
        'value',
    ];

    public function conditionLink()
    {
        return $this->hasOne(ConditionLink::class, 'condition_foreign_id')->where('condition_type', 'rule');
    }
}
