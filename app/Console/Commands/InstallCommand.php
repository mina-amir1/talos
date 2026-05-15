<?php

namespace App\Console\Commands;

use App\Models\TalosRole;
use App\Models\TalosUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'talos:install';

    protected $description = 'Install and set up the Talos CMS';

    public function handle(): int
    {
        $this->printBanner();

        // ── 1. Environment ─────────────────────────────────────────────────
        $this->info('Checking environment...');

        if (! file_exists(base_path('.env'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->warn('.env file created from .env.example — please set your DB credentials and re-run.');
            return self::FAILURE;
        }

        // ── 2. App key ──────────────────────────────────────────────────────
        if (! config('app.key')) {
            $this->info('Generating application key...');
            Artisan::call('key:generate', ['--ansi' => true]);
        }

        // ── 3. Migrations ───────────────────────────────────────────────────
        $this->info('Running database migrations...');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            $this->warn('Make sure your database connection is configured in .env');
            return self::FAILURE;
        }

        // ── 4. Seed default roles ───────────────────────────────────────────
        $this->info('Seeding default roles...');
        $this->seedRoles();

        // ── 5. Storage link ─────────────────────────────────────────────────
        if (! file_exists(public_path('storage'))) {
            $this->info('Creating storage symlink...');
            Artisan::call('storage:link');
        }

        // ── 6. Super admin ──────────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=cyan>╔══════════════════════════════════════╗</>');
        $this->line('<fg=cyan>║      Create Super Admin Account      ║</>');
        $this->line('<fg=cyan>╚══════════════════════════════════════╝</>');
        $this->newLine();

        if (TalosUser::where('is_super_admin', true)->exists()) {
            if (! $this->confirm('A super admin already exists. Create another?', false)) {
                $this->info('Skipping super admin creation.');
                $this->finish();
                return self::SUCCESS;
            }
        }

        $firstname = $this->ask('First name');
        $lastname  = $this->ask('Last name');
        $email     = $this->ask('Email address');

        while (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address. Please try again.');
            $email = $this->ask('Email address');
        }

        if (TalosUser::where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");
            return self::FAILURE;
        }

        $password = $this->secret('Password (min 8 characters)');

        while (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            $password = $this->secret('Password (min 8 characters)');
        }

        $confirm = $this->secret('Confirm password');

        while ($confirm !== $password) {
            $this->error('Passwords do not match. Try again.');
            $password = $this->secret('Password (min 8 characters)');
            $confirm  = $this->secret('Confirm password');
        }

        TalosUser::create([
            'firstname'      => $firstname,
            'lastname'       => $lastname,
            'email'          => $email,
            'password'       => Hash::make($password),
            'is_super_admin' => true,
            'is_active'      => true,
        ]);

        $this->newLine();
        $this->info("Super admin [{$firstname} {$lastname}] created successfully.");

        $this->finish();

        return self::SUCCESS;
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'description' => 'Full access to everything'],
            ['name' => 'Editor',      'description' => 'Can manage content but not settings'],
            ['name' => 'Author',      'description' => 'Can create and edit own content'],
        ];

        foreach ($roles as $role) {
            TalosRole::firstOrCreate(['name' => $role['name']], $role);
        }
    }

    private function finish(): void
    {
        $prefix = config('talos.admin_prefix', 'talos');
        $url    = config('app.url') . '/' . $prefix;

        $this->newLine();
        $this->line('<fg=green>╔══════════════════════════════════════════════════╗</>');
        $this->line('<fg=green>║        Talos CMS installed successfully!         ║</>');
        $this->line('<fg=green>╚══════════════════════════════════════════════════╝</>');
        $this->newLine();
        $this->line("  Admin panel → <href={$url}>{$url}</>");
        $this->newLine();
    }

    private function printBanner(): void
    {
        $this->newLine();
        $cyan = "\033[36m";
        $gray = "\033[90m";
        $rst  = "\033[0m";
        echo $cyan . '  _____     _         '         . $rst . PHP_EOL;
        echo $cyan . ' |_   _|_ _| | ___  ___ '       . $rst . PHP_EOL;
        echo $cyan . '   | |/ _` | |/ _ \\/ __|'       . $rst . PHP_EOL;
        echo $cyan . '   | | (_| | | (_) \\__ \\'       . $rst . PHP_EOL;
        echo $cyan . '   |_|\\__,_|_|\\___/|___/'       . $rst . PHP_EOL;
        echo $gray . '   Headless CMS for Laravel'     . $rst . PHP_EOL;
        $this->newLine();
    }
}
