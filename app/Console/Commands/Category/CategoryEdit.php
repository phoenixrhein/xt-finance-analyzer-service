<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CategoryEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-edit {categoryId} {--name=} {--parentId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit a category';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categoryId = $this->argument('categoryId');
        $category = Category::findOrFail($categoryId);
        if ($category->parent_id === null) {
            $this->info("Update not allowed");
            return;
        }
        if (strlen(implode('', array_values($this->options()))) === 0) {
            $this->info("No option for update");
            return;
        }
        $name = $this->option('name') ?? $category->name;
        $parentId = $this->option('parentId') ?? $category->parent_id;
        $validator = Validator::make(
            ['name' => $name, 'parent_id' => $parentId],
            Category::$rules
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return;
        }
        $category->name = $name;
        $category->parent_id = $parentId;
        $category->save();
        $this->info("Updated category with id '{$categoryId}'");
    }
}
