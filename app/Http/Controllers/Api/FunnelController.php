<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\Enrollment;
use App\Models\FunnelEntry;
use App\Models\FunnelStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FunnelController extends Controller
{
    // GET /api/funnel?session_id=1
    // Data funnel lengkap per sesi
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);

        $entries = FunnelEntry::with('stage')
            ->where('session_id', $session->id)
            ->orderBy('stage_id')
            ->get();

        if ($entries->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data funnel untuk sesi ini.'
            ], 404);
        }

        $stages = $entries->map(fn($f) => [
            'stage_id'        => $f->stage_id,
            'stage'           => $f->stage->name ?? '-',
            'stage_order'     => $f->stage->order ?? 0,
            'total_prospects' => $f->total_prospects,
            'conversion_rate' => (float) $f->conversion_rate,
            'dropoff_rate'    => (float) $f->dropoff_rate,
        ]);

        $totalStart  = $stages->first()['total_prospects'] ?? 0;
        $totalFinish = $stages->last()['total_prospects'] ?? 0;

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
                'start_date'   => $session->start_date,
                'end_date'     => $session->end_date,
            ],
            'funnel' => [
                'stages'             => $stages,
                'total_start'        => $totalStart,
                'total_finish'       => $totalFinish,
                'overall_conversion' => $totalStart > 0
                    ? round(($totalFinish / $totalStart) * 100, 2)
                    : 0,
                'total_dropoff'      => $totalStart - $totalFinish,
            ],
        ]);
    }

    // GET /api/funnel/stage?session_id=1&stage_id=1
    // Detail satu stage — siapa saja mahasiswanya dan statusnya
    public function stageDetail(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
            'stage_id'   => 'required|exists:funnel_stages,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);
        $stage   = FunnelStage::findOrFail($request->stage_id);

        // Ambil student_id yang masuk di periode sesi ini
        $firstStage  = FunnelStage::orderBy('order')->first();
        $studentIds  = Enrollment::where('stage_id', $firstStage->id)
            ->whereBetween('enrolled_date', [
                $session->start_date,
                $session->end_date,
            ])
            ->pluck('student_id');

        $enrollments = Enrollment::with(['student.program', 'dropoffReason'])
            ->where('stage_id', $stage->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->map(fn($e) => [
                'student_id'     => $e->student_id,
                'nim'            => $e->student->nim ?? '-',
                'name'           => $e->student->name ?? '-',
                'program'        => $e->student->program->name ?? '-',
                'status'         => $e->status,
                'enrolled_date'  => $e->enrolled_date,
                'completed_date' => $e->completed_date,
                'dropoff_reason' => $e->dropoffReason->label ?? null,
            ]);

        // Hitung summary per status
        $summary = [
            'passed'  => $enrollments->where('status', 'passed')->count(),
            'failed'  => $enrollments->where('status', 'failed')->count(),
            'dropout' => $enrollments->where('status', 'dropout')->count(),
            'ongoing' => $enrollments->where('status', 'ongoing')->count(),
            'total'   => $enrollments->count(),
        ];

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'stage'       => [
                'id'    => $stage->id,
                'name'  => $stage->name,
                'order' => $stage->order,
            ],
            'summary'     => $summary,
            'enrollments' => $enrollments,
        ]);
    }

    // GET /api/funnel/comparison?stage_id=1
    // Perbandingan satu stage di semua sesi — untuk trend chart
    public function comparison(Request $request): JsonResponse
    {
        $request->validate([
            'stage_id' => 'required|exists:funnel_stages,id',
        ]);

        $stage = FunnelStage::findOrFail($request->stage_id);

        $data = FunnelEntry::with('session')
            ->where('stage_id', $stage->id)
            ->orderBy('session_id')
            ->get()
            ->map(fn($f) => [
                'session_id'      => $f->session_id,
                'periode_name'    => $f->session->periode_name ?? '-',
                'total_prospects' => $f->total_prospects,
                'conversion_rate' => (float) $f->conversion_rate,
                'dropoff_rate'    => (float) $f->dropoff_rate,
            ]);

        return response()->json([
            'stage'      => [
                'id'    => $stage->id,
                'name'  => $stage->name,
                'order' => $stage->order,
            ],
            'comparison' => $data,
        ]);
    }
}