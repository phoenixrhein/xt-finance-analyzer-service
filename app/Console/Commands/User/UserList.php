<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Console\Command;

class UserList extends Command
{
    //dyfds
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-list {--userId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a list of all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('userId');
        $users = User::all(['id', 'email']);
        if ($userId) {
            $users = User::where('id', $userId)->select(['id', 'email'])->get();
        }
        $this->table(
            ['Id', 'Email'],
            $users->toArray()
        );
        if ($userId) {
            $this->table(
                ['Id', 'IBAN', 'BIC'],
                $users->first()->bankAccounts->map(function($bankAccount) {
                    return collect($bankAccount->toArray())
                            ->only('id', 'iban', 'bic')
                            ->all();
                })
            );
        }
    }
}
