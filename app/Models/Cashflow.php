<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cashflow extends Model
{
    use SoftDeletes;
    
    protected $table = 'cashflow';
    
    protected $fillable = [
        'bank_account_id',
        'in_category_id',
        'out_category_id',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function inCategory()
    {
        return $this->belongsTo(Category::class, 'in_category_id');
    }

    public function outCategory()
    {
        return $this->belongsTo(Category::class, 'out_category_id');
    }

    public static function createWithCategories($attributes)
    {
        $inCategory = Category::create(['name' => 'Einnahmen']);
        $outCategory = Category::create(['name' => 'Ausgaben']);

        $attributes['in_category_id'] = $inCategory->id;
        $attributes['out_category_id'] = $outCategory->id;

        return static::create($attributes);
    }
    
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($cashflow) {
            if ($cashflow->inCategory) {
                $cashflow->inCategory->delete();
            }

            if ($cashflow->outCategory) {
                $cashflow->outCategory->delete();
            }
        });
    }
    
}