<?php

namespace de\xovatec\financeAnalyzer\Models;

use de\xovatec\financeAnalyzer\Enums\Cashflow as CashflowEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property string $name
 * @property int|null $parent_id
 */
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
     * @return array
     */
    public static function getRules(): array
    {
        return [
            'id' => 'required|numeric|exists:de\xovatec\financeAnalyzer\Models\Category,id',
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
     * @return HasOne
     */
    public function inCashflow(): HasOne
    {
        return $this->hasOne(Cashflow::class, 'in_category_id');
    }

    /**
     *
     * @return HasOne
     */
    public function outCashflow(): HasOne
    {
        return $this->hasOne(Cashflow::class, 'out_category_id');
    }

    /**
     *
     * @return BelongsTo
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     *
     * @return HasMany
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     *
     * @return Cashflow
     */
    public function getCashflow(): Cashflow
    {
        $cashflowCategory = $this->getCashflowRootCategory();
        return Cashflow::where('in_category_id', $cashflowCategory->id)
            ->orWhere('out_category_id', $cashflowCategory->id)
            ->first();
    }

    /**
     *
     * @return CashflowEnum
     */
    public function getCashflowType(): CashflowEnum 
    {
        $cashflow = $this->getCashflow();
        $rootCategory = $this->getCashflowRootCategory();
        return $cashflow->in_category_id === $rootCategory->id ? CashflowEnum::in : CashflowEnum::out;
    }

    /**
     *
     * @return Category
     */
    public function getCashflowRootCategory(): Category
    {
        if ($this->parent_id === null) {
            return $this;
        }

        return $this->parentCategory->getCashflowRootCategory();
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
