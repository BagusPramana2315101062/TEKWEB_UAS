<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RehashPlaintextPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:rehash-passwords {--dry-run : Do not actually persist changes, only report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find user records with plaintext passwords and re-hash them (preserves original password values by hashing existing string)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $updated = [];
        $checked = 0;

        User::chunk(100, function ($users) use (&$updated, &$checked, $dryRun) {
            foreach ($users as $u) {
                $checked++;

                // If password field isn't hashed according to the Hash manager, hash it
                if (!Hash::isHashed($u->password)) {
                    $updated[] = [$u->id, $u->email, $u->password];

                    if (! $dryRun) {
                        // Preserve the current plaintext by hashing it so the user can continue to sign in with the same secret
                        $u->password = Hash::make($u->password);
                        $u->save();
                    }
                }
            }
        });

        if (empty($updated)) {
            $this->info('No plaintext or unhashed passwords were found.');
            $this->comment("Checked {$checked} users.");

            return Command::SUCCESS;
        }

        $this->info(count($updated) . ' accounts required re-hashing.');
        $this->comment("Checked {$checked} users.");

        foreach ($updated as [$id, $email, $oldPassword]) {
            $this->line("- [ID: {$id}] {$email} (was: '{$oldPassword}')");
        }

        if ($dryRun) {
            $this->warn('Dry run: no changes were persisted.');
        }

        return Command::SUCCESS;
    }
}
