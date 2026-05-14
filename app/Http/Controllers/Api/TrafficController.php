<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\TrafficPerformance;
use App\Models\TrafficSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrafficController extends Controller
{
    // GET /api/traffic?session_id=1
    // Data traffic performance lengkap per sesi
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);

        $performances = TrafficPerformance::with('source')
            ->where('session_id', $session->id)
            ->orderByDesc('enrollments')
            ->get();

        if ($performances->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data traffic untuk sesi ini.'
            ], 404);
        }

        $data = $performances->map(fn($p) => [
            'source_id'       => $p->source_id,
            'source'          => $p->source->name ?? '-',
            'category'        => $p->source->category ?? '-',
            'cost_per_lead'   => $p->source->cost_per_lead
                                    ? (float) $p->source->cost_per_lead
                                    : null,
            'impressions'     => $p->impressions,
            'clicks'          => $p->clicks,
            'leads'           => $p->leads,
            'enrollments'     => $p->enrollments,
            'conversion_rate' => (float) $p->conversion_rate,
            'ctr'             => $p->impressions > 0
                                    ? round(($p->clicks / $p->impressions) * 100, 2)
                                    : 0,
            'cost_per_enrollment' => $p->source->cost_per_lead && $p->enrollments > 0
                                    ? round(($p->source->cost_per_lead * $p->leads) / $p->enrollments, 2)
                                    : null,
            'is_paid'         => !is_null($p->source->cost_per_lead),
        ]);

        // Summary keseluruhan
        $summary = [
            'total_impressions' => $data->sum('impressions'),
            'total_clicks'      => $data->sum('clicks'),
            'total_leads'       => $data->sum('leads'),
            'total_enrollments' => $data->sum('enrollments'),
            'avg_conversion'    => round($data->avg('conversion_rate'), 2),
            'avg_ctr'           => round($data->avg('ctr'), 2),
            'top_source'        => $data->sortByDesc('enrollments')->first()['source'] ?? '-',
        ];

        // Breakdown paid vs organic
        $paidVsOrganic = [
            'paid' => [
                'sources'     => $data->where('is_paid', true)->count(),
                'leads'       => $data->where('is_paid', true)->sum('leads'),
                'enrollments' => $data->where('is_paid', true)->sum('enrollments'),
            ],
            'organic' => [
                'sources'     => $data->where('is_paid', false)->count(),
                'leads'       => $data->where('is_paid', false)->sum('leads'),
                'enrollments' => $data->where('is_paid', false)->sum('enrollments'),
            ],
        ];

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
                'start_date'   => $session->start_date,
                'end_date'     => $session->end_date,
            ],
            'summary'         => $summary,
            'paid_vs_organic' => $paidVsOrganic,
            'performances'    => $data->values(),
        ]);
    }

    // GET /api/traffic/by-category?session_id=1
    // Performance digroup per kategori sumber
    public function byCategory(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session = AnalysisSession::findOrFail($request->session_id);

        $performances = TrafficPerformance::with('source')
            ->where('session_id', $session->id)
            ->get();

        if ($performances->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data traffic untuk sesi ini.'
            ], 404);
        }

        $byCategory = $performances
            ->groupBy(fn($p) => $p->source->category ?? 'unknown')
            ->map(fn($group, $category) => [
                'category'        => $category,
                'total_sources'   => $group->count(),
                'total_impressions' => $group->sum('impressions'),
                'total_clicks'    => $group->sum('clicks'),
                'total_leads'     => $group->sum('leads'),
                'total_enrollments' => $group->sum('enrollments'),
                'avg_conversion'  => round($group->avg('conversion_rate'), 2),
                'ctr'             => $group->sum('impressions') > 0
                                        ? round(($group->sum('clicks') / $group->sum('impressions')) * 100, 2)
                                        : 0,
            ])
            ->sortByDesc('total_enrollments')
            ->values();

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'by_category' => $byCategory,
        ]);
    }

    // GET /api/traffic/source?source_id=1
    // Trend performa satu sumber di semua sesi
    public function sourceDetail(Request $request): JsonResponse
    {
        $request->validate([
            'source_id' => 'required|exists:traffic_sources,id',
        ]);

        $source = TrafficSource::findOrFail($request->source_id);

        $performances = TrafficPerformance::with('session')
            ->where('source_id', $source->id)
            ->orderBy('session_id')
            ->get();

        if ($performances->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data performa untuk sumber ini.'
            ], 404);
        }

        $data = $performances->map(fn($p) => [
            'session_id'      => $p->session_id,
            'periode_name'    => $p->session->periode_name ?? '-',
            'impressions'     => $p->impressions,
            'clicks'          => $p->clicks,
            'leads'           => $p->leads,
            'enrollments'     => $p->enrollments,
            'conversion_rate' => (float) $p->conversion_rate,
            'ctr'             => $p->impressions > 0
                                    ? round(($p->clicks / $p->impressions) * 100, 2)
                                    : 0,
        ]);

        // Trend enrollment naik atau turun
        $trend = 'stable';
        if ($data->count() >= 2) {
            $first = $data->first()['enrollments'];
            $last  = $data->last()['enrollments'];
            $trend = match(true) {
                $last > $first  => 'increasing',
                $last < $first  => 'decreasing',
                default         => 'stable',
            };
        }

        return response()->json([
            'source' => [
                'id'           => $source->id,
                'name'         => $source->name,
                'category'     => $source->category,
                'cost_per_lead' => $source->cost_per_lead
                                    ? (float) $source->cost_per_lead
                                    : null,
                'is_paid'      => !is_null($source->cost_per_lead),
            ],
            'summary' => [
                'total_impressions' => $data->sum('impressions'),
                'total_clicks'      => $data->sum('clicks'),
                'total_leads'       => $data->sum('leads'),
                'total_enrollments' => $data->sum('enrollments'),
                'avg_conversion'    => round($data->avg('conversion_rate'), 2),
                'trend'             => $trend,
            ],
            'performances' => $data,
        ]);
    }

    // GET /api/traffic/sources
    // List semua sumber traffic — untuk dropdown filter
    public function sources(): JsonResponse
    {
        $sources = TrafficSource::orderBy('category')
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id,
                'name'         => $s->name,
                'category'     => $s->category,
                'cost_per_lead' => $s->cost_per_lead
                                    ? (float) $s->cost_per_lead
                                    : null,
                'is_paid'      => !is_null($s->cost_per_lead),
            ]);

        // Group per kategori
        $grouped = $sources
            ->groupBy('category')
            ->map(fn($group, $category) => [
                'category' => $category,
                'count'    => $group->count(),
                'sources'  => $group->values(),
            ])
            ->values();

        return response()->json([
            'total'   => $sources->count(),
            'paid'    => $sources->where('is_paid', true)->count(),
            'organic' => $sources->where('is_paid', false)->count(),
            'sources' => $grouped,
        ]);
    }
}