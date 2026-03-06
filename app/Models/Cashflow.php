<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $in_category_id
 * @property int $out_category_id
 */
class Cashflow extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'cashflow';

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bank_account_id',
        'in_category_id',
        'out_category_id',
    ];

    /**
     *
     * @return BelongsTo
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     *
     * @return BelongsTo
     */
    public function inCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'in_category_id');
    }

    /**
     *
     * @return BelongsTo
     */
    public function outCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'out_category_id');
    }

    /**
     *
     * @param array $attributes
     * @return void
     */
    public static function createWithCategories(array $attributes)
    {
        $inCategory = Category::create(['name' => __('cli.base.cashflow_in')]);
        $outCategory = Category::create(['name' => __('cli.base.cashflow_out')]);

        $attributes['in_category_id'] = $inCategory->id;
        $attributes['out_category_id'] = $outCategory->id;

        static::create($attributes);
    }

    /**
     *
     * @return void
     */
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
