<?php
namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatusResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $distribution = $this->resource;
        $totalPatients = (int) $distribution->sum();

        $pieChartData = collect(Patient::getStatuses())->map(function ($status) use ($distribution, $totalPatients) {
            $count = (int) ($distribution[$status] ?? 0);

            return [
                'status' => $status,
                'value' => $count,
                'percentage' => $totalPatients > 0 ? round(($count / $totalPatients) * 100) : 0,
            ];
        })->values()->all();

        return [
            'total_registered_patients' => $totalPatients,
            'pie_chart_data' => $pieChartData,
        ];
    }
}
