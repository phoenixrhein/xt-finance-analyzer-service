<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'category';

    /**
     *
     * @var array
     */
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

    /**
     *
     * @return void
     */
    public function inCashflow()
    {
        return $this->hasOne(Cashflow::class, 'in_category_id');
    }

    /**
     *
     * @return void
     */
    public function outCashflow()
    {
        return $this->hasOne(Cashflow::class, 'out_category_id');
    }

    /**
     *
     * @return void
     */
    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     *
     * @return void
     */
    public function subCategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($category) {
            $category->subCategories()->delete();
        });
    }
}