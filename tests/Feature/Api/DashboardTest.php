<?php

namespace Tests\Feature\Api;

use App\Models\AnalysisSession;
use App\Models\FunnelStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->token = $this->user->createToken('auth_token')->plainTextToken;

        // Seed funnel stages yang dibutuhkan controller
        $this->seedFunnelStages();
    }

    private function seedFunnelStages(): void
    {
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
    }

    public function test_can_get_sessions(): void
    {
        AnalysisSession::factory()->count(3)->create([
            'admin_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/dashboard/sessions');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'sessions' => [
                         '*' => ['id', 'periode_name', 'start_date', 'end_date', 'created_by']
                     ]
                 ]);
    }

    public function test_can_get_dashboard_with_session_id(): void
    {
        $session = AnalysisSession::factory()->create([
            'admin_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/dashboard?session_id={$session->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'funnel',
                     'attrition',
                     'retention',
                     'insights',
                     'overview',
                     'traffic',
                 ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }

    public function test_dashboard_trend_requires_valid_period(): void
    {
        $session = AnalysisSession::factory()->create([
            'admin_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/dashboard/trend?session_id={$session->id}&period=invalid");

        $response->assertStatus(422);
    }

    public function test_dashboard_trend_returns_data(): void
    {
        $session = AnalysisSession::factory()->create([
            'admin_id' => $this->user->id,
        ]);

        foreach (['weekly', 'monthly', 'yearly'] as $period) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                             ->getJson("/api/dashboard/trend?session_id={$session->id}&period={$period}");

            $response->assertStatus(200)
                     ->assertJsonStructure(['session', 'period', 'trend']);
        }
    }
}