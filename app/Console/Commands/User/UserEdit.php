<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

class UserEdit extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-edit {userId : [:cli.base.param.user_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.user.edit.description';

    /**
     * Execute the console command.
     */
    protected function process(): void
    {
        $userId = $this->argument('userId');
        $user = User::find($userId);

        if (!$user instanceof User) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found_user', ['userId' => $userId]));
            return;
        }

        $email = $user->email;
        do {
            $email = text(
                label: __('cli.user.edit.input_email'),
                default: $email
            );
            $validator = Validator::make(['email' => $email], User::$rules);

            $valid = true;
            if ($validator->fails()) {
                $valid = false;
                $this->error($validator->errors()->first());
            }

            if (
                $valid
                && !confirm(
                    label: __('cli.user.edit.confirm_save'),
                    yes: __('cli.base.button.yes'),
                    no: __('cli.base.button.no')
                )
            ) {
                $valid = false;
            }
        } while (!$valid);

        $user->email = $email;
        $user->save();
        $this->info(__('cli.user.edit.updated', ['mail' => $email, 'id' => $user->id]));
    }
}
