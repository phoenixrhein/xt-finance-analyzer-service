<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;
    
    protected $table = 'bank_account';
    
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
    
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
    
    public function cashflow()
    {
        return $this->hasOne(Cashflow::class);
    }
    
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($bankAccount) {
            if ($bankAccount->cashflow) {
                $bankAccount->cashflow->delete();
            }
        });
    }
}
