<?php

namespace App\Actions\Subscription;

use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GetAvailablePlansAction
{
    public function execute(): AnonymousResourceCollection
    {
        $plans = Plan::all();

        return PlanResource::collection($plans);
    }
}
