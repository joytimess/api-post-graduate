<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\Enrollment;
use App\Models\FunnelStage;
use App\Models\RetentionAnalysis;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RetentionController extends Controller
{
    // GET /api/retention?session_id=1
    // Data retention lengkap per sesi
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session   = AnalysisSession::findOrFail($request->session_id);
        $retention = RetentionAnalysis::where('session_id', $session->id)->first();

        if (!$retention) {
            return response()->json([
                'message' => 'Belum ada data retention untuk sesi ini.'
            ], 404);
        }

        $totalStudents = $retention->active_students + $retention->inactive_students;

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
                'start_date'   => $session->start_date,
                'end_date'     => $session->end_date,
            ],
            'retention' => [
                'retention_rate'    => (float) $retention->retention_rate,
                'active_students'   => $retention->active_students,
                'inactive_students' => $retention->inactive_students,
                'total_students'    => $totalStudents,
                'status'            => $retention->retention_rate >= 70 ? 'good' : 'needs_attention',
                'target'            => 70.00,
                'gap_to_target'     => round(max(70 - (float) $retention->retention_rate, 0), 2),
            ],
        ]);
    }

    // GET /api/retention/students?session_id=1&status=inactive
    // List mahasiswa berdasarkan status retention
    public function students(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
            'status'     => 'nullable|in:active,inactive,graduated,dropout',
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

        $query = Student::with('program')
            ->whereIn('id', $studentIds);

        // Filter by status kalau ada
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->get()->map(fn($s) => [
            'student_id'   => $s->id,
            'nim'          => $s->nim,
            'name'         => $s->name,
            'program'      => $s->program->name ?? '-',
            'level'        => $s->program->level ?? '-',
            'status'       => $s->status,
            'enrolled_at'  => $s->enrolled_at,
            'graduated_at' => $s->graduated_at,
        ]);

        // Summary per status
        $summary = [
            'active'    => $students->where('status', 'active')->count(),
            'inactive'  => $students->where('status', 'inactive')->count(),
            'graduated' => $students->where('status', 'graduated')->count(),
            'dropout'   => $students->where('status', 'dropout')->count(),
            'total'     => $students->count(),
        ];

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'summary'  => $summary,
            'students' => $students,
        ]);
    }

    // GET /api/retention/comparison
    // Trend retention rate di semua sesi
    public function comparison(): JsonResponse
    {
        $data = RetentionAnalysis::with('session')
            ->orderBy('session_id')
            ->get()
            ->map(fn($r) => [
                'session_id'        => $r->session_id,
                'periode_name'      => $r->session->periode_name ?? '-',
                'retention_rate'    => (float) $r->retention_rate,
                'active_students'   => $r->active_students,
                'inactive_students' => $r->inactive_students,
                'total_students'    => $r->active_students + $r->inactive_students,
                'status'            => $r->retention_rate >= 70 ? 'good' : 'needs_attention',
            ]);

        // Hitung trend
        $trend = 'stable';
        if ($data->count() >= 2) {
            $first = $data->first()['retention_rate'];
            $last  = $data->last()['retention_rate'];
            $trend = match(true) {
                $last > $first + 5  => 'increasing',
                $last < $first - 5  => 'decreasing',
                default             => 'stable',
            };
        }

        // Rata-rata retention rate semua sesi
        $avgRetention = round($data->avg('retention_rate'), 2);

        // Berapa sesi yang di bawah target
        $belowTarget = $data->where('retention_rate', '<', 70)->count();

        return response()->json([
            'summary' => [
                'avg_retention_rate' => $avgRetention,
                'total_sessions'     => $data->count(),
                'below_target'       => $belowTarget,
                'above_target'       => $data->count() - $belowTarget,
                'trend'              => $trend,
            ],
            'comparison' => $data,
        ]);
    }

    // GET /api/retention/by-program?session_id=1
    // Retention rate dipecah per program studi
    public function byProgram(Request $request): JsonResponse
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

        $students = Student::with('program')
            ->whereIn('id', $studentIds)
            ->get();

        // Group per program
        $byProgram = $students
            ->groupBy('program_id')
            ->map(fn($group) => [
                'program'        => $group->first()->program->name ?? '-',
                'level'          => $group->first()->program->level ?? '-',
                'faculty'        => $group->first()->program->faculty ?? '-',
                'total'          => $group->count(),
                'active'         => $group->where('status', 'active')->count(),
                'inactive'       => $group->where('status', 'inactive')->count(),
                'graduated'      => $group->where('status', 'graduated')->count(),
                'dropout'        => $group->where('status', 'dropout')->count(),
                'retention_rate' => round(
                    ($group->where('status', 'active')->count() / max($group->count(), 1)) * 100,
                    2
                ),
            ])
            ->sortByDesc('retention_rate')
            ->values();

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'by_program' => $byProgram,
        ]);
    }
}