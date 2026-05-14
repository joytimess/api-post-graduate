<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            // Fakultas Teknik
            [
                'name'      => 'Magister Teknik Informatika',
                'code'      => 'MTI',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Teknik',
                'is_active' => true,
            ],
            [
                'name'      => 'Magister Teknik Mesin',
                'code'      => 'MTM',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Teknik',
                'is_active' => true,
            ],
            [
                'name'      => 'Magister Teknik Elektro',
                'code'      => 'MTE',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Teknik',
                'is_active' => true,
            ],
            [
                'name'      => 'Doktor Teknik Informatika',
                'code'      => 'DTI',
                'level'     => 'S3',
                'faculty'   => 'Fakultas Teknik',
                'is_active' => true,
            ],

            // Fakultas Ekonomi dan Bisnis
            [
                'name'      => 'Magister Manajemen',
                'code'      => 'MM',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Ekonomi dan Bisnis',
                'is_active' => true,
            ],
            [
                'name'      => 'Magister Administrasi Bismis',
                'code'      => 'MAB',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Ekonomi dan Bisnis',
                'is_active' => true,
            ],
            [
                'name'      => 'Magister Akuntansi',
                'code'      => 'MAKSI',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Ekonomi dan Bisnis',
                'is_active' => true,
            ],
            [
                'name'      => 'Doktor Ilmu Ekonomi',
                'code'      => 'DIE',
                'level'     => 'S3',
                'faculty'   => 'Fakultas Ekonomi dan Bisnis',
                'is_active' => true,
            ],

            // Fakultas Hukum
            [
                'name'      => 'Magister Hukum',
                'code'      => 'MH',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Hukum',
                'is_active' => true,
            ],
            [
                'name'      => 'Doktor Ilmu Hukum',
                'code'      => 'DIH',
                'level'     => 'S3',
                'faculty'   => 'Fakultas Hukum',
                'is_active' => true,
            ],

            // Fakultas Ilmu Sosial
            [
                'name'      => 'Magister Ilmu Komunikasi',
                'code'      => 'MIK',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Ilmu Sosial dan Politik',
                'is_active' => true,
            ],
            [
                'name'      => 'Magister Ilmu Politik',
                'code'      => 'MIP',
                'level'     => 'S2',
                'faculty'   => 'Fakultas Ilmu Sosial dan Politik',
                'is_active' => true,
            ],
            [
                'name'      => 'Doktor Ilmu Sosial',
                'code'      => 'DIS',
                'level'     => 'S3',
                'faculty'   => 'Fakultas Ilmu Sosial dan Politik',
                'is_active' => true,
            ],
        ];

        foreach ($programs as $program) {
            Program::create($program);
        }
    }
}
