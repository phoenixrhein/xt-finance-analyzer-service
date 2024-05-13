<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Support\Facades\Validator;
use function \Laravel\Prompts\text;
use function \Laravel\Prompts\confirm;

class UserAdd extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.user.add.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $email = '';
        do {
            $email = text(
                label: __('cli.user.add.input_email'),
                default: $email
            );
            $validator = Validator::make(['email' => $email], User::$rules);
            
            $valid = true;
            if ($validator->fails()) {
                $valid = false;
                $this->error($validator->errors()->first());
            }

            if ($valid && User::where('email', $email)->count()) {
                $valid = false;
                $this->error(__('cli.user.add.validate_error.duplicate_email'));
            }

            if (
                $valid
                && !confirm(
                    label: __('cli.user.add.confirm'),
                    yes: __('cli.base.button.create'),
                    no: __('cli.base.button.abort')
                )
            ) {
                return;
            }
                
        } while (!$valid);
        
        $newEntry = User::create([
            'email' => $email
        ]);
        
        $this->info(__('cli.user.add.created', ['mail' => $email, 'id' => $newEntry->id]));
    }
}
