<?php

namespace App\Console\Commands;

use App\Models\AnalysisSession;
use App\Models\AttritionAnalysis;
use App\Models\Enrollment;
use App\Models\FunnelEntry;
use App\Models\FunnelStage;
use App\Models\Insight;
use App\Models\RetentionAnalysis;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CalculateAnalytics extends Command
{
    protected $signature   = 'analytics:calculate';
    protected $description = 'Hitung semua analytics berdasarkan data enrollment terbaru';

    public function handle(): void
    {
        $this->info('Memulai kalkulasi analytics...');

        $sessions = AnalysisSession::all();

        foreach ($sessions as $session) {
            $this->info("Memproses sesi: {$session->periode_name}");

            // Ambil student_id yang masuk di periode ini (dari stage pertama)
            $firstStage = FunnelStage::orderBy('order')->first();
            $studentIds = Enrollment::where('stage_id', $firstStage->id)
                ->whereBetween('enrolled_date', [
                    $session->start_date,
                    $session->end_date,
                ])
                ->pluck('student_id');

            if ($studentIds->isEmpty()) {
                $this->warn("  ! Tidak ada mahasiswa di periode ini, skip.");
                continue;
            }

            $this->info("  → {$studentIds->count()} mahasiswa ditemukan di periode ini");

            $this->calculateFunnel($session, $studentIds);
            $this->calculateAttrition($session, $studentIds);
            $this->calculateRetention($session, $studentIds);
            $this->generateInsights($session);
        }

        $this->info('Kalkulasi analytics selesai!');
    }

    // =========================================================
    // 1. FUNNEL ENTRIES
    // =========================================================
    private function calculateFunnel(AnalysisSession $session, Collection $studentIds): void
    {
        $stages = FunnelStage::orderBy('order')->get();

        foreach ($stages as $stage) {
            $totalProspects = Enrollment::where('stage_id', $stage->id)
                ->whereIn('student_id', $studentIds)
                ->count();

            $totalPassed = Enrollment::where('stage_id', $stage->id)
                ->whereIn('student_id', $studentIds)
                ->where('status', 'passed')
                ->count();

            $totalDropped = Enrollment::where('stage_id', $stage->id)
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', ['failed', 'dropout'])
                ->count();

            $conversionRate = $totalProspects > 0
                ? round(($totalPassed / $totalProspects) * 100, 2)
                : 0;

            $dropoffRate = $totalProspects > 0
                ? round(($totalDropped / $totalProspects) * 100, 2)
                : 0;

            FunnelEntry::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'stage_id'   => $stage->id,
                ],
                [
                    'total_prospects' => $totalProspects,
                    'conversion_rate' => $conversionRate,
                    'dropoff_rate'    => $dropoffRate,
                ]
            );
        }

        $this->line("  ✓ Funnel entries dihitung");
    }

    // =========================================================
    // 2. ATTRITION ANALYSIS
    // =========================================================
    private function calculateAttrition(AnalysisSession $session, Collection $studentIds): void
    {
        $stages = FunnelStage::orderBy('order')->get();

        foreach ($stages as $stage) {
            $total = Enrollment::where('stage_id', $stage->id)
                ->whereIn('student_id', $studentIds)
                ->count();

            $dropped = Enrollment::where('stage_id', $stage->id)
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', ['failed', 'dropout'])
                ->count();

            if ($total === 0) continue;

            $attritionRate = round(($dropped / $total) * 100, 2);

            $riskLevel = match(true) {
                $attritionRate >= 30 => 'critical',
                $attritionRate >= 20 => 'high',
                $attritionRate >= 10 => 'medium',
                default              => 'low',
            };

            AttritionAnalysis::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'stage_id'   => $stage->id,
                ],
                [
                    'attrition_rate' => $attritionRate,
                    'risk_level'     => $riskLevel,
                    'dropoff_reason' => $this->getDominantReason($studentIds, $stage->id),
                ]
            );
        }

        $this->line("  ✓ Attrition analysis dihitung");
    }

    // =========================================================
    // 3. RETENTION ANALYSIS
    // =========================================================
    private function calculateRetention(AnalysisSession $session, Collection $studentIds): void
    {
        if ($studentIds->isEmpty()) return;

        $totalStudents = Student::whereIn('id', $studentIds)->count();

        $activeStudents = Student::whereIn('id', $studentIds)
            ->where('status', 'active')
            ->count();

        $inactiveStudents = Student::whereIn('id', $studentIds)
            ->whereIn('status', ['inactive', 'dropout'])
            ->count();

        $retentionRate = round(($activeStudents / $totalStudents) * 100, 2);

        RetentionAnalysis::updateOrCreate(
            ['session_id' => $session->id],
            [
                'retention_rate'    => $retentionRate,
                'active_students'   => $activeStudents,
                'inactive_students' => $inactiveStudents,
            ]
        );

        $this->line("  ✓ Retention analysis dihitung");
    }

    // =========================================================
    // 4. INSIGHTS
    // =========================================================
    private function generateInsights(AnalysisSession $session): void
    {
        Insight::where('session_id', $session->id)->delete();

        // Insight funnel — stage dengan dropoff tertinggi
        $worstFunnel = FunnelEntry::where('session_id', $session->id)
            ->where('total_prospects', '>', 0)
            ->orderByDesc('dropoff_rate')
            ->with('stage')
            ->first();

        if ($worstFunnel && $worstFunnel->dropoff_rate > 0) {
            $stageName = $worstFunnel->stage->name ?? 'Unknown';
            Insight::create([
                'session_id'     => $session->id,
                'insight_type'   => 'funnel',
                'description'    => "Tahap '{$stageName}' memiliki dropoff rate tertinggi sebesar {$worstFunnel->dropoff_rate}%.",
                'recommendation' => "Evaluasi proses dan persyaratan di tahap '{$stageName}' untuk mengurangi angka gugur.",
            ]);
        }

        // Insight attrition — stage dengan risk level critical/high
        $criticalStages = AttritionAnalysis::where('session_id', $session->id)
            ->whereIn('risk_level', ['critical', 'high'])
            ->with('stage')
            ->get();

        foreach ($criticalStages as $attrition) {
            $stageName = $attrition->stage->name ?? 'Unknown';
            Insight::create([
                'session_id'     => $session->id,
                'insight_type'   => 'attrition',
                'description'    => "Tahap '{$stageName}' memiliki risk level {$attrition->risk_level} dengan attrition rate {$attrition->attrition_rate}%.",
                'recommendation' => "Perlu perhatian khusus di tahap '{$stageName}' — pertimbangkan untuk menambah sesi konsultasi atau mempermudah persyaratan.",
            ]);
        }

        // Insight retention
        $retention = RetentionAnalysis::where('session_id', $session->id)->first();
        if ($retention && $retention->retention_rate < 70) {
            Insight::create([
                'session_id'     => $session->id,
                'insight_type'   => 'retention',
                'description'    => "Retention rate periode ini hanya {$retention->retention_rate}% — di bawah target 70%.",
                'recommendation' => "Tingkatkan program mentoring dan pendampingan akademik untuk mahasiswa yang berisiko tidak aktif.",
            ]);
        }

        $this->line("  ✓ Insights digenerate");
    }

    // =========================================================
    // HELPER
    // =========================================================
    private function getDominantReason(Collection $studentIds, int $stageId): ?string
    {
        $dominant = Enrollment::where('stage_id', $stageId)
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['failed', 'dropout'])
            ->whereNotNull('dropoff_reason_id')
            ->with('dropoffReason')
            ->get()
            ->groupBy('dropoff_reason_id')
            ->sortByDesc(fn($group) => $group->count())
            ->first();

        return $dominant?->first()?->dropoffReason?->label;
    }
}