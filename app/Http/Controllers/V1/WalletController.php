<?php

namespace App\Http\Controllers\V1;


use App\Actions\GetTransactionHistoryAction;
use App\Helpers\ApiResponse;
use App\Http\Requests\ChargeWalletRequest;
use App\Services\PaymobService;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __construct(
        public PaymobService  $paymobService,
    ){}
    public function index(GetTransactionHistoryAction $action) : JsonResponse
    {
        $currentDoctor = auth()->user()->doctor;
        $data = $action->execute($currentDoctor);
        return ApiResponse::success(message: 'Wallet transactions retrieved successfully', data: $data);
    }

    public function store(ChargeWalletRequest $request) : JsonResponse
    {
        $currentUser = auth()->user();
        $response = $this->paymobService->createIntention($currentUser,$request->validated());
        $checkoutUrl= config('services.paymob.base_url') . 'unifiedcheckout/?publicKey='. config('services.paymob.public_key') . '&clientSecret='. $response['client_secret'];
        return ApiResponse::success(message: 'Wallet charge initiated successfully', data: ['checkout_url' => $checkoutUrl]);
    }
}
