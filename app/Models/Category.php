<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
        
    protected $table = 'category';
    
    public static $rules = [
        'name' => 'required'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'parent_id'
    ];
    
    public function inCashflow()
    {
        return $this->hasOne(Cashflow::class, 'in_category_id');
    }

    public function outCashflow()
    {
        return $this->hasOne(Cashflow::class, 'out_category_id');
    }

    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function subCategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($category) {
            $category->subCategories()->delete();
        });
    }
}