<?php

namespace Database\Seeders;

use App\Models\DropoffReason;
use App\Models\Enrollment;
use App\Models\FunnelStage;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::all();
        $stages   = FunnelStage::orderBy('order')->get();
        $reasons  = DropoffReason::all();

        foreach ($students as $student) {
            $this->generateEnrollmentJourney($student, $stages, $reasons);
        }
    }

    private function generateEnrollmentJourney(Student $student, $stages, $reasons): void
    {
        $enrolledDate = Carbon::parse($student->enrolled_at);

        foreach ($stages as $index => $stage) {

            $dropoffChance = match($stage->order) {
                1       => 0.05,
                2       => 0.10,
                3       => 0.15,
                4       => 0.12,
                5       => 0.20,
                6       => 0.15,
                7       => 0.08,
                8       => 0.10,
                9       => 0.05,
                default => 0.10,
            };

            $isDropped = rand(1, 100) <= ($dropoffChance * 100);

            if ($isDropped) {
                $relevantReasons = $this->getRelevantReasons($reasons, $stage->order);

                Enrollment::create([
                    'student_id'        => $student->id,
                    'program_id'        => $student->program_id,
                    'stage_id'          => $stage->id,
                    'status'            => fake()->randomElement(['failed', 'dropout']),
                    'dropoff_reason_id' => $relevantReasons->random()->id,
                    'enrolled_date'     => $enrolledDate->toDateString(),
                    'completed_date'    => fake()->dateTimeBetween(
                                            $enrolledDate->toDateString(),
                                            $enrolledDate->copy()->addDays(30)->toDateString()
                                          ),
                    'notes'             => null,
                ]);

                break;
            }

            $completedDate = Carbon::instance(
                fake()->dateTimeBetween(
                    $enrolledDate->toDateString(),
                    $enrolledDate->copy()->addDays(45)->toDateString()
                )
            );

            Enrollment::create([
                'student_id'        => $student->id,
                'program_id'        => $student->program_id,
                'stage_id'          => $stage->id,
                'status'            => 'passed',
                'dropoff_reason_id' => null,
                'enrolled_date'     => $enrolledDate->toDateString(),
                'completed_date'    => $completedDate->toDateString(),
                'notes'             => null,
            ]);

            // Tahap berikutnya mulai dari selesai tahap ini
            $enrolledDate = $completedDate->copy();
        }
    }

    private function getRelevantReasons($reasons, int $stageOrder)
    {
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

        return $filtered->isNotEmpty() ? $filtered : $reasons;
    }
}