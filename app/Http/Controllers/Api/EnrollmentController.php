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

        // Ambil enrollment dengan status ongoing
        $pending = Enrollment::with(['student.program', 'stage'])
            ->whereIn('student_id', $studentIds)
            ->where('status', 'ongoing')
            ->latest('enrolled_date')
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'enrollment_id' => $e->id,
                'student_id'    => $e->student_id,
                'name'          => $e->student->name ?? '-',
                'email'         => $e->student->email ?? '-',
                'program'       => $e->student->program->name ?? '-',
                'level'         => $e->student->program->level ?? '-',
                'stage'         => $e->stage->name ?? '-',
                'stage_order'   => $e->stage->order ?? 0,
                'enrolled_date' => $e->enrolled_date,
                'waiting_since' => Carbon::parse($e->enrolled_date)->diffForHumans(),
                'avatar'        => 'https://ui-avatars.com/api/?name=' . urlencode($e->student->name ?? 'Unknown') . '&background=5341CD&color=fff&bold=true&size=64',
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