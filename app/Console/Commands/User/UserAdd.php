<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class UserAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-add {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $validator = Validator::make(['email' => $email], User::$rules);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return;
        }
        $newEntry = User::create([
            'email' => $email
        ]);
        $this->info("user created with email: {$email} and id {$newEntry->id}");
    }
}
