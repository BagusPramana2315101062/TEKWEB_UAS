<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ReplaceProductsWithCreators extends Command
{
    protected $signature = 'products:replace-with-creators {--apply : Actually apply changes instead of dry-run} {--cycle : Cycle the list if there are more products than items}';

    protected $description = 'Replace existing products in-place with a creator-themed curated list (dry-run by default).';

    protected $fs;

    public function __construct(Filesystem $fs)
    {
        parent::__construct();
        $this->fs = $fs;
    }

    protected function productsList()
    {
        // Indonesian creator-themed product list
        return [
            ['name' => 'Paket Pencahayaan Studio', 'category' => 'Peralatan', 'price' => 1200000, 'stock' => 10, 'is_active' => true],
            ['name' => 'Mikrofon Kondensor (USB)', 'category' => 'Peralatan', 'price' => 750000, 'stock' => 15, 'is_active' => true],
            ['name' => 'Layar Hijau Portabel', 'category' => 'Peralatan', 'price' => 450000, 'stock' => 8, 'is_active' => true],
            ['name' => 'Tripod Ringan', 'category' => 'Peralatan', 'price' => 250000, 'stock' => 20, 'is_active' => true],
            ['name' => 'Buku Perencana Konten', 'category' => 'Alat', 'price' => 120000, 'stock' => 50, 'is_active' => true],
            ['name' => 'Paket Template Media Sosial (Digital)', 'category' => 'Digital', 'price' => 99000, 'stock' => 9999, 'is_active' => true],
            ['name' => 'Paket Mockup Merchandise (Digital)', 'category' => 'Digital', 'price' => 75000, 'stock' => 9999, 'is_active' => true],
            ['name' => 'Preset Color Grading (Digital)', 'category' => 'Digital', 'price' => 89000, 'stock' => 9999, 'is_active' => true],
            ['name' => 'Paket Intro Video (Digital)', 'category' => 'Digital', 'price' => 149000, 'stock' => 9999, 'is_active' => true],
            ['name' => 'Tiket Workshop Kreator', 'category' => 'Event', 'price' => 200000, 'stock' => 200, 'is_active' => true],
            ['name' => 'Notebook Konten Planner', 'category' => 'Alat', 'price' => 80000, 'stock' => 100, 'is_active' => true],
            ['name' => 'Compact LED Panel', 'category' => 'Peralatan', 'price' => 320000, 'stock' => 12, 'is_active' => true],
        ];
    }

    protected function ensureCategory($name)
    {
        // Ensure slug is set when creating category (some installs require slug non-null)
        $slug = Str::slug($name);
        $cat = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'slug' => $slug]);
        return $cat->id;
    }

    protected function backupDatabase()
    {
        // Create a SQL dump for supported drivers (sqlite or mysql). Fallback to JSON if mysqldump not available.
        $now = now()->format('Ymd_His');
        $driver = config('database.default');
        $path = database_path("backups");
        if (! $this->fs->exists($path)) {
            $this->fs->makeDirectory($path, 0755, true);
        }

        if ($driver === 'sqlite') {
            $dbFile = database_path(config('database.connections.sqlite.database'));
            $dest = $path . DIRECTORY_SEPARATOR . "sqlite-backup-{$now}.sqlite";
            $this->fs->copy($dbFile, $dest);
            return $dest;
        }

        if ($driver === 'mysql') {
            // Try mysqldump first
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');
            $db = config('database.connections.mysql.database');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $dest = $path . DIRECTORY_SEPARATOR . "mysql-backup-{$now}.sql";
            $cmd = "mysqldump -h{$host} -P{$port} -u{$user} -p'{$pass}' {$db} > " . escapeshellarg($dest);
            exec($cmd, $out, $rc);
            if ($rc === 0 && $this->fs->exists($dest)) {
                return $dest;
            }

            // Fallback: export key tables to JSON
            $jsonDest = $path . DIRECTORY_SEPARATOR . "json-backup-{$now}.json";
            $data = [];
            $data['products'] = Product::all()->toArray();
            $data['categories'] = Category::all()->toArray();
            // include minimal related tables to allow restore if needed
            try {
                $data['transactions'] = DB::table('transactions')->get()->toArray();
                $data['transaction_items'] = DB::table('transaction_items')->get()->toArray();
                $data['stock_movements'] = DB::table('stock_movements')->get()->toArray();
            } catch (\Exception $e) {
                // If any table missing, ignore
            }

            $this->fs->put($jsonDest, json_encode($data, JSON_PRETTY_PRINT));
            return $jsonDest;
        }

        // For other drivers, attempt JSON backup of key tables
        $jsonDest = $path . DIRECTORY_SEPARATOR . "json-backup-{$now}.json";
        $data = [];
        $data['products'] = Product::all()->toArray();
        $data['categories'] = Category::all()->toArray();
        $this->fs->put($jsonDest, json_encode($data, JSON_PRETTY_PRINT));
        return $jsonDest;
    }

    public function handle()
    {
        $apply = $this->option('apply');
        $cycle = $this->option('cycle');

        $products = Product::orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->info('No products found in the database.');
            return 0;
        }

        $list = $this->productsList();

        // Backup
        $this->info('Creating database backup...');
        $backup = $this->backupDatabase();
        if (! $backup) {
            $this->warn('Backup failed or unsupported driver. Aborting.');
            return 1;
        }
        $this->info('Backup created: ' . $backup);

        $mapping = [];
        $listCount = count($list);
        $index = 0;

        foreach ($products as $product) {
            if ($index >= $listCount) {
                if ($cycle) {
                    $index = 0;
                } else {
                    // If no cycle and list exhausted, stop mapping
                    break;
                }
            }
            $item = $list[$index];
            $mapping[] = [
                'id' => $product->id,
                'old' => ['name' => $product->name, 'category' => optional($product->category)->name, 'price' => $product->selling_price, 'stock' => $product->stock, 'is_active' => $product->is_active],
                'new' => $item,
            ];
            $index++;
        }

        // Display dry-run
        $this->table(['Product ID', 'Old Name', 'New Name', 'Old Price', 'New Price', 'Old Stock', 'New Stock', 'Old Active', 'New Active'], array_map(function ($m) {
            return [
                $m['id'],
                $m['old']['name'],
                $m['new']['name'],
                $m['old']['price'],
                $m['new']['price'],
                $m['old']['stock'],
                $m['new']['stock'],
                $m['old']['is_active'] ? 'Yes' : 'No',
                $m['new']['is_active'] ? 'Yes' : 'No',
            ];
        }, $mapping));

        if (! $apply) {
            $this->info("Dry-run complete. Re-run with --apply to execute changes. Use --cycle to reuse the list if needed.");
            return 0;
        }

        // Confirm
        if (! $this->confirm('Are you sure you want to apply these changes? This will overwrite existing product records.')) {
            $this->info('Aborted by user.');
            return 0;
        }

        // Apply changes in a transaction
        DB::transaction(function () use ($mapping) {
            foreach ($mapping as $m) {
                $prod = Product::find($m['id']);
                if (! $prod) continue;
                $catId = $this->ensureCategory($m['new']['category']);
                $prod->name = $m['new']['name'];
                $prod->category_id = $catId;
                $prod->selling_price = $m['new']['price'];
                // Some installations store stock separately via stock_movements; avoid setting a non-existent `stock` column.
                $prod->is_active = $m['new']['is_active'];
                $prod->save();
            }
        });

        $this->info('Products updated successfully.');
        return 0;
    }
}
