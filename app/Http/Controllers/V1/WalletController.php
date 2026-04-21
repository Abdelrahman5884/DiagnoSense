<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\ChargeWalletRequest;
use App\Http\Resources\TransactionResource;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class WalletController extends Controller
{
    public function index()
    {
        $currentDoctor = auth()->user()->doctor;
        $transactions = $currentDoctor->transactions()->latest()->get();
        $credits = $currentDoctor->wallet->balance;
        $data = [
            'credits' => (float) $credits ?? 0,
            'transactions' => TransactionResource::collection($transactions),
        ];

        return ApiResponse::success(message: 'Wallet transactions retrieved successfully', data: $data, statusCode: 200);
    }

    public function store(ChargeWalletRequest $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => 'Wallet Charge',
                    ],
                    'unit_amount' => $request->balance * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'client_reference_id' => auth()->user()->doctor->id,
            'metadata' => [
                'doctor_id' => auth()->user()->doctor->id,
                'amount' => $request->balance,
            ],
            'success_url' => 'http://localhost:5173/subscription?status=success',
            'cancel_url' => 'http://localhost:5173/subscription?status=cancel',
        ]);

        return response()->json([
            'success' => true,
            'checkout_url' => $session->url,
        ]);
    }
}
