<?php

namespace App\Exports;

use App\Models\AnalysisSession;
use App\Models\FunnelEntry;
use App\Models\AttritionAnalysis;
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