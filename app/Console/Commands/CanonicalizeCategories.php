<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CanonicalizeCategories extends Command
{
    protected $signature = 'categories:canonicalize {--apply : Apply mapping instead of dry-run}';

    protected $description = 'Create canonical categories and map existing products to them (dry-run by default).';

    protected $fs;

    public function __construct(Filesystem $fs)
    {
        parent::__construct();
        $this->fs = $fs;
    }

    protected function canonicalList()
    {
        return [
            'Peralatan',
            'Alat',
            'Digital',
            'Event',
            'Merchandise',
            'Aksesoris'
        ];
    }

    protected function ensureCategories()
    {
        $created = [];
        foreach ($this->canonicalList() as $name) {
            $slug = Str::slug($name);
            $cat = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'slug' => $slug]);
            $created[$name] = $cat->id;
        }
        return $created;
    }

    protected function detectCategoryForProduct($product)
    {
        $name = strtolower($product->name);
        if (Str::contains($name, ['mikrofon','tripod','led','pencahayaan','studio','compact led','panel','kamera'])) {
            return 'Peralatan';
        }
        if (Str::contains($name, ['buku','notebook','planner','konten','note','notebook'])) {
            return 'Alat';
        }
        if (Str::contains($name, ['template','preset','mockup','intro','digital','paket'])) {
            return 'Digital';
        }
        if (Str::contains($name, ['tiket','workshop','event'])) {
            return 'Event';
        }
        if (Str::contains($name, ['merch','merchandise','mockup'])) {
            return 'Merchandise';
        }
        // fallback
        return 'Peralatan';
    }

    public function handle()
    {
        $apply = $this->option('apply');

        $this->info('Ensuring canonical categories exist...');
        $cats = $this->ensureCategories();
        $this->info('Categories confirmed: ' . implode(', ', array_keys($cats)));

        $products = Product::orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->info('No products found.');
            return 0;
        }

        $mapping = [];
        foreach ($products as $p) {
            $oldCategory = optional($p->category)->name ?: '(none)';
            $suggested = $this->detectCategoryForProduct($p);
            $mapping[] = [
                'id' => $p->id,
                'name' => $p->name,
                'old' => $oldCategory,
                'new' => $suggested,
            ];
        }

        $this->table(['Product ID','Name','Old Category','Suggested Category'], array_map(function ($r) {
            return [$r['id'],$r['name'],$r['old'],$r['new']];
        }, $mapping));

        if (! $apply) {
            $this->info('Dry-run complete. Re-run with --apply to actually update product category_ids.');
            return 0;
        }

        // Apply
        DB::transaction(function () use ($mapping, $cats) {
            foreach ($mapping as $m) {
                $p = Product::find($m['id']);
                if (!$p) continue;
                $catName = $m['new'];
                if (!isset($cats[$catName])) continue;
                $p->category_id = $cats[$catName];
                $p->save();
            }
        });

        $this->info('Product categories updated successfully.');

        return 0;
    }
}
