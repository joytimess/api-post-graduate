<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalysisSession;
use App\Models\Insight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    // GET /api/insights?session_id=1
    // Semua insight per sesi
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
        ]);

        $session  = AnalysisSession::findOrFail($request->session_id);
        $insights = Insight::where('session_id', $session->id)
            ->orderBy('insight_type')
            ->get();

        if ($insights->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada insight untuk sesi ini.'
            ], 404);
        }

        // Group per tipe
        $grouped = $insights
            ->groupBy('insight_type')
            ->map(fn($group, $type) => [
                'type'  => $type,
                'count' => $group->count(),
                'items' => $group->map(fn($i) => [
                    'id'             => $i->id,
                    'description'    => $i->description,
                    'recommendation' => $i->recommendation,
                    'created_at'     => $i->created_at,
                ]),
            ])
            ->values();

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
                'start_date'   => $session->start_date,
                'end_date'     => $session->end_date,
            ],
            'summary' => [
                'total'     => $insights->count(),
                'funnel'    => $insights->where('insight_type', 'funnel')->count(),
                'attrition' => $insights->where('insight_type', 'attrition')->count(),
                'retention' => $insights->where('insight_type', 'retention')->count(),
                'traffic'   => $insights->where('insight_type', 'traffic')->count(),
            ],
            'insights' => $grouped,
        ]);
    }

    // GET /api/insights/type?session_id=1&type=attrition
    // Insight per tipe tertentu
    public function byType(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:analysis_sessions,id',
            'type'       => 'required|in:funnel,attrition,retention,traffic',
        ]);

        $session  = AnalysisSession::findOrFail($request->session_id);
        $insights = Insight::where('session_id', $session->id)
            ->where('insight_type', $request->type)
            ->get();

        if ($insights->isEmpty()) {
            return response()->json([
                'message' => "Belum ada insight tipe '{$request->type}' untuk sesi ini."
            ], 404);
        }

        return response()->json([
            'session' => [
                'id'           => $session->id,
                'periode_name' => $session->periode_name,
            ],
            'type'     => $request->type,
            'total'    => $insights->count(),
            'insights' => $insights->map(fn($i) => [
                'id'             => $i->id,
                'description'    => $i->description,
                'recommendation' => $i->recommendation,
                'created_at'     => $i->created_at,
            ]),
        ]);
    }

    // GET /api/insights/all
    // Semua insight dari semua sesi — untuk halaman ringkasan
    public function all(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);

        $insights = Insight::with('session')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'summary' => [
                'total'     => Insight::count(),
                'funnel'    => Insight::where('insight_type', 'funnel')->count(),
                'attrition' => Insight::where('insight_type', 'attrition')->count(),
                'retention' => Insight::where('insight_type', 'retention')->count(),
                'traffic'   => Insight::where('insight_type', 'traffic')->count(),
            ],
            'insights' => $insights->map(fn($i) => [
                'id'             => $i->id,
                'session_id'     => $i->session_id,
                'periode_name'   => $i->session->periode_name ?? '-',
                'type'           => $i->insight_type,
                'description'    => $i->description,
                'recommendation' => $i->recommendation,
                'created_at'     => $i->created_at,
            ]),
            'pagination' => [
                'current_page' => $insights->currentPage(),
                'last_page'    => $insights->lastPage(),
                'per_page'     => $insights->perPage(),
                'total'        => $insights->total(),
            ],
        ]);
    }
}