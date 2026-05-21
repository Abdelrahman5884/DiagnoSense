<?php
namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getPatientStatusDistribution(Doctor $doctor): Collection
    {
        return $doctor->patients()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
    }
}
