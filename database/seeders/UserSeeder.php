<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'     => 'Super Admin',
            'username' => 'superadmin',
            'email'    => 'superadmin@admin.com',
            'password' => bcrypt('password'),
            'role'     => 'super_admin',
        ]);

        User::create([
            'name'     => 'Admin Akademik',
            'username' => 'admin akademik',
            'email'    => 'admin@admin.com',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Hendri Armando',
            'username' => 'hendriarmando',
            'email'    => 'hendriarmando23@gmail.com',
            'password' => bcrypt('hendri123'),
            'role'     => 'viewer',
        ]);

        User::create([
            'name'     => 'Bagas Hermawan',
            'username' => 'bagashermawan',
            'email'    => 'bagashermawan213@gmail.com',
            'password' => bcrypt('bagas123'),
            'role'     => 'viewer',
        ]);

        User::create([
            'name'     => 'Claudia Winata',
            'username' => 'claudiawinata',
            'email'    => 'claudiawinata18@gmail.com',
            'password' => bcrypt('claudia123'),
            'role'     => 'viewer',
        ]);
    }
}
