<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CategoryAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-add {name} {parentId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new category';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $parentId = $this->argument('parentId');
        $validator = Validator::make(
            ['name' => $name, 'parent_id' => $parentId],
            Category::$rules
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return;
        }
        $newEntry = Category::create(['name' => $name, 'parent_id' => $parentId]);
        $this->info("category created '{$name}' and id {$newEntry->id}");
    }
}
