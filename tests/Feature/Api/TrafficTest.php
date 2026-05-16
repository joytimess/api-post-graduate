<?php

namespace Tests\Feature\Api;

use App\Models\AnalysisSession;
use App\Models\FunnelStage;
use App\Models\TrafficPerformance;
use App\Models\TrafficSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private AnalysisSession $session;
    private TrafficSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->token = $this->user->createToken('auth_token')->plainTextToken;

        $stages = [
            ['name' => 'Informasi & Ketertarikan', 'order' => 1, 'description' => '-'],
            ['name' => 'Pendaftaran Online',        'order' => 2, 'description' => '-'],
            ['name' => 'Kelengkapan Berkas',        'order' => 3, 'description' => '-'],
            ['name' => 'Seleksi Administrasi',      'order' => 4, 'description' => '-'],
            ['name' => 'Tes Masuk',                 'order' => 5, 'description' => '-'],
            ['name' => 'Wawancara',                 'order' => 6, 'description' => '-'],
            ['name' => 'Pengumuman Hasil',           'order' => 7, 'description' => '-'],
            ['name' => 'Registrasi & Pembayaran',   'order' => 8, 'description' => '-'],
            ['name' => 'Aktif Kuliah',              'order' => 9, 'description' => '-'],
        ];

        foreach ($stages as $stage) {
            FunnelStage::create($stage);
        }

        $this->session = AnalysisSession::factory()->create([
            'admin_id' => $this->user->id,
        ]);

        $this->source = TrafficSource::create([
            'name'          => 'Instagram Ads',
            'category'      => 'social_media',
            'cost_per_lead' => 45000,
        ]);
    }

    public function test_can_get_traffic_data(): void
    {
        TrafficPerformance::create([
            'session_id'      => $this->session->id,
            'source_id'       => $this->source->id,
            'impressions'     => 5000,
            'clicks'          => 300,
            'leads'           => 50,
            'enrollments'     => 10,
            'conversion_rate' => 20.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/traffic?session_id={$this->session->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'summary' => [
                         'total_impressions',
                         'total_clicks',
                         'total_leads',
                         'total_enrollments',
                         'avg_conversion',
                         'top_source',
                     ],
                     'paid_vs_organic',
                     'organic_summary',
                     'paid_summary',
                     'performances',
                 ]);
    }

    public function test_traffic_returns_404_when_no_data(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/traffic?session_id={$this->session->id}");

        $response->assertStatus(404);
    }

    public function test_traffic_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/traffic');

        $response->assertStatus(422);
    }

    public function test_traffic_by_category_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/traffic/by-category');

        $response->assertStatus(422);
    }

    public function test_traffic_source_detail_requires_source_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/traffic/source');

        $response->assertStatus(422);
    }

    public function test_traffic_sources_returns_list(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/traffic/sources');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'paid',
                     'organic',
                     'sources',
                 ]);
    }

    public function test_traffic_requires_authentication(): void
    {
        $response = $this->getJson("/api/traffic?session_id={$this->session->id}");
        $response->assertStatus(401);
    }
}