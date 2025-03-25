<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;

class CategoryManagement extends AbstractCategory implements ProvidesAccountListQueryInterface
{
    use BankAccountIdParameter;

    /**
     *
     * @param AccountListQuery $accountListQuery
     */
    public function __construct(private AccountListQuery $accountListQuery)
    {
        parent::__construct();
    }

    /**
     *
     * @return AccountListQuery
     */
    public function getAccountListQuery(): AccountListQuery
    {
        return $this->accountListQuery;
    }
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-mgmt {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.manage.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $accountId = ($this->getBankAccount((int)$this->argument('accountId'), true))->id;

        $cashflow = Cashflow::where('bank_account_id', $accountId)->first();
        if (!$cashflow instanceof Cashflow) {
            $this->emptyLn();
            $this->error(__('cli.category.base.error.not_found_cashflow', ['bankAccountId' => $accountId]));
            return;
        }

        $changed = false;

        while (
            ($action = select(
                __('cli.category.manage.select_action'),
                [
                    1 => __('cli.category.manage.action.add'),
                    2 => __('cli.category.manage.action.edit'),
                    3 => __('cli.category.manage.action.delete'),
                    0 => __('cli.category.manage.action.finish')
                ]
            )) > 0
        ) {
            $changed = true;
            switch ($action) {
                default:
                case 1:
                    $this->addCategory($cashflow);
                    break;
                case 2:
                    $this->editCategory($cashflow);
                    break;
                case 3:
                    $this->deleteCategory($cashflow);
                    break;
            }
        }

        if ($changed) {
           $this->displayCashflowTrees($cashflow);
        }
    }

    /**
     *
     * @param Cashflow $cashflow
     * @return void
     */
    private function deleteCategory(Cashflow $cashflow): void
    {
        $category = Category::find(
            $this->findAndSelectCategory($cashflow)
        );

        $error = false;

        if ($category->parent_id == null) {
            $this->emptyLn();
            $this->error(__('cli.category.delete.error.category_is_cashflow'));
            $error = true;
        }

        $actionsQuery = Action::where('category_id', $category->id);
        if ($actionsQuery->count() > 0) {
            $this->emptyLn();
            $this->error(__('cli.category.delete.error.has_rules'));
            $this->emptyLn();
            $this->info(__('cli.base.rules') . ':');
            foreach ($actionsQuery->get() as $action) {
                $this->info('  • ' . $action->rule->name . ' [ID:' . $action->id . ']');
            }
            $error = true;
        }

        if (Category::where('parent_id', $category->id)->count() > 0) {
            $this->emptyLn();
            $this->error(__('cli.category.delete.error.has_childs'));
            $error = true;
        }

        if ($error === true) {
            return;
        }
        
        if ($this->confirmPrompt(__('cli.category.delete.confirm_question', ['name' => $category->name])) === false) {
            return;
        }

        $category->delete();
        $this->info(__('cli.category.delete.deleted', ['categoryId' => $category->id]));
    }

    /**
     *
     * @param string|null $name
     * @param integer|null $parentId
     * @return array
     */
    private function manageCategory(?string $name = null, ?int $parentId = null): array
    {
        do {
            $valid = true;
            $name = $this->viewNameInput($name ?? '');
            $parentId = $this->viewCategoryIdInput('parent_id', __('cli.category.base.input_parent_id'), $parentId ?? '');

            $parentCategory = Category::find($parentId);
            $this->viewCategoryPath($parentCategory, $name);

            if (Category::where('name', $name)->where('parent_id', $parentId)->exists()) {
                $this->error(__('cli.category.base.error.already_exist'));
                $valid = false;
            }

            if (
                $valid === true &&
                !confirm(
                    label: __('cli.base.confirm_save'),
                    yes: __('cli.base.button.yes'),
                    no: __('cli.base.button.no')
                )
            ) {
                $valid = false;
            }
        } while (!$valid);

        return [$name, $parentId];
    }

    /**
     *
     * @param Cashflow $cashflow
     * @return void
     */
    private function editCategory(Cashflow $cashflow): void
    {
        $category = Category::find(
            $this->findAndSelectCategory($cashflow)
        );
        list($name, $parentId) = $this->manageCategory($category->name, $category->parent_id);
        $category->name = $name;
        $category->parent_id = $parentId;
        $category->save();
        $this->info(__('cli.category.edit.edited', ['categoryId' => $category->id]));
    }

    /**
     *
     * @param Cashflow $cashflow
     * @return integer
     */
    private function findAndSelectCategory(Cashflow $cashflow): int
    {
        $this->emptyLn();
        $this->displayCashflowTrees($cashflow);
        return $this->viewCategoryIdInput('id', __('cli.category.base.select_category'));
    }

    /**
     *
     * @param Cashflow $cashflow
     * @return void
     */
    private function addCategory(Cashflow $cashflow): void
    {
        $this->emptyLn();
        $this->displayCashflowTrees($cashflow);

        list($name, $parentId) = $this->manageCategory();

        $newEntry = Category::create(['name' => $name, 'parent_id' => $parentId]);
        $this->info(__('cli.category.add.created', ['name' => $name, 'id' => $newEntry->id]));
    }
}
