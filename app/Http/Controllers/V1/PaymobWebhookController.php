<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\InvalidHmacException;
use App\Exceptions\MissingHmacException;
use App\Models\Transactions;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymobWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $data = $request->all();
        if (! isset($data['obj'])) {
            \Log::error('Paymob Webhook: Invalid Data Structure');

            return response()->json(['error' => 'Invalid data'], 400);
        }
        $obj = $data['obj'];
        $boolToString = function ($value) {
            if (is_string($value)) {
                return $value;
            }

            return $value ? 'true' : 'false';
        };
        $string = $this->createWebhookPayload($obj, $boolToString);
        $this->verifyHmac($request, $string);
        if ($boolToString($obj['success']) === 'true') {
            $orderId = $obj['order']['id'];
            $amount = $obj['amount_cents'] / 100;
            $fullReference = $obj['order']['merchant_order_id'] ?? null;
            if ($fullReference) {
                $doctorId = explode('-', $fullReference)[0];
            } else {
                $doctorId = null;
            }

            if (! $doctorId) {
                \Log::error('Paymob Webhook Error: doctor_id not found in payload');

                return response()->json(['error' => 'Doctor ID missing'], 400);
            }

            return $this->recordPaymentTransaction($doctorId, $amount, $orderId);
        }

        return response()->json(['success' => false]);
    }

    private function createWebhookPayload(mixed $obj, \Closure $boolToString): string
    {
        $string = $obj['amount_cents'].
            $obj['created_at'].
            $obj['currency'].
            $boolToString($obj['error_occured']).
            $boolToString($obj['has_parent_transaction']).
            $obj['id'].
            $obj['integration_id'].
            $boolToString($obj['is_3d_secure']).
            $boolToString($obj['is_auth']).
            $boolToString($obj['is_capture']).
            $boolToString($obj['is_refunded']).
            $boolToString($obj['is_standalone_payment']).
            $boolToString($obj['is_voided']).
            $obj['order']['id'].
            $obj['owner'].
            $boolToString($obj['pending']).
            ($obj['source_data']['pan'] ?? '').
            ($obj['source_data']['sub_type'] ?? '').
            ($obj['source_data']['type'] ?? '').
            $boolToString($obj['success']);

        return $string;
    }

    private function recordPaymentTransaction(string $doctorId, float|int $amount, mixed $orderId): JsonResponse
    {
        return DB::transaction(function () use ($doctorId, $amount, $orderId) {
            $exists = Transactions::where('payment_id', $orderId)->exists();
            if ($exists) {
                return response()->json(['status' => 'already processed']);
            }

            $wallet = $this->addFundsToWallet($doctorId, $amount);

            Transactions::create([
                'amount' => $amount,
                'status' => 'completed',
                'type' => 'charge',
                'sourceable_type' => Wallet::class,
                'sourceable_id' => $wallet->id,
                'description' => 'Wallet charge via Paymob',
                'doctor_id' => $doctorId,
                'payment_id' => $orderId,
            ]);

            return response()->json(['success' => true]);
        });
    }

    private function addFundsToWallet(string $doctorId, float|int $amount): Wallet
    {
        $wallet = Wallet::firstOrCreate(['doctor_id' => $doctorId]);
        $wallet->increment('balance', $amount);

        return $wallet;
    }

    private function verifyHmac(Request $request, string $payload)
    {
        if (! $request->has('hmac')) {
            \Log::error('Paymob Webhook: Missing HMAC');
            throw new MissingHmacException;
        }
        $hmacSecret = config('services.paymob.hmac_secret');
        $calculatedHmac = hash_hmac('sha512', $payload, $hmacSecret);
        $receivedHmac = $request->query('hmac');
        if (! hash_equals($calculatedHmac, $receivedHmac)) {
            \Log::error('Paymob HMAC Mismatch. Calculated: '.$calculatedHmac.' Received: '.$receivedHmac);
            throw new InvalidHmacException;
        }

        return true;
    }
}
