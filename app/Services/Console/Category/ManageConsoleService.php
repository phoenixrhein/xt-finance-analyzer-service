<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Category;

use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\Category;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ManageConsoleService extends AbstractCategory
{
    /**
     *
     * @param TreeViewConsoleService $treeViewConsoleService
     */
    public function __construct(private TreeViewConsoleService $treeViewConsoleService)
    {
        parent::__construct();
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

        $promptText = __('cli.category.delete.confirm_question', ['name' => $category->name]);
        if ($this->confirmPrompt($promptText) === false) {
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
            $parentId = $this->viewCategoryIdInput(
                'parent_id',
                __('cli.category.base.input_parent_id'),
                $parentId ?? ''
            );

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
    public function findAndSelectCategory(Cashflow $cashflow): int
    {
        $this->emptyLn();
        $this->treeViewConsoleService->displayCashflowTrees($cashflow);
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
        $this->treeViewConsoleService->displayCashflowTrees($cashflow);

        list($name, $parentId) = $this->manageCategory();

        $newEntry = Category::create(['name' => $name, 'parent_id' => $parentId]);
        $this->info(__('cli.category.add.created', ['name' => $name, 'id' => $newEntry->id]));
    }

    /**
     *
     * @param int $accountId
     * @param string $continueButtonText
     * @return void
     */
    public function manage(int $accountId, string $continueButtonText): void
    {
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
                    0 => $continueButtonText
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
            $this->treeViewConsoleService->displayCashflowTrees($cashflow);
        }
    }
}
