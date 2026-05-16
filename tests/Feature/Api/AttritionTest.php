<?php

namespace Tests\Feature\Api;

use App\Models\AnalysisSession;
use App\Models\AttritionAnalysis;
use App\Models\FunnelStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttritionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private AnalysisSession $session;

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
    }

    public function test_can_get_attrition_data(): void
    {
        FunnelStage::all()->each(function ($stage) {
            AttritionAnalysis::create([
                'session_id'     => $this->session->id,
                'stage_id'       => $stage->id,
                'risk_level'     => 'low',
                'attrition_rate' => rand(0, 30),
                'dropoff_reason' => null,
            ]);
        });

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/attrition?session_id={$this->session->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'avg_days_to_attrition',
                     'attrition' => [
                         'stages',
                         'highest_risk',
                         'avg_attrition',
                         'risk_summary',
                     ]
                 ]);
    }

    public function test_attrition_returns_404_when_no_data(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/attrition?session_id={$this->session->id}");

        $response->assertStatus(404);
    }

    public function test_attrition_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/attrition');

        $response->assertStatus(422);
    }

    public function test_attrition_comparison_requires_stage_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/attrition/comparison');

        $response->assertStatus(422);
    }

    public function test_attrition_reasons_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/attrition/reasons');

        $response->assertStatus(422);
    }

    public function test_attrition_heatmap_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/attrition/heatmap');

        $response->assertStatus(422);
    }

    public function test_attrition_requires_authentication(): void
    {
        $response = $this->getJson("/api/attrition?session_id={$this->session->id}");
        $response->assertStatus(401);
    }
}