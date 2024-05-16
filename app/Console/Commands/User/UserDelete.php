<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\User;

use function Laravel\Prompts\confirm;

class UserDelete extends FinCommand
{
    /**
     * @inheritDoc
     */
    protected $signature = 'fin:user-delete {userId}';

    /**
     * @inheritDoc
     */
    protected $description = 'cli.user.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $userId = $this->argument('userId');
        $user = User::find($userId);
        if (!$user instanceof User) {
            $this->emptyLn();
            $this->error(__('cli.user.delete.error.not_found', ['userId' => $userId]));
            return;
        }

        if (confirm(__('cli.user.delete.confirm_question', ['mail' => $user->email])) === false) {
            return;
        }
        
        User::destroy($userId);
        $this->info(__('cli.user.delete.deleted', ['userId' => $userId, 'mail' => $user->email]));
    }
}
