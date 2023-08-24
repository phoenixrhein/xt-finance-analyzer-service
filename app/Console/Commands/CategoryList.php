<?php

namespace de\xovatec\financeAnalyzer\Console\Commands;

use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\Category;
use Illuminate\Console\Command;

class CategoryList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-list {cashflowId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a category tree for a cashflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cashflowId = $this->argument('cashflowId');
        $cashflow = Cashflow::findOrFail($cashflowId);

        $inCategory = Category::with('subCategories')->findOrFail($cashflow->in_category_id);
        $outCategory = Category::with('subCategories')->findOrFail($cashflow->out_category_id);

        $this->displayCategoryWithSubcategories($inCategory, 0);

        $this->info("\n");
        $this->displayCategoryWithSubcategories($outCategory, 0);
    }
    
    /**
     * @return void
     */
    private function displayCategoryWithSubcategories($category, $indent): void
    {
        $this->info(str_repeat('-', $indent) . $category->name);

        foreach ($category->subCategories as $subCategory) {
            $this->displayCategoryWithSubcategories($subCategory, $indent + 2);
        }
    }
}
