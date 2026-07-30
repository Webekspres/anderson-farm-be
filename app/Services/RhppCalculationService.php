<?php

namespace App\Services;

use App\Models\ProductionPeriod;

class RhppCalculationService
{
    /**
     * Calculate RHPP metrics for a production period.
     *
     * @return array Structured calculation data
     */
    public function calculateMetrics(ProductionPeriod $period): array
    {
        // Load all required relations
        $period->load([
            'floor.coop',
            'dailyActivityHeaders.harvests',
            'dailyActivityHeaders.ovkUsages.ovkItem',
            'salaries',
        ]);

        // Calculate Gross Revenue
        $grossRevenue = $this->calculateGrossRevenue($period);

        // Calculate Total Cost
        $totalCost = $this->calculateTotalCost($period);

        // Calculate Net Profit
        $netProfit = $grossRevenue - $totalCost;

        // Calculate FCR (Feed Conversion Ratio)
        $feedConsumption = $this->calculateFeedConsumption($period);
        $totalHarvestedWeight = $this->calculateTotalHarvestedWeight($period);
        $fcr = $this->calculateFcr($feedConsumption, $totalHarvestedWeight);

        // Calculate IP (Index Performance)
        $ip = $this->calculateIndexPerformance($period, $fcr, $totalHarvestedWeight);

        return [
            'gross_revenue' => round($grossRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'net_profit' => round($netProfit, 2),
            'feed_consumption' => round($feedConsumption, 2),
            'total_harvested_weight' => round($totalHarvestedWeight, 2),
            'fcr' => round($fcr, 4),
            'ip' => round($ip, 2),
            'profitability_margin' => $grossRevenue > 0 ? round(($netProfit / $grossRevenue) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate gross revenue from harvests.
     */
    private function calculateGrossRevenue(ProductionPeriod $period): float
    {
        $revenue = 0;

        foreach ($period->dailyActivityHeaders as $header) {
            foreach ($header->harvests as $harvest) {
                $revenue += $harvest->total_weight * $harvest->price_per_kg;
            }
        }

        return $revenue;
    }

    /**
     * Calculate total production costs.
     */
    private function calculateTotalCost(ProductionPeriod $period): float
    {
        $cost = 0;

        // Initial DOC (Day-Old-Chick) cost
        $cost += $period->initial_doc_cost ?? 0;

        // OVK (Obat, Vaksin, Kimia) usage costs
        foreach ($period->dailyActivityHeaders as $header) {
            foreach ($header->ovkUsages as $usage) {
                $cost += ($usage->quantity_used ?? 0) * ($usage->ovkItem?->unit_cost ?? 0);
            }
        }

        // Employee salaries
        $salaries = 0;
        foreach ($period->salaries as $salary) {
            $salaries += $salary->salary_amount ?? 0;
        }
        $cost += $salaries;

        return $cost;
    }

    /**
     * Calculate total feed consumption in kilograms from daily activity headers.
     */
    private function calculateFeedConsumption(ProductionPeriod $period): float
    {
        return (float) $period->dailyActivityHeaders->sum('feed_consumption_kg');
    }

    /**
     * Calculate total harvested weight in kilograms.
     */
    private function calculateTotalHarvestedWeight(ProductionPeriod $period): float
    {
        $totalWeight = 0;

        foreach ($period->dailyActivityHeaders as $header) {
            foreach ($header->harvests as $harvest) {
                $totalWeight += $harvest->total_weight ?? 0;
            }
        }

        return $totalWeight;
    }

    /**
     * Calculate FCR (Feed Conversion Ratio).
     * FCR = Total Feed Consumed (kg) / Total Bird Weight Harvested (kg)
     * Handles division by zero by returning 0.
     */
    private function calculateFcr(float $feedConsumption, float $harvestedWeight): float
    {
        if ($harvestedWeight === 0 || $harvestedWeight < 0.001) {
            return 0;
        }

        return $feedConsumption / $harvestedWeight;
    }

    /**
     * Calculate IP (Index Performance) aligned with MonitoringService.
     * IP = ((100 - deplesi%) * avg_bw_gram) / (FCR * age_days * 10)
     */
    private function calculateIndexPerformance(ProductionPeriod $period, float $fcr, float $totalHarvestedWeight): float
    {
        $initialStock = (float) ($period->initial_stock ?? 0);
        if ($initialStock < 0.001 || $fcr < 0.001) {
            return 0;
        }

        $totalMortality = (float) $period->dailyActivityHeaders->sum('mortality_count');
        $totalCull = (float) $period->dailyActivityHeaders->sum('cull_count');
        $deplesi = (($totalMortality + $totalCull) / $initialStock) * 100;

        $avgBw = $period->dailyActivityHeaders
            ->whereNotNull('average_weight')
            ->last()?->average_weight;

        if ($avgBw === null) {
            return 0;
        }

        $ageInDays = $period->start_date
            ? max(1, (int) $period->start_date->diffInDays(now()))
            : 35;

        return round(((100 - $deplesi) * (float) $avgBw) / ($fcr * $ageInDays * 10), 1);
    }
}
