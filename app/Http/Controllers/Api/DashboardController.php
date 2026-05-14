<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\AttritionAnalysis;
use App\Models\Enrollment;
use App\Models\FunnelEntry;
use App\Models\Insight;
use App\Models\RetentionAnalysis;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/dashboard
    // Ringkasan semua analytics — untuk halaman utama
    public function index(Request $request): JsonResponse
    {
        // Ambil sesi terbaru sebagai default
        // atau bisa pilih sesi tertentu via query param ?session_id=1
        $session = $request->has('session_id')
            ? AnalysisSession::findOrFail($request->session_id)
            : AnalysisSession::latest()->first();

        if (!$session) {
            return response()->json([
                'message' => 'Belum ada sesi analisis tersedia.'
            ], 404);
        }

        return response()->json([
            'session'   => $this->getSessionInfo($session),
            'funnel'    => $this->getFunnelSummary($session),
            'attrition' => $this->getAttritionSummary($session),
            'retention' => $this->getRetentionSummary($session),
            'insights'  => $this->getInsights($session),
            'overview'  => $this->getOverview($session),
        ]);
    }

    // GET /api/dashboard/sessions
    // List semua sesi — untuk dropdown pilih periode di dashboard
    public function sessions(): JsonResponse
    {
        $sessions = AnalysisSession::with('admin')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id,
                'periode_name' => $s->periode_name,
                'start_date'   => $s->start_date,
                'end_date'     => $s->end_date,
                'created_by'   => $s->admin->name,
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    // =========================================================
    // PRIVATE METHODS
    // =========================================================

    private function getSessionInfo(AnalysisSession $session): array
    {
        return [
            'id'           => $session->id,
            'periode_name' => $session->periode_name,
            'start_date'   => $session->start_date,
            'end_date'     => $session->end_date,
        ];
    }

    private function getFunnelSummary(AnalysisSession $session): array
    {
        $entries = FunnelEntry::with('stage')
            ->where('session_id', $session->id)
            ->orderBy('stage_id')
            ->get()
            ->map(fn($f) => [
                'stage'           => $f->stage->name ?? '-',
                'stage_order'     => $f->stage->order ?? 0,
                'total_prospects' => $f->total_prospects,
                'conversion_rate' => (float) $f->conversion_rate,
                'dropoff_rate'    => (float) $f->dropoff_rate,
            ]);

        return [
            'stages'            => $entries,
            'total_start'       => $entries->first()['total_prospects'] ?? 0,
            'total_finish'      => $entries->last()['total_prospects'] ?? 0,
            'overall_conversion' => $this->getOverallConversion($entries),
        ];
    }

    private function getAttritionSummary(AnalysisSession $session): array
    {
        $analyses = AttritionAnalysis::with('stage')
            ->where('session_id', $session->id)
            ->orderBy('stage_id')
            ->get()
            ->map(fn($a) => [
                'stage'          => $a->stage->name ?? '-',
                'stage_order'    => $a->stage->order ?? 0,
                'attrition_rate' => (float) $a->attrition_rate,
                'risk_level'     => $a->risk_level,
                'dropoff_reason' => $a->dropoff_reason,
            ]);

        // Stage paling berisiko
        $highestRisk = $analyses->sortByDesc('attrition_rate')->first();

        return [
            'stages'       => $analyses->values(),
            'highest_risk' => $highestRisk,
            'risk_summary' => [
                'critical' => $analyses->where('risk_level', 'critical')->count(),
                'high'     => $analyses->where('risk_level', 'high')->count(),
                'medium'   => $analyses->where('risk_level', 'medium')->count(),
                'low'      => $analyses->where('risk_level', 'low')->count(),
            ],
        ];
    }

    private function getRetentionSummary(AnalysisSession $session): array
    {
        $retention = RetentionAnalysis::where('session_id', $session->id)->first();

        if (!$retention) {
            return [
                'retention_rate'    => 0,
                'active_students'   => 0,
                'inactive_students' => 0,
                'total_students'    => 0,
            ];
        }

        return [
            'retention_rate'    => (float) $retention->retention_rate,
            'active_students'   => $retention->active_students,
            'inactive_students' => $retention->inactive_students,
            'total_students'    => $retention->active_students + $retention->inactive_students,
        ];
    }

    private function getInsights(AnalysisSession $session): array
    {
        return Insight::where('session_id', $session->id)
            ->orderBy('insight_type')
            ->get()
            ->map(fn($i) => [
                'type'           => $i->insight_type,
                'description'    => $i->description,
                'recommendation' => $i->recommendation,
            ])
            ->toArray();
    }

    private function getOverview(AnalysisSession $session): array
    {
        // Total mahasiswa yang masuk di periode ini
        $totalMahasiswa = Student::whereBetween('enrolled_at', [
            $session->start_date,
            $session->end_date,
        ])->count();

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_programs'  => Student::whereBetween('enrolled_at', [
                    $session->start_date,
                    $session->end_date,
                ])
                ->distinct('program_id')
                ->count('program_id'),
            'total_dropout'   => Enrollment::whereIn('status', ['failed', 'dropout'])
                ->whereHas('student', fn($q) => $q->whereBetween('enrolled_at', [
                    $session->start_date,
                    $session->end_date,
                ]))
                ->distinct('student_id')
                ->count('student_id'),
        ];
    }

    private function getOverallConversion(mixed $entries): float
    {
        $start  = $entries->first()['total_prospects'] ?? 0;
        $finish = $entries->last()['total_prospects'] ?? 0;

        if ($start === 0) return 0;

        return round(($finish / $start) * 100, 2);
    }
}