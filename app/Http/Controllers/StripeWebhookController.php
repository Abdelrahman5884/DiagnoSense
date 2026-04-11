<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use App\Models\Wallet;
use App\Notifications\CreditAdded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $wallet = DB::transaction(function () use ($doctorId, $amount, $session) {
                $transactionExists = Transactions::query()->where('payment_id', $session->id)->exists();
                if ($transactionExists) {
                    return response()->json(['message' => 'Already processed']);
                }
                $wallet = Wallet::query()->firstOrCreate(['doctor_id' => $doctorId]);
                $wallet->increment('balance', $amount);
                Transactions::query()->create([
                    'amount' => $amount,
                    'status' => 'completed',
                    'type' => 'charge',
                    'source_type' => Wallet::class,
                    'source_id' => $wallet->id,
                    'payment_id' => $session->id,
                    'description' => 'Wallet charge via Stripe',
                    'doctor_id' => $doctorId,
                ]);

                return $wallet->fresh();
            });
            if ($wallet) {
                $user = $wallet->doctor->user;
                $user->doctor->notify(new CreditAdded($amount, $wallet->balance));
            }

            return response()->json(['success' => true]);
        }
    }
}
