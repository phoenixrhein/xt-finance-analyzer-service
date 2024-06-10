<?php
// vielleicht sollte nach einer Verändung (egal ob add, edit, delete) immer das aktualisierte Tree einmal angezeigt werden

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Models\Category;

class CategoryDelete extends AbstractCategory
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-delete {categoryId : [:cli.category.base.param.category_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $categoryId = $this->argument('categoryId');
        $category = Category::find($categoryId);
        if (!$category instanceof Category) {
            $this->emptyLn();
            $this->error(__('cli.category.base.error.not_found_category_id', ['categoryId' => $categoryId]));
            return;
        }

        if ($category->parent_id == null) {
            $this->emptyLn();
            $this->error(__('cli.category.delete.error.category_is_cashflow'));
            return;
        }

        if (Category::where('parent_id', $categoryId)->count() > 0) {
            $this->emptyLn();
            $this->error(__('cli.category.delete.error.has_childs'));
            return;
        }

        if (Action::where('category_id', $categoryId)->count() > 0) {
            $this->emptyLn();
            $this->error(__('cli.category.delete.error.has_rules'));
            return;
        }

        if ($this->confirmPrompt(__('cli.category.delete.confirm_question', ['name' => $category->name])) === false) {
            return;
        }


        Category::destroy($categoryId);

        $this->displayCashflowTrees($category->getCashflow());

        $this->info(__('cli.category.delete.deleted', ['categoryId' => $categoryId, 'name' => $category->name]));
    }
}
