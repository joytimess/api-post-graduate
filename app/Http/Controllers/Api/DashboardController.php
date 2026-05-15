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
use App\Models\TrafficPerformance;
use App\Models\FunnelStage;

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
            'traffic'   => $this->getTrafficSummary($session),
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
        $firstStage = FunnelStage::orderBy('order')->first();
        $lastStage  = FunnelStage::orderBy('order', 'desc')->first();

        // Student IDs yang masuk di periode ini
        $studentIds = Enrollment::where('stage_id', $firstStage->id)
            ->whereBetween('enrolled_date', [
                $session->start_date,
                $session->end_date,
            ])
            ->pluck('student_id');

        // Total Apps — yang sampai stage 2 (Pendaftaran Online)
        $appsStage   = FunnelStage::orderBy('order')->skip(1)->first();
        $totalApps   = Enrollment::where('stage_id', $appsStage->id)
            ->whereIn('student_id', $studentIds)
            ->count();

        // Total Enrolled — passed di stage terakhir (Aktif Kuliah)
        $totalEnrolled = Enrollment::where('stage_id', $lastStage->id)
            ->where('status', 'passed')
            ->whereIn('student_id', $studentIds)
            ->count();

        // Avg Conv. Time — rata-rata hari dari stage 1 ke stage 9
        $firstEnrollments = Enrollment::where('stage_id', $firstStage->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $lastEnrollments = Enrollment::where('stage_id', $lastStage->id)
            ->where('status', 'passed')
            ->whereIn('student_id', $studentIds)
            ->get();

        $avgDays = $lastEnrollments->avg(function ($e) use ($firstEnrollments) {
            $first = $firstEnrollments->get($e->student_id);
            if (!$first) return null;
            return \Carbon\Carbon::parse($first->enrolled_date)
                ->diffInDays($e->completed_date);
        });

        // Total dropout — distinct student yang punya status failed/dropout
        $totalDropout = Enrollment::whereIn('student_id', $studentIds)
            ->whereIn('status', ['failed', 'dropout'])
            ->distinct('student_id')
            ->count('student_id');

        return [
            'total_mahasiswa' => $studentIds->count(),
            'total_apps'      => $totalApps,
            'total_enrolled'  => $totalEnrolled,
            'total_programs'  => Student::whereIn('id', $studentIds)
                ->distinct('program_id')
                ->count('program_id'),
            'total_dropout'   => $totalDropout,
            'avg_conv_days'   => $avgDays ? round($avgDays) : 0,
        ];
    }

    private function getOverallConversion(mixed $entries): float
    {
        $start  = $entries->first()['total_prospects'] ?? 0;
        $finish = $entries->last()['total_prospects'] ?? 0;

        if ($start === 0) return 0;

        return round(($finish / $start) * 100, 2);
    }

    private function getTrafficSummary(AnalysisSession $session): array
    {
        $performances = TrafficPerformance::where('session_id', $session->id)->get();

        return [
            'total_impressions' => $performances->sum('impressions'),
            'total_clicks'      => $performances->sum('clicks'),
            'total_leads'       => $performances->sum('leads'),
            'top_source'        => $performances->sortByDesc('enrollments')
                                    ->first()?->source?->name ?? '-',
        ];
    }

    // GET /api/dashboard/trend?session_id=6&period=weekly
    public function trend(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
            'period'     => 'required|in:weekly,monthly,yearly',
        ]);

        $session    = AnalysisSession::findOrFail($request->session_id);
        $firstStage = FunnelStage::orderBy('order')->first();

        // Ambil student_id yang masuk di periode sesi ini
        $studentIds = Enrollment::where('stage_id', $firstStage->id)
            ->whereBetween('enrolled_date', [
                $session->start_date,
                $session->end_date,
            ])
            ->pluck('student_id');

        $enrollments = Enrollment::where('stage_id', $firstStage->id)
            ->whereIn('student_id', $studentIds)
            ->get();

        $data = match($request->period) {
            'weekly'  => $this->groupByWeek($enrollments),
            'monthly' => $this->groupByMonth($enrollments),
            'yearly'  => $this->groupByYear($enrollments),
        };

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'period' => $request->period,
            'trend'  => $data,
        ]);
    }

    private function groupByWeek($enrollments): array
    {
        return $enrollments
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->enrolled_date)->startOfWeek()->format('d M Y'))
            ->map(fn($group, $week) => [
                'label' => $week,
                'total' => $group->count(),
            ])
            ->sortKeys()
            ->values()
            ->toArray();
    }

    private function groupByMonth($enrollments): array
    {
        return $enrollments
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->enrolled_date)->format('M Y'))
            ->map(fn($group, $month) => [
                'label' => $month,
                'total' => $group->count(),
            ])
            ->sortBy(fn($item, $key) => \Carbon\Carbon::parse($key)->timestamp)
            ->values()
            ->toArray();
    }

    private function groupByYear($enrollments): array
    {
        return $enrollments
            ->groupBy(fn($e) => \Carbon\Carbon::parse($e->enrolled_date)->format('Y'))
            ->map(fn($group, $year) => [
                'label' => $year,
                'total' => $group->count(),
            ])
            ->sortKeys()
            ->values()
            ->toArray();
    }
}