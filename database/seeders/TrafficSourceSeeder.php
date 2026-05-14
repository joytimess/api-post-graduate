<?php

namespace Database\Seeders;

use App\Models\TrafficSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrafficSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            // Social Media
            [
                'name'         => 'Instagram Ads',
                'category'     => 'social_media',
                'cost_per_lead' => 45000.00,
            ],
            [
                'name'         => 'Facebook Ads',
                'category'     => 'social_media',
                'cost_per_lead' => 38000.00,
            ],
            [
                'name'         => 'LinkedIn Ads',
                'category'     => 'social_media',
                'cost_per_lead' => 85000.00,
            ],
            [
                'name'         => 'TikTok Ads',
                'category'     => 'social_media',
                'cost_per_lead' => 32000.00,
            ],
            [
                'name'         => 'YouTube Ads',
                'category'     => 'social_media',
                'cost_per_lead' => 55000.00,
            ],

            // Search
            [
                'name'         => 'Google Search Ads',
                'category'     => 'search',
                'cost_per_lead' => 72000.00,
            ],
            [
                'name'         => 'Google Display Network',
                'category'     => 'search',
                'cost_per_lead' => 41000.00,
            ],
            [
                'name'         => 'SEO Organik',
                'category'     => 'search',
                'cost_per_lead' => null, // tidak berbayar
            ],

            // Event
            [
                'name'         => 'Pameran Pendidikan Nasional',
                'category'     => 'event',
                'cost_per_lead' => 120000.00,
            ],
            [
                'name'         => 'Webinar Open House',
                'category'     => 'event',
                'cost_per_lead' => 25000.00,
            ],
            [
                'name'         => 'Campus Visit & Campus Tour',
                'category'     => 'event',
                'cost_per_lead' => 30000.00,
            ],
            [
                'name'         => 'Seminar & Conference',
                'category'     => 'event',
                'cost_per_lead' => 95000.00,
            ],

            // Referral
            [
                'name'         => 'Referral Alumni',
                'category'     => 'referral',
                'cost_per_lead' => null, // organik
            ],
            [
                'name'         => 'Referral Dosen',
                'category'     => 'referral',
                'cost_per_lead' => null, // organik
            ],
            [
                'name'         => 'Kerjasama Perusahaan',
                'category'     => 'referral',
                'cost_per_lead' => 60000.00,
            ],
            [
                'name'         => 'Kerjasama Instansi Pemerintah',
                'category'     => 'referral',
                'cost_per_lead' => 55000.00,
            ],

            // Direct
            [
                'name'         => 'Website Universitas',
                'category'     => 'direct',
                'cost_per_lead' => null, // organik
            ],
            [
                'name'         => 'Walk-in Langsung',
                'category'     => 'direct',
                'cost_per_lead' => null, // organik
            ],
            [
                'name'         => 'Email Marketing',
                'category'     => 'direct',
                'cost_per_lead' => 18000.00,
            ],
        ];

        foreach ($sources as $source) {
            TrafficSource::create($source);
        }
    }
}
