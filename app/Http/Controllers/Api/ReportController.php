<?php

namespace App\Http\Controllers\Api;

use App\Exports\AnalyticsExport;
use App\Exports\CsvExport;
use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\AttritionAnalysis;
use App\Models\FunnelEntry;
use App\Models\Insight;
use App\Models\Report;
use App\Models\RetentionAnalysis;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // GET /api/reports?session_id=1
    // List semua report yang pernah diekspor di sesi ini
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $reports = Report::where('session_id', $request->session_id)
            ->orderByDesc('exported_at')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'file_type'   => $r->file_type,
                'exported_at' => $r->exported_at,
            ]);

        return response()->json([
            'session_id' => $request->session_id,
            'total'      => $reports->count(),
            'reports'    => $reports,
        ]);
    }

    // GET /api/reports/export/pdf?session_id=1
    // Export laporan PDF
    public function exportPdf(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);
        $data    = $this->buildReportData($session);

        $pdf = Pdf::loadView('resources.views.reports.analytics', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'laporan-analytics-' . str()->slug($session->periode_name) . '.pdf';

        // Simpan rekam jejak ke tabel reports
        Report::create([
            'session_id'  => $session->id,
            'name'        => $filename,
            'path_file'   => 'exports/' . $filename,
            'file_type'   => 'pdf',
            'exported_at' => now(),
        ]);

        return $pdf->download($filename);
    }

    // GET /api/reports/export/xlsx?session_id=1
    // Export Excel
    public function exportXlsx(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session  = AnalysisSession::findOrFail($request->session_id);
        $filename = 'laporan-analytics-' . str()->slug($session->periode_name) . '.xlsx';

        Report::create([
            'session_id'  => $session->id,
            'name'        => $filename,
            'path_file'   => 'exports/' . $filename,
            'file_type'   => 'xlsx',
            'exported_at' => now(),
        ]);

        return Excel::download(new AnalyticsExport($session), $filename);
    }

    // GET /api/reports/export/csv?session_id=1
    // Export CSV
    public function exportCsv(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session  = AnalysisSession::findOrFail($request->session_id);
        $filename = 'laporan-analytics-' . str()->slug($session->periode_name) . '.csv';

        Report::create([
            'session_id'  => $session->id,
            'name'        => $filename,
            'path_file'   => 'exports/' . $filename,
            'file_type'   => 'csv',
            'exported_at' => now(),
        ]);

        return Excel::download(new CsvExport($session), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    // =========================================================
    // PRIVATE HELPER
    // =========================================================
    private function buildReportData(AnalysisSession $session): array
    {
        // Funnel
        $funnelEntries = FunnelEntry::with('stage')
            ->where('session_id', $session->id)
            ->orderBy('stage_id')
            ->get();

        $funnelStages = $funnelEntries->map(fn($f) => [
            'stage'           => $f->stage->name ?? '-',
            'stage_order'     => $f->stage->order ?? 0,
            'total_prospects' => $f->total_prospects,
            'conversion_rate' => (float) $f->conversion_rate,
            'dropoff_rate'    => (float) $f->dropoff_rate,
        ]);

        $funnel = [
            'stages'             => $funnelStages,
            'total_start'        => $funnelStages->first()['total_prospects'] ?? 0,
            'total_finish'       => $funnelStages->last()['total_prospects'] ?? 0,
            'overall_conversion' => $funnelStages->first()['total_prospects'] > 0
                ? round(($funnelStages->last()['total_prospects'] / $funnelStages->first()['total_prospects']) * 100, 2)
                : 0,
        ];

        // Attrition
        $attritionData = AttritionAnalysis::with('stage')
            ->where('session_id', $session->id)
            ->orderBy('stage_id')
            ->get();

        $attritionStages = $attritionData->map(fn($a) => [
            'stage'          => $a->stage->name ?? '-',
            'stage_order'    => $a->stage->order ?? 0,
            'attrition_rate' => (float) $a->attrition_rate,
            'risk_level'     => $a->risk_level,
            'dropoff_reason' => $a->dropoff_reason,
        ]);

        $attrition = [
            'stages'       => $attritionStages,
            'avg_attrition' => round($attritionStages->avg('attrition_rate'), 2),
            'highest_risk' => $attritionStages->sortByDesc('attrition_rate')->first(),
        ];

        // Retention
        $retentionData = RetentionAnalysis::where('session_id', $session->id)->first();
        $retention = [
            'retention_rate'    => $retentionData ? (float) $retentionData->retention_rate : 0,
            'active_students'   => $retentionData?->active_students ?? 0,
            'inactive_students' => $retentionData?->inactive_students ?? 0,
        ];

        // Insights
        $insights = Insight::where('session_id', $session->id)
            ->get()
            ->map(fn($i) => [
                'type'           => $i->insight_type,
                'description'    => $i->description,
                'recommendation' => $i->recommendation,
            ])
            ->toArray();

        // Overview
        $overview = [
            'total_mahasiswa' => Student::whereBetween('enrolled_at', [
                $session->start_date,
                $session->end_date,
            ])->count(),
            'total_programs'  => Student::whereBetween('enrolled_at', [
                $session->start_date,
                $session->end_date,
            ])->distinct('program_id')->count('program_id'),
            'total_dropout'   => $attritionStages->sum(fn($s) => 0),
        ];

        return compact('session', 'funnel', 'attrition', 'retention', 'insights', 'overview');
    }
}