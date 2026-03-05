<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Report\DTO\CategoryNode;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportTableRenderer;
use de\xovatec\financeAnalyzer\Traits\Console\CurrencyFormatterTrait;
use Illuminate\Support\Collection;

class CategoryTreeRenderer extends AbstractIOService
{
    use CurrencyFormatterTrait;

    /**
     * Constructor.
     *
     * @param ReportTableRenderer $tableRenderer
     * @param int $maxCategoryDepth
     * @param bool $showEmptyCategories
     */
    public function __construct(
        private ReportTableRenderer $tableRenderer,
        private int $maxCategoryDepth = 2,
        private bool $showEmptyCategories = false
    ) {
        parent::__construct();
        $this->initializeCurrencyFormatter();
    }

    /**
     * Render category rows recursively, including subcategories and totals.
     *
     * @param Collection $reportData
     * @param CategoryNode $category
     * @param callable $getCategoriesCallback
     * @param string $prefix
     */
    public function renderCategoryRows(
        Collection $reportData,
        CategoryNode $category,
        callable $getCategoriesCallback,
        string $prefix
    ): void {
        if (!$category->shouldDisplay($this->showEmptyCategories, $this->maxCategoryDepth)) {
            return;
        }

        // Handle "Unzugeordnet" (unassigned) categories - just show one line
        if ($category->categoryId === null) {
            $nameRow = ['(' . __('cli.report.presentation.label_unassigned') . ')'];
            foreach ($reportData as $period) {
                $categories = $getCategoriesCallback($period);
                $found = $categories->firstWhere('categoryId', null);
                $nameRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
            }
            $this->tableRenderer->renderDataRow($nameRow);
            return;
        }

        // Show category name
        $nameRow = [$prefix . $category->name];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $this->findCategoryInTree($categories, $category->categoryId);
            $nameRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
        }
        $this->tableRenderer->renderDataRow($nameRow);

        // Only show sub-rows (zugeordnet, Summe Unterkategorien, Gesamt) if category has children
        $this->renderCategorySubRows($reportData, $category, $getCategoriesCallback, $prefix);
    }

    /**
     * Render sub-rows for a category (direct amount, sum of subcategories, and children).
     *
     * @param Collection $reportData
     * @param CategoryNode $category
     * @param callable $getCategoriesCallback
     * @param string $prefix
     */
    private function renderCategorySubRows(
        Collection $reportData,
        CategoryNode $category,
        callable $getCategoriesCallback,
        string $prefix
    ): void {
        if ($category->children->isEmpty()) {
            return;
        }

        $nextPrefix = $prefix . '  ';

        // Direct amount row
        $directRow = [$nextPrefix . '├─ ' . __('cli.report.presentation.label_direct_amount')];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $this->findCategoryInTree($categories, $category->categoryId);
            $directRow[] = $found ? $this->formatAmount($found->directAmount) : '-';
        }
        $this->tableRenderer->renderDataRow($directRow);

        // Children sum row
        $childrenRow = [$nextPrefix . '├─ ' . __('cli.report.presentation.label_subcategories_sum')];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $this->findCategoryInTree($categories, $category->categoryId);
            $childrenRow[] = $found ? $this->formatAmount($found->childrenAmount) : '-';
        }
        $this->tableRenderer->renderDataRow($childrenRow);

        // Render child categories (under "Summe Unterkategorien")
        $visibleChildren = $category->getVisibleChildren($this->showEmptyCategories, $this->maxCategoryDepth);

        // Determine tree symbols based on position
        foreach ($visibleChildren as $index => $child) {
            $isLast = $index === $visibleChildren->count() - 1;
            $childSymbol = $isLast ? '└─ ' : '├─ ';
            // Always use vertical line to connect to Summe Unterkategorien
            $childrenWithVertical = $nextPrefix . '│  ';

            $this->renderCategoryRows(
                $reportData,
                $child,
                $getCategoriesCallback,
                $childrenWithVertical . $childSymbol
            );
        }
    }

    /**
     * Find a category node recursively in the tree.
     *
     * @param Collection $categories
     * @param int $categoryId
     * @return CategoryNode|null
     */
    private function findCategoryInTree(Collection $categories, int $categoryId): ?CategoryNode
    {
        foreach ($categories as $category) {
            if ($category->categoryId === $categoryId) {
                return $category;
            }

            // Search in children recursively
            if ($category->children->isNotEmpty()) {
                $found = $this->findCategoryInTree($category->children, $categoryId);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
