<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Category;

use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;

use function Laravel\Prompts\text;
use function Laravel\Prompts\intro;

abstract class AbstractCategory extends AbstractIOService
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

        foreach ($ancestors->reverse() as $ancestor) {
            echo $ancestor->name . " [{$ancestor->id}] " . ' \ ';
        }
        echo $category->name . " [{$category->id}] " . ' \ ' . $name . PHP_EOL;
        $this->emptyLn();
    }

    /**
     *
     * @param string $idField
     * @param string $label
     * @param string $rawParentId
     * @return string
     */
    protected function viewCategoryIdInput(string $idField, string $label, string $rawParentId = ''): string
    {
        $parentId = $rawParentId;
        do {
            $parentId = text(
                label: $label,
                default: $parentId
            );

            $valid = $this->viewValidatorError(
                [
                    $idField => $parentId
                ],
                Category::getRules()
            );
        } while (!$valid);

        return $parentId;
    }
}
