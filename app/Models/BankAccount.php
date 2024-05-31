<?php

namespace de\xovatec\financeAnalyzer\Models;

use de\xovatec\financeAnalyzer\Rules\Bic;
use de\xovatec\financeAnalyzer\Rules\Iban;
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
    public static function getRules(): array
    {
        return [
            'iban' => ['required', new Iban()],
            'bic' => ['required', new Bic()]
        ];
    }

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
                foreach($bankAccount->ignoreList as $ignoreList) {
                    $ignoreList->delete();
                }
            }
        });
    }
}
