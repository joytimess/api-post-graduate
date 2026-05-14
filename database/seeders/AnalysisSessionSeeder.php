<?php

namespace Database\Seeders;

use App\Models\AnalysisSession;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnalysisSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'super_admin')->firstOrFail();

        $sessions = [
            [
                'admin_id'     => $admin->id,
                'periode_name' => 'Penerimaan Semester Ganjil 2022/2023',
                'start_date'   => '2022-06-01',
                'end_date'     => '2022-08-31',
            ],
            [
                'admin_id'     => $admin->id,
                'periode_name' => 'Penerimaan Semester Genap 2022/2023',
                'start_date'   => '2022-11-01',
                'end_date'     => '2023-01-31',
            ],
            [
                'admin_id'     => $admin->id,
                'periode_name' => 'Penerimaan Semester Ganjil 2023/2024',
                'start_date'   => '2023-06-01',
                'end_date'     => '2023-08-31',
            ],
            [
                'admin_id'     => $admin->id,
                'periode_name' => 'Penerimaan Semester Genap 2023/2024',
                'start_date'   => '2023-11-01',
                'end_date'     => '2024-01-31',
            ],
            [
                'admin_id'     => $admin->id,
                'periode_name' => 'Penerimaan Semester Ganjil 2024/2025',
                'start_date'   => '2024-06-01',
                'end_date'     => '2024-08-31',
            ],
            [
                'admin_id'     => $admin->id,
                'periode_name' => 'Penerimaan Semester Genap 2024/2025',
                'start_date'   => '2024-11-01',
                'end_date'     => '2025-01-31',
            ],
        ];

        foreach ($sessions as $session) {
            AnalysisSession::create($session);
        }
    }
}
