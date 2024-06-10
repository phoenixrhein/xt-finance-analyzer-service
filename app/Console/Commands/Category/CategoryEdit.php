<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Category;

class CategoryEdit extends AbstractCategory
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-edit {categoryId : [:cli.category.base.param.category_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.edit.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $categoryId = (int)$this->argument('categoryId');
        $category = Category::find($categoryId);

        if (!$category instanceof Category) {
            $this->emptyLn();
            $this->error(__('cli.category.base.error.not_found_category_id', ['categoryId' => $categoryId]));
            return;
        }

        $name = $category->name;
        $parentId = $category->parent_id;

        do {
            $valid = true;
            $name = $this->viewNameInput($name);
            $parentId = $this->viewParentIdInput($parentId ?? '');

            $parentCategory = Category::find($parentId);
            $this->viewCategoryPath($parentCategory, $name);

            if (
                $name != $category->name
                && Category::where('name', $name)->where('parent_id', $parentId)->count() > 0
            ) {
                $this->error('Der Name ist in der übergeordneten Kategorie schon vorhanden');
                $valid = false;
            }
        } while (!$valid);

        if (!$this->confirmPrompt(__('cli.base.confirm_save'))) {
            return;
        }

        $category->name = $name;
        $category->parent_id = $parentId;
        $category->save();
        $this->displayCashflowTrees($category->getCashflow());
        $this->info(__('cli.category.edit.edited', ['categoryId' => $category->id]));
    }
}
