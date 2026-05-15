<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\Enrollment;
use App\Models\FunnelStage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    // GET /api/enrollments/pending?session_id=6
    // GET /api/enrollments/pending?session_id=6&limit=10
    public function pending(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
            'limit'      => 'nullable|integer|min:1|max:50',
        ]);

        $session    = AnalysisSession::findOrFail($request->session_id);
        $firstStage = FunnelStage::orderBy('order')->first();
        $limit      = $request->limit ?? 10;

        // Ambil student_id yang masuk di periode sesi ini
        $studentIds = Enrollment::where('stage_id', $firstStage->id)
            ->whereBetween('enrolled_date', [
                $session->start_date,
                $session->end_date,
            ])
            ->pluck('student_id');

        // Stage yang relevan untuk pending review (3-8)
        $pendingStages = FunnelStage::whereBetween('order', [3, 8])->pluck('id');

        // Ambil yang failed/dropout di stage 3-8
        $pending = Enrollment::with(['student.program', 'stage', 'dropoffReason'])
            ->whereIn('student_id', $studentIds)
            ->whereIn('stage_id', $pendingStages)
            ->whereIn('status', ['failed', 'dropout'])
            ->latest('completed_date')
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'enrollment_id'  => $e->id,
                'student_id'     => $e->student_id,
                'name'           => $e->student->name ?? '-',
                'email'          => $e->student->email ?? '-',
                'program'        => $e->student->program->name ?? '-',
                'level'          => $e->student->program->level ?? '-',
                'stage'          => $e->stage->name ?? '-',
                'stage_order'    => $e->stage->order ?? 0,
                'status'         => $e->status,
                'dropoff_reason' => $e->dropoffReason->label ?? '-',
                'completed_date' => $e->completed_date,
                'dropped_since'  => Carbon::parse($e->completed_date)->diffForHumans(),
                'avatar'         => 'https://ui-avatars.com/api/?name=' . urlencode($e->student->name ?? 'Unknown') . '&background=EF4444&color=fff&bold=true&size=64',
            ]);

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'total'   => $pending->count(),
            'pending' => $pending,
        ]);
    }
}