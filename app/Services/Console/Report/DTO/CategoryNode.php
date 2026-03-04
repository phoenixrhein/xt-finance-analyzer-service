<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report\DTO;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object for category data in reports.
 * Represents a single category node with its financial data and child categories.
 */
class CategoryNode
{
    /**
     * @param int|null $categoryId The category ID (null for unassigned transactions)
     * @param string $name Category name
     * @param float $directAmount Sum of transactions directly assigned to this category
     * @param float $childrenAmount Sum of transactions from all child categories
     * @param Collection<CategoryNode> $children Direct child categories
     * @param int $depth Current depth in the hierarchy (0 = root)
     */
    public function __construct(
        public readonly ?int $categoryId,
        public readonly string $name,
        public float $directAmount,
        public float $childrenAmount,
        public readonly Collection $children,
        public readonly int $depth = 0,
    ) {
    }

    /**
     * Get the total amount (direct + children).
     */
    public function getTotalAmount(): float
    {
        return $this->directAmount + $this->childrenAmount;
    }

    /**
     * Check if this node has any transactions (direct or from children).
     */
    public function hasAmount(): bool
    {
        return $this->getTotalAmount() !== 0.0;
    }

    /**
     * Check if this node should be displayed based on configuration.
     */
    public function shouldDisplay(bool $showEmptyCategories, int $maxDepth): bool
    {
        // Allow display up to maxDepth (inclusive)
        if ($this->depth > $maxDepth) {
            return false;
        }

        if (!$showEmptyCategories && !$this->hasAmount()) {
            return false;
        }

        return true;
    }

    /**
     * Get visible children based on configuration.
     */
    public function getVisibleChildren(bool $showEmptyCategories, int $maxDepth): Collection
    {
        return $this->children
            ->filter(fn (CategoryNode $child) => $child->shouldDisplay($showEmptyCategories, $maxDepth));
    }
}
