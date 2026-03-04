<?php

namespace de\xovatec\financeAnalyzer\Traits\Utils;

use de\xovatec\financeAnalyzer\Models\Category;

trait CategoryPath
{
    /**
     * Builds the full category path as a string.
     *
     * @param Category $category
     * @return string
     */
    public function buildPathAsString(Category $category): string
    {
        $ancestors = $category->ancestors();
        $path = '';

        foreach ($ancestors->reverse() as $ancestor) {
            $path .= $ancestor->name . " [{$ancestor->id}] " . ' \ ';
        }
        
        return $path . $category->name . " [{$category->id}] ";
    }
}
