<?php

namespace Database\Factories;

use App\Models\AnalysisSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalysisSessionFactory extends Factory
{
    protected $model = AnalysisSession::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', '-6 months');

        return [
            'admin_id'     => User::factory(),
            'periode_name' => 'Penerimaan Semester ' . $this->faker->randomElement(['Ganjil', 'Genap']) . ' ' . $this->faker->year(),
            'start_date'   => $startDate,
            'end_date'     => $this->faker->dateTimeBetween($startDate, '+3 months'),
        ];
    }
}