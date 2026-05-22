<?php

namespace App\Exports;

use App\Models\AnalysisSession;
use App\Models\FunnelEntry;
use App\Models\AttritionAnalysis;
use App\Models\RetentionAnalysis;
use App\Models\Insight;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class AnalyticsExport implements WithMultipleSheets
{
    public function __construct(
        private AnalysisSession $session
    ) {}

    public function sheets(): array
    {
        return [
            new FunnelSheet($this->session),
            new AttritionSheet($this->session),
            new RetentionSheet($this->session),
            new InsightSheet($this->session),
            new OverviewSheet($this->session),
        ];
    }
}

// Sheet 1 — Funnel
class FunnelSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private AnalysisSession $session) {}

    public function title(): string
    {
        return 'Funnel Analysis';
    }

    public function headings(): array
    {
        return [
            'Tahap',
            'Urutan',
            'Total Prospects',
            'Conversion Rate (%)',
            'Dropoff Rate (%)',
        ];
    }

    public function collection()
    {
        return FunnelEntry::with('stage')
            ->where('session_id', $this->session->id)
            ->orderBy('stage_id')
            ->get();
    }

    public function map($row): array
    {
        return [
            $row->stage->name ?? '-',
            $row->stage->order ?? 0,
            $row->total_prospects,
            $row->conversion_rate,
            $row->dropoff_rate,
        ];
    }
}

// Sheet 2 — Attrition
class AttritionSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private AnalysisSession $session) {}

    public function title(): string
    {
        return 'Attrition Analysis';
    }

    public function headings(): array
    {
        return [
            'Tahap',
            'Urutan',
            'Attrition Rate (%)',
            'Risk Level',
            'Alasan Utama Dropout',
        ];
    }

    public function collection()
    {
        return AttritionAnalysis::with('stage')
            ->where('session_id', $this->session->id)
            ->orderBy('stage_id')
            ->get();
    }

    public function map($row): array
    {
        return [
            $row->stage->name ?? '-',
            $row->stage->order ?? 0,
            $row->attrition_rate,
            $row->risk_level,
            $row->dropoff_reason ?? '-',
        ];
    }
}

// Sheet 3 — Retention
class RetentionSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private AnalysisSession $session) {}

    public function title(): string
    {
        return 'Retention Analysis';
    }

    public function headings(): array
    {
        return [
            'Retention Rate (%)',
            'Active Students',
            'Inactive Students',
        ];
    }

    public function collection()
    {
        return RetentionAnalysis::where('session_id', $this->session->id)->get();
    }

    public function map($row): array
    {
        return [
            $row->retention_rate,
            $row->active_students,
            $row->inactive_students,
        ];
    }
}

// Sheet 4 — Insights
class InsightSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private AnalysisSession $session) {}

    public function title(): string
    {
        return 'Insights';
    }

    public function headings(): array
    {
        return [
            'Tipe Insight',
            'Deskripsi',
            'Rekomendasi',
        ];
    }

    public function collection()
    {
        return Insight::where('session_id', $this->session->id)->get();
    }

    public function map($row): array
    {
        return [
            $row->insight_type,
            $row->description,
            $row->recommendation ?? '-',
        ];
    }
}

// Sheet 5 — Overview
class OverviewSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private AnalysisSession $session) {}

    public function title(): string
    {
        return 'Overview';
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Start Date',
            'End Date',
            'Total Mahasiswa',
            'Total Program',
            'Overall Conversion (%)',
        ];
    }

    public function collection()
    {
        $funnelEntries = FunnelEntry::with('stage')
            ->where('session_id', $this->session->id)
            ->orderBy('stage_id')
            ->get();

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

        return collect([[
            $this->session->periode_name,
            $this->session->start_date->format('Y-m-d'),
            $this->session->end_date->format('Y-m-d'),
            $totalMahasiswa,
            $totalPrograms,
            $overallConversion,
        ]]);
    }
}