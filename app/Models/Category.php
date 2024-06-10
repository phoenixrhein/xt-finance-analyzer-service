<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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
    public static function getRules(): array
    {
        return [
            'name' => 'required',
            'parent_id' => 'required|numeric|exists:de\xovatec\financeAnalyzer\Models\Category,id'
        ];
    }

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
     * @return Cashflow
     */
    public function getCashflow(): Cashflow
    {
        $cashflowCategory = $this->getCashflowCategory();
        return Cashflow::where('in_category_id', $cashflowCategory->id)
            ->orWhere('out_category_id', $cashflowCategory->id)
            ->first();
    }

    /**
     *
     * @return Category
     */
    public function getCashflowCategory(): Category
    {
        if ($this->parent_id === null) {
            return $this;
        }

        return $this->parentCategory->getCashflowCategory();
    }

    /**
     *
     * @return Collection
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();

        $category = $this;
        while ($category->parentCategory) {
            $ancestors->push($category->parentCategory);
            $category = $category->parentCategory;
        }

        return $ancestors;
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
