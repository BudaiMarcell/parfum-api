<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the default admin.
     *
     * ONLY runs in local/testing. In production use:
     *     php artisan make:admin <email> [password]
     *
     * The default password comes from the SEED_ADMIN_PASSWORD env var so
     * each developer can set their own without committing it.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('AdminSeeder skipped (APP_ENV != local|testing). Use `php artisan make:admin` in production.');
            return;
        }

        $email    = 'admin@parfumeria.hu';
        $password = env('SEED_ADMIN_PASSWORD', 'admin1234');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Admin',
                'password' => Hash::make($password),
            ]
        );

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Admin',
                'password' => Hash::make($password),
                'role'     => 'admin',
            ]
        );
    }
}
