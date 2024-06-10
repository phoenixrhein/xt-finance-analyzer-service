<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\Category;
use Illuminate\Database\Eloquent\Collection;

use function Laravel\Prompts\text;
use function Laravel\Prompts\intro;

abstract class AbstractCategory extends FinCommand
{
    /**
     *
     * @param string $rawName
     * @return string
     */
    protected function viewNameInput(string $rawName = ''): string
    {
        $name = $rawName;
        do {
            $name = text(
                label: __('cli.category.base.input_name'),
                default: $name
            );

            $valid = $this->viewValidatorError(
                [
                    'name' => $name
                ],
                ['name' => Category::getRules()['name']]
            );
        } while (!$valid);

        return $name;
    }

    /**
     *
     * @param Category $category
     * @param string $name
     * @return void
     */
    protected function viewCategoryPath(Category $category, string $name): void
    {
        intro(__('cli.category.base.category_path'));
        $ancestors = $category->ancestors();

        foreach ($ancestors as $ancestor) {
            echo $ancestor->name . " [{$ancestor->id}] " . ' \ ';
        }
        echo $category->name . " [{$category->id}] " . ' \ ' . $name . PHP_EOL;
        $this->emptyLn();
    }

    /**
     *
     * @param string $rawParentId
     * @return string
     */
    protected function viewParentIdInput(string $rawParentId = ''): string
    {
        $parentId = $rawParentId;
        do {
            $parentId = text(
                label: __('cli.category.base.input_parent_id'),
                default: $parentId
            );

            $valid = $this->viewValidatorError(
                [
                    'parent_id' => $parentId
                ],
                Category::getRules()
            );
        } while (!$valid);

        return $parentId;
    }

    /**
     *
     * @param Cashflow $cashflow
     * @return void
     */
    protected function displayCashflowTrees(Cashflow $cashflow): void
    {
        $inCategory = Category::with('subCategories')->findOrFail($cashflow->in_category_id);
        $outCategory = Category::with('subCategories')->findOrFail($cashflow->out_category_id);
        $this->displayCategoryWithSubcategories($inCategory);
        $this->displayCategoryWithSubcategories($outCategory);
    }

    /**
     *
     * @param Category $category
     * @return void
     */
    private function displayCategoryWithSubcategories(Category $category): void
    {
        $this->info('<options=bold,underscore;fg=green>' . $category->name . " [{$category->id}]" . '</>');
        $this->info($this->buildTree($category->subCategories));
    }

    /**
     *
     * @param Collection $categories
     * @param string $prefix
     * @return string
     */
    private function buildTree(Collection $categories, string $prefix = ''): string
    {
        $tree = '';

        foreach ($categories as $index => $category) {
            $isCurrentLast = $index == count($categories) - 1;
            $tree .= $prefix . ($isCurrentLast ? '└── ' : '├── ') . $category->name . " [{$category->id}]". PHP_EOL;
            if ($category->subCategories->isNotEmpty()) {
                $tree .= $this->buildTree(
                    $category->subCategories,
                    $prefix . ($isCurrentLast ? '    ' : '│   '),
                    $isCurrentLast
                );
            }
        }

        return $tree;
    }
}
