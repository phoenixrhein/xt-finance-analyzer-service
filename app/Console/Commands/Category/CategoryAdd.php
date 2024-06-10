<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Category;

use function Laravel\Prompts\confirm;

class CategoryAdd extends AbstractCategory
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.add.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        do {
            $valid = true;
            $name = $this->viewNameInput($name ?? '');
            $parentId = $this->viewParentIdInput($parentId ?? '');

            $parentCategory = Category::find($parentId);
            $this->viewCategoryPath($parentCategory, $name);

            if (Category::where('name', $name)->where('parent_id', $parentId)->count() > 0) {
                $this->error('Der Name ist in der übergeordneten Kategorie schon vorhanden');
                $valid = false;
            }
        } while(!$valid);

        if (
            !confirm(
                label: __('cli.category.add.confirm'),
                yes: __('cli.base.button.create'),
                no: __('cli.base.button.abort')
            )
        ) {
            return;
        }

        $newEntry = Category::create(['name' => $name, 'parent_id' => $parentId]);
        $this->displayCashflowTrees($newEntry->getCashflow());
        $this->info(__('cli.category.add.created', ['name' => $name, 'id' => $newEntry->id]));
    }
}
