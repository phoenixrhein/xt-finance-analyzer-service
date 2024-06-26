<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\User;
use Throwable;
use Illuminate\Support\Facades\DB;

class UserDelete extends FinCommand
{
    /**
     * @inheritDoc
     */
    protected $signature = 'fin:user-delete {userId : [:cli.base.param.user_id:]}';

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
            $this->error(__('cli.base.error.not_found_user', ['userId' => $userId]));
            return;
        }

        if ($this->confirmPrompt(__('cli.user.delete.confirm_question', ['mail' => $user->email])) === false) {
            return;
        }

        DB::beginTransaction();
        try {
            $deletableAccounts = [];
            if ($user->bankAccounts()->count() > 0) {
                foreach ($user->bankAccounts as $account) {
                    if ($account->users()->count() === 1) {
                        $deletableAccounts[] = $account;
                    }
                }
                $user->bankAccounts()->detach();
            }

            if (
                !empty($deletableAccounts)
                && $this->confirmPrompt(
                    __('cli.user.delete.question_delete_accounts', ['mail' => $user->email])
                ) === true
            ) {
                foreach ($deletableAccounts as $deletableAccount) {
                    $deletableAccount->delete();
                }
            }

            User::destroy($userId);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(__('cli.base.error.message', ['error' => $e]));
            return;
        }
        $this->info(__('cli.user.delete.deleted', ['userId' => $userId, 'mail' => $user->email]));
    }
}
