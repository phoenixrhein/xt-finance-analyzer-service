<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;
    
    /**
     *
     * @var string
     */
    protected $table = 'bank_account';

    /**
     *
     * @var array
     */
    public static $rules = [
        'iban' => 'required|max:34',
        'bic' => 'required|max:11'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'iban',
        'bic'
    ];
    
    /**
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     *
     * @return HasOne
     */
    public function cashflow(): HasOne
    {
        return $this->hasOne(Cashflow::class);
    }

    /**
     *
     * @return HasMany
     */
    public function ignoreList(): HasMany
    {
        return $this->hasMany(IgnoreList::class);
    }

    /**
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($bankAccount) {
            if ($bankAccount->cashflow) {
                $bankAccount->cashflow->delete();
            }
            if ($bankAccount->ignoreList) {
                $bankAccount->ignoreList->delete();
            }
        });
    }
}
