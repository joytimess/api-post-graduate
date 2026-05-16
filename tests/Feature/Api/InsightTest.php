<?php

namespace Tests\Feature\Api;

use App\Models\AnalysisSession;
use App\Models\Insight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightTest extends TestCase
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

        $this->session = AnalysisSession::factory()->create([
            'admin_id' => $this->user->id,
        ]);

        // Seed beberapa insight
        $types = ['funnel', 'attrition', 'retention'];
        foreach ($types as $type) {
            Insight::create([
                'session_id'     => $this->session->id,
                'insight_type'   => $type,
                'description'    => "Test insight untuk {$type}",
                'recommendation' => "Test rekomendasi untuk {$type}",
            ]);
        }
    }

    public function test_can_get_insights_by_session(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/insights?session_id={$this->session->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'summary' => [
                         'total',
                         'funnel',
                         'attrition',
                         'retention',
                         'traffic',
                     ],
                     'insights',
                 ]);
    }

    public function test_can_get_all_insights_with_pagination(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/insights/all?per_page=5');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'summary',
                     'insights',
                     'pagination' => [
                         'current_page',
                         'last_page',
                         'per_page',
                         'total',
                     ],
                 ]);
    }

    public function test_can_get_insights_by_type(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/insights/type?session_id={$this->session->id}&type=funnel");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'session',
                     'type',
                     'total',
                     'insights',
                 ]);
    }

    public function test_insights_by_type_requires_valid_type(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/insights/type?session_id={$this->session->id}&type=invalid");

        $response->assertStatus(422);
    }

    public function test_insights_requires_session_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/insights');

        $response->assertStatus(422);
    }

    public function test_insights_returns_404_when_no_data(): void
    {
        $emptySession = AnalysisSession::factory()->create([
            'admin_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/insights?session_id={$emptySession->id}");

        $response->assertStatus(404);
    }

    public function test_insights_requires_authentication(): void
    {
        $response = $this->getJson("/api/insights?session_id={$this->session->id}");
        $response->assertStatus(401);
    }
}