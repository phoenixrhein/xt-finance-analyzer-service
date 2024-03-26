<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    /**
     *
     * @var string
     */
    protected $table = 'user';

    /**
     *
     * @var array
     */
    public static $rules = [
        'email' => 'required|email'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
    ];

    /**
     *
     * @return BelongsToMany
     */
    public function bankAccounts(): BelongsToMany
    {
        return $this->belongsToMany(BankAccount::class)->withTimestamps();
    }
}
