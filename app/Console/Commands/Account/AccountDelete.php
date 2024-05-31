<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use Throwable;

class AccountDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-delete {accountId : [:cli.account.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.account.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $accountId = $this->argument('accountId');
        $account = BankAccount::find($accountId);
        if (!$account instanceof BankAccount) {
            $this->emptyLn();
            $this->error(__('cli.account.base.error.not_found_account_id', ['accountId' => $accountId]));
            return;
        }

        if ($this->confirmPrompt(__('cli.account.delete.confirm_question', ['iban' => $account->iban])) === false) {
            return;
        }


        DB::beginTransaction();
        try {
            $deletableUsers = [];
            if ($account->users()->count() > 0) {
                foreach ($account->users as $user) {
                    if ($user->bankAccounts()->count() === 1) {
                        $deletableUsers[] = $user;
                    }
                }
                $account->users()->detach();
            }

            if (
                !empty($deletableUsers)
                && $this->confirmPrompt(
                    __('cli.account.delete.question_delete_users', ['mail' => $user->email])
                ) === true
            ) {
                foreach ($deletableUsers as $deletableUser) {
                    $deletableUser->delete();
                }
            }
            BankAccount::destroy($accountId);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(__('cli.base.error.message', ['error' => $e]));
            return;
        }

        $this->info(__('cli.account.delete.deleted', ['accountId' => $accountId, 'iban' => $account->iban]));
    }
}
