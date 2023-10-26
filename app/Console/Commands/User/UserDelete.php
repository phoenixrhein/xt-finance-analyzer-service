<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Console\Command;

class UserDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-delete {userId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Do you want to delete?') === false) {
            return;
        }
        $userId = $this->argument('userId');
        User::destroy($userId);
        $this->info("Deleted user with id '{$userId}'");
    }
}
