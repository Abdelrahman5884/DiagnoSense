<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController
{
    public function handle(Request $request)
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            $event = Webhook::constructEvent($payload, $signature, $endpointSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $doctorId = $session->metadata->doctor_id;
            $amount = $session->metadata->amount;

            $wallet = Wallet::query()->firstOrCreate(['doctor_id' => $doctorId]);
            $wallet->increment('balance', $amount);
            $wallet->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment successful',
                'credits' => $wallet->balance,
            ]);
        }
    }
}
