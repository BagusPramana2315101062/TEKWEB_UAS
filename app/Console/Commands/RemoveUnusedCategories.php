<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Product;

class RemoveUnusedCategories extends Command
{
    protected $signature = 'categories:remove-unused {--apply : Actually delete categories}';

    protected $description = 'Backup and remove unused categories by name (dry-run by default). Will reassign products to Peralatan before deletion.';

    protected $fs;

    public function __construct(Filesystem $fs)
    {
        parent::__construct();
        $this->fs = $fs;
    }

    protected function targets()
    {
        return ['Quia','Dolores','Vel','Et','Recusandae','Electronics','Books','Clothing','Sit','Voluptatem','Deserunt','Enim','Iure','Dolor','Voluptas','Aut','Inventore','Nisi','Eius','Labore','Harum','Perferendis','Repudiandae','Ut','Qui','Impedit','Assumenda','Atque'];
    }

    protected function backup($cats, $products)
    {
        $now = now()->format('Ymd_His');
        $path = database_path('backups');
        if (! $this->fs->exists($path)) {
            $this->fs->makeDirectory($path, 0755, true);
        }
        $dest = $path . DIRECTORY_SEPARATOR . "remove-cats-{$now}.json";
        $this->fs->put($dest, json_encode(['categories' => $cats, 'products' => $products], JSON_PRETTY_PRINT));
        return $dest;
    }

    public function handle()
    {
        $apply = $this->option('apply');
        $names = $this->targets();

        $cats = Category::whereIn('name', $names)->get();
        $catIds = $cats->pluck('id')->all();
        $products = Product::whereIn('category_id', $catIds)->get();

        $backupPath = $this->backup($cats, $products);
        $this->info('Backup created: ' . $backupPath);
        $this->info('Categories found: ' . $cats->count());
        $this->info('Products referencing these categories: ' . $products->count());

        $table = [];
        foreach ($cats as $c) {
            $table[] = [$c->id, $c->name, $c->slug];
        }
        if (count($table) > 0) {
            $this->table(['ID','Name','Slug'], $table);
        } else {
            $this->info('No matching categories found — nothing to delete.');
            return 0;
        }

        if (! $apply) {
            $this->info('Dry-run complete. Re-run with --apply to delete found categories (this will reassign products to category "Peralatan").');
            return 0;
        }

        // Apply: reassign products -> Peralatan
        $per = Category::firstOrCreate(['slug' => Str::slug('Peralatan')], ['name' => 'Peralatan', 'slug' => Str::slug('Peralatan')]);
        $reassigned = 0;
        DB::transaction(function () use ($catIds, $per, &$reassigned) {
            $reassigned = Product::whereIn('category_id', $catIds)->update(['category_id' => $per->id]);
            Category::whereIn('id', $catIds)->delete();
        });

        $this->info("Deleted categories and reassigned {$reassigned} product(s) to category 'Peralatan'.");
        return 0;
    }
}
