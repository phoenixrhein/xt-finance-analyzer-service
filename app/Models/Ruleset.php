<?php

namespace de\xovatec\financeAnalyzer\Models;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ruleset extends Model
{
    /*
    - phpdocs
    - types
    - commit + push


    #- ruleset delete
    - rulset query/list:  id +name + quirery ------> wie mit weiterer verschachtelung
    - rulset edit (delete + add)
*/
    use HasFactory, SoftDeletes;

    protected $table = 'ruleset';

    protected $fillable = ['name', 'type', 'condition_id'];

    public function actions()
    {
        return $this->hasOne(Action::class);
    }

    public function condition()
    {
        return $this->hasOne(ConditionLink::class, 'id', 'condition_id');
    }

    protected static function boot()
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
