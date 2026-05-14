<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Student::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('id_ID');
        
        $enrolledAt = $faker->dateTimeBetween('-4 years', '-6 months');
        $status = $faker->randomElement([
            'active', 'active', 'active', 'active', // 4x lebih banyak active
            'inactive',
            'graduated',
            'dropout',
        ]);

        return [
            'nim'          => $faker->unique()->numerify('########'),
            'name'         => $faker->name(),
            'email'        => $faker->unique()->safeEmail(),
            'program_id'   => Program::inRandomOrder()->first()->id,
            'status'       => $status,
            'enrolled_at'  => $enrolledAt,
            'graduated_at' => $status === 'graduated'
                                ? $faker->dateTimeBetween($enrolledAt, 'now')
                                : null,
        ];
    }
}
