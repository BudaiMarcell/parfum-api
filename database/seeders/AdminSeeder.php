<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@parfumeria.hu',
            'password' => Hash::make('admin1234'),
        ]);

        Admin::create([
            'name'     => 'Admin',
            'email'    => 'admin@parfumeria.hu',
            'password' => Hash::make('admin1234'),
            'role'     => 'admin',
        ]);
    }
}