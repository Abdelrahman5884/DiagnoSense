<?php

namespace App\Actions;

use App\Http\Resources\TransactionResource;
use App\Models\Doctor;

final class GetTransactionHistoryAction
{
    public function execute(Doctor $doctor): array
    {
        $transactions = $doctor->transactions()->latest()->get();

        return [
            'credits' => (float) ($doctor->wallet?->balance ?? 0),
            'transactions' => TransactionResource::collection($transactions),
        ];
    }
}
