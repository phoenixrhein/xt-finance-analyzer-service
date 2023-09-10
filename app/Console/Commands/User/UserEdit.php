<?php

namespace de\xovatec\financeAnalyzer\Console\Commands;

use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class UserEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-edit {userId} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        $email = $this->option('email');
        $validator = Validator::make(['Email' => $email], User::$rules);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return;
        }
        $user = User::findOrFail($userId);
        $user->email = $email;
        $user->save();
        $this->info("Updated user with id '{$userId}'");
    }
}
