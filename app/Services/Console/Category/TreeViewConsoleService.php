<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Category;

use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class TreeViewConsoleService extends AbstractCategory
{
    /**
     *
     * @param Cashflow $cashflow
     * @return void
     */
    public function displayCashflowTrees(Cashflow $cashflow): void
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
     * @param Collection<int, Category> $categories
     * @param string $prefix
     * @return string
     */
    private function buildTree(Collection $categories, string $prefix = ''): string
    {
        $tree = '';

        foreach ($categories as $index => $category) {
            $isCurrentLast = $index == count($categories) - 1;
            $tree .= $prefix . ($isCurrentLast ? '└── ' : '├── ') . $category->name . " [{$category->id}]" . PHP_EOL;
            if ($category->subCategories->isNotEmpty()) {
                $tree .= $this->buildTree(
                    $category->subCategories,
                    $prefix . ($isCurrentLast ? '    ' : '│   ')
                );
            }
        }

        return $tree;
    }
}
