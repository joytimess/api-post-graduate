<?php

namespace Database\Seeders;

use App\Models\AnalysisSession;
use App\Models\TrafficPerformance;
use App\Models\TrafficSource;
use Illuminate\Database\Seeder;

class TrafficPerformanceSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = AnalysisSession::all();
        $sources  = TrafficSource::all();

        foreach ($sessions as $session) {
            foreach ($sources as $source) {
                // Generate angka realistis per kategori
                [$impressions, $clicks, $leads, $enrollments] = $this->generateMetrics($source->category);

                $conversionRate = $leads > 0
                    ? round(($enrollments / $leads) * 100, 2)
                    : 0;

                TrafficPerformance::create([
                    'session_id'      => $session->id,
                    'source_id'       => $source->id,
                    'impressions'     => $impressions,
                    'clicks'          => $clicks,
                    'leads'           => $leads,
                    'enrollments'     => $enrollments,
                    'conversion_rate' => $conversionRate,
                ]);
            }
        }
    }

    private function generateMetrics(string $category): array
    {
        return match($category) {
            // Social media — impresi tinggi, konversi rendah
            'social_media' => [
                $impressions  = rand(5000, 20000),
                $clicks       = rand(200, 1000),
                $leads        = rand(20, 80),
                $enrollments  = rand(2, 15),
            ],
            // Search — impresi sedang, intent tinggi
            'search' => [
                $impressions  = rand(3000, 10000),
                $clicks       = rand(300, 1200),
                $leads        = rand(30, 100),
                $enrollments  = rand(5, 20),
            ],
            // Event — impresi kecil, konversi tinggi
            'event' => [
                $impressions  = rand(200, 1000),
                $clicks       = rand(100, 500),
                $leads        = rand(20, 80),
                $enrollments  = rand(5, 25),
            ],
            // Referral — paling tinggi konversinya
            'referral' => [
                $impressions  = rand(100, 500),
                $clicks       = rand(50, 300),
                $leads        = rand(15, 60),
                $enrollments  = rand(5, 20),
            ],
            // Direct — organik, konversi sedang
            'direct' => [
                $impressions  = rand(1000, 5000),
                $clicks       = rand(100, 800),
                $leads        = rand(10, 50),
                $enrollments  = rand(2, 15),
            ],
            default => [1000, 100, 20, 5],
        };
    }
}