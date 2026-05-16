<?php

namespace Tests\Feature\Api;

use App\Models\AnalysisSession;
use App\Models\FunnelEntry;
use App\Models\FunnelStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelTest extends TestCase
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

        // Seed funnel stages
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

    public function test_can_get_funnel_data(): void
    {
        // Buat funnel entries
        FunnelStage::all()->each(function ($stage) {
            FunnelEntry::create([
                'session_id'      => $this->session->id,
                'stage_id'        => $stage->id,
                'total_prospects' => rand(10, 100),
                'conversion_rate' => rand(50, 100),
                'dropoff_rate'    => rand(0, 50),
            ]);
        });

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/funnel?session_id={$this->session->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'funnel' => [
                         'stages',
                         'total_start',
                         'total_finish',
                         'overall_conversion',
                         'total_dropoff',
                     ]
                 ]);
    }

    public function test_funnel_returns_404_when_no_data(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/funnel?session_id={$this->session->id}");

        $response->assertStatus(404);
    }

    public function test_funnel_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/funnel');

        $response->assertStatus(422);
    }

    public function test_funnel_comparison_requires_stage_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/funnel/comparison');

        $response->assertStatus(422);
    }

    public function test_funnel_comparison_returns_data(): void
    {
        $stage = FunnelStage::first();

        FunnelEntry::create([
            'session_id'      => $this->session->id,
            'stage_id'        => $stage->id,
            'total_prospects' => 50,
            'conversion_rate' => 80,
            'dropoff_rate'    => 20,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/funnel/comparison?stage_id={$stage->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'stage',
                     'comparison',
                 ]);
    }

    public function test_funnel_requires_authentication(): void
    {
        $response = $this->getJson("/api/funnel?session_id={$this->session->id}");
        $response->assertStatus(401);
    }
}