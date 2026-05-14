<?php

namespace Database\Seeders;

use App\Models\FunnelStage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProgramSeeder::class,
            FunnelStageSeeder::class,
            DropoffReasonSeeder::class,
            TrafficSourceSeeder::class,
            StudentSeeder::class,
            AnalysisSessionSeeder::class,
            EnrollmentSeeder::class,
        ]);
    }
}
