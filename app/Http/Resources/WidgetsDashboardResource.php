<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WidgetsDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'doctor_name' => $this['doctor_name'],
            'widgets' => [
                'total_patients'   => number_format($this['total_patients']),
                'today_appointments' => $this['today_appointments'],
                'reports_analyzed' => number_format($this['reports_analyzed']),
                'monthly_growth'   => [
                    'details' => [
                        'last_month'  => $this['last_month_count'],
                        'this_month'  => $this['this_month_count'],
                        'difference'  => ($this['diff'] >= 0 ? '+' : '') . $this['diff'],
                        'growth_rate' => $this['growth_percentage'] . '%',
                        'trend'       => $this['growth_percentage'] >= 0 ? 'up' : 'down',
                    ],
                ],
            ],
        ];
    }
}