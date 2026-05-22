<?php

namespace App\Exports;

use App\Models\AnalysisSession;
use App\Models\AttritionAnalysis;
use App\Models\FunnelEntry;
use App\Models\Insight;
use App\Models\RetentionAnalysis;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class CsvExport implements FromArray, WithTitle
{
    public function __construct(private AnalysisSession $session) {}

    public function title(): string
    {
        return 'Analytics';
    }

    public function array(): array
    {
        $rows = [];

        // ── FUNNEL ANALYSIS ──────────────────────────────────────
        $rows[] = ['FUNNEL ANALYSIS'];
        $rows[] = ['Tahap', 'Urutan', 'Total Prospects', 'Conversion Rate (%)', 'Dropoff Rate (%)'];

        $funnelEntries = FunnelEntry::with('stage')
            ->where('session_id', $this->session->id)
            ->orderBy('stage_id')
            ->get();

        foreach ($funnelEntries as $entry) {
            $rows[] = [
                $entry->stage->name ?? '-',
                $entry->stage->order ?? 0,
                $entry->total_prospects,
                $entry->conversion_rate,
                $entry->dropoff_rate,
            ];
        }

        $rows[] = [];

        // ── ATTRITION ANALYSIS ───────────────────────────────────
        $rows[] = ['ATTRITION ANALYSIS'];
        $rows[] = ['Tahap', 'Urutan', 'Attrition Rate (%)', 'Risk Level', 'Alasan Dropout'];

        $attritionData = AttritionAnalysis::with('stage')
            ->where('session_id', $this->session->id)
            ->orderBy('stage_id')
            ->get();

        foreach ($attritionData as $entry) {
            $rows[] = [
                $entry->stage->name ?? '-',
                $entry->stage->order ?? 0,
                $entry->attrition_rate,
                $entry->risk_level,
                $entry->dropoff_reason ?? '-',
            ];
        }

        $rows[] = [];

        // ── RETENTION ANALYSIS ───────────────────────────────────
        $rows[] = ['RETENTION ANALYSIS'];
        $rows[] = ['Retention Rate (%)', 'Active Students', 'Inactive Students'];

        $retention = RetentionAnalysis::where('session_id', $this->session->id)->first();
        if ($retention) {
            $rows[] = [
                $retention->retention_rate,
                $retention->active_students,
                $retention->inactive_students,
            ];
        }

        $rows[] = [];

        // ── INSIGHTS ─────────────────────────────────────────────
        $rows[] = ['INSIGHTS'];
        $rows[] = ['Tipe Insight', 'Deskripsi', 'Rekomendasi'];

        $insights = Insight::where('session_id', $this->session->id)->get();
        foreach ($insights as $insight) {
            $rows[] = [
                $insight->insight_type,
                $insight->description,
                $insight->recommendation ?? '-',
            ];
        }

        $rows[] = [];

        // ── OVERVIEW ─────────────────────────────────────────────
        $rows[] = ['OVERVIEW'];
        $rows[] = ['Periode', 'Start Date', 'End Date', 'Total Mahasiswa', 'Total Program', 'Overall Conversion (%)'];

        $totalStart  = $funnelEntries->first()?->total_prospects ?? 0;
        $totalFinish = $funnelEntries->last()?->total_prospects ?? 0;
        $overallConversion = $totalStart > 0
            ? round(($totalFinish / $totalStart) * 100, 2)
            : 0;

        $totalMahasiswa = Student::whereBetween('enrolled_at', [
            $this->session->start_date,
            $this->session->end_date,
        ])->count();

        $totalPrograms = Student::whereBetween('enrolled_at', [
            $this->session->start_date,
            $this->session->end_date,
        ])->distinct('program_id')->count('program_id');

        $rows[] = [
            $this->session->periode_name,
            $this->session->start_date->format('Y-m-d'),
            $this->session->end_date->format('Y-m-d'),
            $totalMahasiswa,
            $totalPrograms,
            $overallConversion,
        ];

        return $rows;
    }
}
