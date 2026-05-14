<?php

namespace Database\Seeders;

use App\Models\DropoffReason;
use App\Models\Enrollment;
use App\Models\FunnelStage;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students  = Student::all();
        $stages    = FunnelStage::orderBy('order')->get();
        $reasons   = DropoffReason::all();

        foreach ($students as $student) {
            // Setiap mahasiswa mulai dari stage 1
            // dan perjalanannya berbeda-beda
            $this->generateEnrollmentJourney(
                $student,
                $stages,
                $reasons
            );
        }
    }

    private function generateEnrollmentJourney(
        Student $student,
        $stages,
        $reasons
    ): void {
        $enrolledDate = $student->enrolled_at;

        foreach ($stages as $index => $stage) {

            // Probabilitas lanjut ke tahap berikutnya
            // Makin tinggi tahap, makin ketat seleksinya
            $dropoffChance = match($stage->order) {
                1 => 0.05,  // 5%  gugur di tahap informasi
                2 => 0.10,  // 10% gugur di pendaftaran online
                3 => 0.15,  // 15% gugur di kelengkapan berkas
                4 => 0.12,  // 12% gugur di seleksi administrasi
                5 => 0.20,  // 20% gugur di tes masuk
                6 => 0.15,  // 15% gugur di wawancara
                7 => 0.08,  // 8%  gugur di pengumuman
                8 => 0.10,  // 10% gugur di registrasi & pembayaran
                9 => 0.05,  // 5%  gugur setelah aktif kuliah
                default => 0.10,
            };

            $isDropped = rand(1, 100) <= ($dropoffChance * 100);

            if ($isDropped) {
                // Pilih alasan dropout yang relevan per tahap
                $relevantReasons = $this->getRelevantReasons(
                    $reasons,
                    $stage->order
                );

                Enrollment::create([
                    'student_id'        => $student->id,
                    'program_id'        => $student->program_id,
                    'stage_id'          => $stage->id,
                    'status'            => fake()->randomElement(['failed', 'dropout']),
                    'dropoff_reason_id' => $relevantReasons->random()->id,
                    'enrolled_date'     => $enrolledDate,
                    'completed_date'    => fake()->dateTimeBetween(
                                            $enrolledDate,
                                            '+2 months'
                                          ),
                    'notes'             => null,
                ]);

                // Mahasiswa gugur — tidak lanjut ke tahap berikutnya
                break;
            }

            // Mahasiswa lanjut — buat record passed untuk tahap ini
            $completedDate = fake()->dateTimeBetween(
                $enrolledDate,
                '+1 month'
            );

            Enrollment::create([
                'student_id'        => $student->id,
                'program_id'        => $student->program_id,
                'stage_id'          => $stage->id,
                'status'            => $index === $stages->count() - 1
                                        ? 'passed'   // tahap terakhir
                                        : 'passed',
                'dropoff_reason_id' => null,
                'enrolled_date'     => $enrolledDate,
                'completed_date'    => $completedDate,
                'notes'             => null,
            ]);

            // Tanggal mulai tahap berikutnya = selesai tahap ini
            $enrolledDate = $completedDate;
        }
    }

    private function getRelevantReasons($reasons, int $stageOrder)
    {
        // Map tahap ke kategori alasan yang masuk akal
        $categoryMap = match(true) {
            $stageOrder <= 2 => ['personal', 'unknown'],
            $stageOrder == 3 => ['administrative', 'personal', 'unknown'],
            $stageOrder == 4 => ['administrative', 'academic'],
            $stageOrder == 5 => ['academic', 'personal'],
            $stageOrder == 6 => ['academic', 'personal'],
            $stageOrder == 7 => ['personal', 'unknown'],
            $stageOrder == 8 => ['financial', 'personal', 'administrative'],
            $stageOrder == 9 => ['financial', 'personal', 'unknown'],
            default          => ['unknown'],
        };

        $filtered = $reasons->whereIn('category', $categoryMap);

        // Fallback kalau filtered kosong
        return $filtered->isNotEmpty() ? $filtered : $reasons;
    }
}
