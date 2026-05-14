<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\AttritionAnalysis;
use App\Models\DropoffReason;
use App\Models\Enrollment;
use App\Models\FunnelStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttritionController extends Controller
{
    // GET /api/attrition?session_id=1
    // Data attrition lengkap per sesi
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);

        $analyses = AttritionAnalysis::with('stage')
            ->where('session_id', $session->id)
            ->orderBy('stage_id')
            ->get();

        if ($analyses->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data attrition untuk sesi ini.'
            ], 404);
        }

        $stages = $analyses->map(fn($a) => [
            'stage_id'       => $a->stage_id,
            'stage'          => $a->stage->name ?? '-',
            'stage_order'    => $a->stage->order ?? 0,
            'attrition_rate' => (float) $a->attrition_rate,
            'risk_level'     => $a->risk_level,
            'dropoff_reason' => $a->dropoff_reason,
        ]);

        // Risk summary
        $riskSummary = [
            'critical' => $stages->where('risk_level', 'critical')->count(),
            'high'     => $stages->where('risk_level', 'high')->count(),
            'medium'   => $stages->where('risk_level', 'medium')->count(),
            'low'      => $stages->where('risk_level', 'low')->count(),
        ];

        // Stage paling berisiko
        $highestRisk = $stages->sortByDesc('attrition_rate')->first();

        // Rata-rata attrition rate keseluruhan
        $avgAttrition = round($stages->avg('attrition_rate'), 2);

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
                'start_date'   => $session->start_date,
                'end_date'     => $session->end_date,
            ],
            'attrition' => [
                'stages'          => $stages->values(),
                'highest_risk'    => $highestRisk,
                'avg_attrition'   => $avgAttrition,
                'risk_summary'    => $riskSummary,
            ],
        ]);
    }

    // GET /api/attrition/stage?session_id=1&stage_id=1
    // Detail dropout per stage — siapa saja yang gugur dan alasannya
    public function stageDetail(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
            'stage_id'   => 'required|exists:funnel_stages,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);
        $stage   = FunnelStage::findOrFail($request->stage_id);

        // Ambil student_id yang masuk di periode sesi ini
        $firstStage = FunnelStage::orderBy('order')->first();
        $studentIds = Enrollment::where('stage_id', $firstStage->id)
            ->whereBetween('enrolled_date', [
                $session->start_date,
                $session->end_date,
            ])
            ->pluck('student_id');

        // Ambil yang dropout/failed saja
        $dropped = Enrollment::with(['student.program', 'dropoffReason'])
            ->where('stage_id', $stage->id)
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['failed', 'dropout'])
            ->get()
            ->map(fn($e) => [
                'student_id'     => $e->student_id,
                'nim'            => $e->student->nim ?? '-',
                'name'           => $e->student->name ?? '-',
                'program'        => $e->student->program->name ?? '-',
                'status'         => $e->status,
                'dropoff_reason' => $e->dropoffReason->label ?? '-',
                'reason_category'=> $e->dropoffReason->category ?? '-',
                'enrolled_date'  => $e->enrolled_date,
                'completed_date' => $e->completed_date,
            ]);

        // Grouping alasan dropout
        $reasonBreakdown = $dropped
            ->groupBy('dropoff_reason')
            ->map(fn($group, $reason) => [
                'reason' => $reason,
                'count'  => $group->count(),
                'percentage' => round(($group->count() / max($dropped->count(), 1)) * 100, 2),
            ])
            ->sortByDesc('count')
            ->values();

        // Grouping per kategori
        $categoryBreakdown = $dropped
            ->groupBy('reason_category')
            ->map(fn($group, $category) => [
                'category' => $category,
                'count'    => $group->count(),
                'percentage' => round(($group->count() / max($dropped->count(), 1)) * 100, 2),
            ])
            ->sortByDesc('count')
            ->values();

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'stage' => [
                'id'    => $stage->id,
                'name'  => $stage->name,
                'order' => $stage->order,
            ],
            'summary' => [
                'total_dropped' => $dropped->count(),
                'by_status'     => [
                    'failed'  => $dropped->where('status', 'failed')->count(),
                    'dropout' => $dropped->where('status', 'dropout')->count(),
                ],
            ],
            'reason_breakdown'   => $reasonBreakdown,
            'category_breakdown' => $categoryBreakdown,
            'students'           => $dropped,
        ]);
    }

    // GET /api/attrition/comparison?stage_id=1
    // Trend attrition satu stage di semua sesi
    public function comparison(Request $request): JsonResponse
    {
        $request->validate([
            'stage_id' => 'required|exists:funnel_stages,id',
        ]);

        $stage = FunnelStage::findOrFail($request->stage_id);

        $data = AttritionAnalysis::with('session')
            ->where('stage_id', $stage->id)
            ->orderBy('session_id')
            ->get()
            ->map(fn($a) => [
                'session_id'     => $a->session_id,
                'periode_name'   => $a->session->periode_name ?? '-',
                'attrition_rate' => (float) $a->attrition_rate,
                'risk_level'     => $a->risk_level,
                'dropoff_reason' => $a->dropoff_reason,
            ]);

        // Trend — naik atau turun?
        $trend = 'stable';
        if ($data->count() >= 2) {
            $first = $data->first()['attrition_rate'];
            $last  = $data->last()['attrition_rate'];
            $trend = match(true) {
                $last > $first + 5  => 'increasing',
                $last < $first - 5  => 'decreasing',
                default             => 'stable',
            };
        }

        return response()->json([
            'stage' => [
                'id'    => $stage->id,
                'name'  => $stage->name,
                'order' => $stage->order,
            ],
            'trend'      => $trend,
            'comparison' => $data,
        ]);
    }

    // GET /api/attrition/reasons?session_id=1
    // Ringkasan semua alasan dropout di satu sesi
    public function reasons(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);

        // Ambil student_id yang masuk di periode sesi ini
        $firstStage = FunnelStage::orderBy('order')->first();
        $studentIds = Enrollment::where('stage_id', $firstStage->id)
            ->whereBetween('enrolled_date', [
                $session->start_date,
                $session->end_date,
            ])
            ->pluck('student_id');

        $dropped = Enrollment::with(['dropoffReason', 'stage'])
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['failed', 'dropout'])
            ->whereNotNull('dropoff_reason_id')
            ->get();

        // Breakdown per alasan
        $byReason = $dropped
            ->groupBy('dropoff_reason_id')
            ->map(fn($group) => [
                'reason'     => $group->first()->dropoffReason->label ?? '-',
                'category'   => $group->first()->dropoffReason->category ?? '-',
                'count'      => $group->count(),
                'percentage' => round(($group->count() / max($dropped->count(), 1)) * 100, 2),
            ])
            ->sortByDesc('count')
            ->values();

        // Breakdown per kategori
        $byCategory = $dropped
            ->groupBy(fn($e) => $e->dropoffReason->category ?? 'unknown')
            ->map(fn($group, $category) => [
                'category'   => $category,
                'count'      => $group->count(),
                'percentage' => round(($group->count() / max($dropped->count(), 1)) * 100, 2),
            ])
            ->sortByDesc('count')
            ->values();

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'summary' => [
                'total_dropped' => $dropped->count(),
            ],
            'by_reason'   => $byReason,
            'by_category' => $byCategory,
        ]);
    }
}