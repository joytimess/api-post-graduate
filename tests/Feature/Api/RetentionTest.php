<?php

namespace Tests\Feature\Api;

use App\Models\AnalysisSession;
use App\Models\FunnelStage;
use App\Models\RetentionAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionTest extends TestCase
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

    public function test_can_get_retention_data(): void
    {
        RetentionAnalysis::create([
            'session_id'        => $this->session->id,
            'retention_rate'    => 65.5,
            'active_students'   => 50,
            'inactive_students' => 26,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/retention?session_id={$this->session->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'retention' => [
                         'retention_rate',
                         'active_students',
                         'inactive_students',
                         'total_students',
                         'status',
                         'target',
                         'gap_to_target',
                         'first_year_retention',
                         'dropout_rate',
                         'total_graduated',
                     ]
                 ]);
    }

    public function test_retention_returns_404_when_no_data(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/retention?session_id={$this->session->id}");

        $response->assertStatus(404);
    }

    public function test_retention_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/retention');

        $response->assertStatus(422);
    }

    public function test_retention_comparison_returns_data(): void
    {
        RetentionAnalysis::create([
            'session_id'        => $this->session->id,
            'retention_rate'    => 65.5,
            'active_students'   => 50,
            'inactive_students' => 26,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/retention/comparison');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'summary' => [
                         'avg_retention_rate',
                         'total_sessions',
                         'below_target',
                         'above_target',
                         'trend',
                     ],
                     'comparison',
                 ]);
    }

    public function test_retention_by_faculty_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/retention/by-faculty');

        $response->assertStatus(422);
    }

    public function test_retention_trend_returns_data(): void
    {
        RetentionAnalysis::create([
            'session_id'        => $this->session->id,
            'retention_rate'    => 65.5,
            'active_students'   => 50,
            'inactive_students' => 26,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/retention/trend');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'target',
                     'trend',
                 ]);
    }

    public function test_retention_requires_authentication(): void
    {
        $response = $this->getJson("/api/retention?session_id={$this->session->id}");
        $response->assertStatus(401);
    }
}