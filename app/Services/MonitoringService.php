<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyActivityHeader;
use App\Models\HarvestEntry;
use App\Models\ProductionPeriod;
use Carbon\Carbon;

class MonitoringService
{
    /**
     * Compute KPIs for a given period.
     *
     * @return array{deplesi: float|null, avg_bw: float|null, fcr: float|null, ip: float|null, total_harvest: array, age_days: int|null, daily_summary: array}
     */
    public function computeKpi(string $periodId): array
    {
        $period = ProductionPeriod::findOrFail($periodId);
        $initialStock = (int) ($period->initial_stock ?? 0);

        $activities = DailyActivityHeader::query()
            ->where('period_id', $periodId)
            ->whereNull('deleted_at')
            ->orderBy('date')
            ->get();

        $harvests = HarvestEntry::query()
            ->whereHas('header', fn ($q) => $q->where('period_id', $periodId))
            ->whereNull('deleted_at')
            ->get();

        $totalMortality = $activities->sum('mortality_count');
        $totalCull = $activities->sum('cull_count');
        $totalDead = $totalMortality + $totalCull;
        $totalFeedKg = $activities->sum('feed_consumption_kg');

        // Deplesi %
        $deplesi = $initialStock > 0
            ? round(($totalDead / $initialStock) * 100, 2)
            : null;

        // Average body weight (latest non-null)
        $avgBw = $activities
            ->whereNotNull('average_weight')
            ->last()?->average_weight;

        // Harvest totals
        $totalBirds = $harvests->sum('total_birds');
        $totalWeight = $harvests->sum('total_weight');
        $totalRevenue = $harvests->sum(fn (HarvestEntry $h) => $h->total_weight * $h->price_per_kg);

        // FCR = total feed (kg) / total live weight (kg)
        $liveWeight = $totalWeight > 0 ? $totalWeight : ($avgBw ? ($initialStock - $totalDead) * $avgBw / 1000 : 0);
        $fcr = $liveWeight > 0 && $totalFeedKg > 0
            ? round($totalFeedKg / $liveWeight, 3)
            : null;

        // Age in days
        $ageDays = $period->start_date
            ? (int) Carbon::parse($period->start_date)->diffInDays(now())
            : null;

        // IP = (100 - deplesi%) * avg_bw(gram) / (FCR * age) * 100
        // Standard broiler IP formula
        $ip = null;
        if ($deplesi !== null && $avgBw !== null && $fcr !== null && $fcr > 0 && $ageDays !== null && $ageDays > 0) {
            $ip = round(((100 - $deplesi) * $avgBw) / ($fcr * $ageDays * 10), 1);
        }

        // Daily summary for trend
        $dailySummary = $activities->map(fn (DailyActivityHeader $a) => [
            'date' => $a->date?->toDateString(),
            'mortality_count' => $a->mortality_count,
            'cull_count' => $a->cull_count,
            'feed_consumption_kg' => $a->feed_consumption_kg,
            'average_weight' => $a->average_weight,
        ])->values()->toArray();

        return [
            'deplesi' => $deplesi,
            'avg_bw' => $avgBw,
            'fcr' => $fcr,
            'ip' => $ip,
            'age_days' => $ageDays,
            'total_harvest' => [
                'birds' => $totalBirds,
                'weight_kg' => round($totalWeight, 2),
                'revenue' => round($totalRevenue, 2),
            ],
            'total_mortality' => $totalDead,
            'total_feed_kg' => round($totalFeedKg, 2),
            'initial_stock' => $initialStock,
            'daily_summary' => $dailySummary,
        ];
    }

    /**
     * Compare actuals against ARV standard documents and return deviations.
     *
     * @return array<int, array{metric: string, day: int|null, actual: float|null, standard: float|null, deviation_pct: float|null, severity: string}>
     */
    public function computeDeviations(string $periodId): array
    {
        $kpi = $this->computeKpi($periodId);
        $deviations = [];

        // Simple threshold-based deviation alerts
        if ($kpi['deplesi'] !== null && $kpi['deplesi'] > 5.0) {
            $deviations[] = [
                'metric' => 'deplesi',
                'day' => $kpi['age_days'],
                'actual' => $kpi['deplesi'],
                'standard' => 5.0,
                'deviation_pct' => round($kpi['deplesi'] - 5.0, 2),
                'severity' => $kpi['deplesi'] > 10 ? 'high' : 'medium',
            ];
        }

        if ($kpi['fcr'] !== null && $kpi['fcr'] > 1.8) {
            $deviations[] = [
                'metric' => 'fcr',
                'day' => $kpi['age_days'],
                'actual' => $kpi['fcr'],
                'standard' => 1.8,
                'deviation_pct' => round((($kpi['fcr'] - 1.8) / 1.8) * 100, 2),
                'severity' => $kpi['fcr'] > 2.2 ? 'high' : 'medium',
            ];
        }

        if ($kpi['ip'] !== null && $kpi['ip'] < 300) {
            $deviations[] = [
                'metric' => 'ip',
                'day' => $kpi['age_days'],
                'actual' => $kpi['ip'],
                'standard' => 300.0,
                'deviation_pct' => round(((300 - $kpi['ip']) / 300) * 100, 2),
                'severity' => $kpi['ip'] < 200 ? 'high' : 'medium',
            ];
        }

        // Check daily mortality spikes (> 2% in one day)
        foreach ($kpi['daily_summary'] as $day) {
            $dayMortality = ($day['mortality_count'] ?? 0) + ($day['cull_count'] ?? 0);
            if ($kpi['initial_stock'] > 0 && $dayMortality > 0) {
                $dayPercent = ($dayMortality / $kpi['initial_stock']) * 100;
                if ($dayPercent > 2.0) {
                    $deviations[] = [
                        'metric' => 'daily_mortality_spike',
                        'day' => null,
                        'date' => $day['date'],
                        'actual' => round($dayPercent, 2),
                        'standard' => 2.0,
                        'deviation_pct' => round($dayPercent - 2.0, 2),
                        'severity' => $dayPercent > 5 ? 'high' : 'medium',
                    ];
                }
            }
        }

        return $deviations;
    }
}
