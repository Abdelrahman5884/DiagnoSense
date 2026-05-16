<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class PaymobService
{
    public function createIntention(User $user, array $data): array
    {
        $headers = [
            'Authorization' => 'Token '.config('services.paymob.secret_key'),
            'content-type' => 'application/json',
        ];

        $reference = $user->doctor->id.'-'.time();

        $body = $this->preparePaymentData($user, $data['balance'], $reference);

        return Http::withHeaders($headers)->post(config('services.paymob.base_url').'v1/intention/', $body)->throw()->json();
    }

    private function preparePaymentData(User $user, float|int $balance, string $reference): array
    {
        return [
            'amount' => $balance * 100,
            'currency' => 'EGP',
            'payment_methods' => config('services.paymob.integration_ids'),
            'billing_data' => [
                'first_name' => 'DR.',
                'last_name' => $user->name,
                'phone_number' => filter_var($user->contact,
                    FILTER_VALIDATE_EMAIL) ? 'N/A' : $user->contact,
                'email' => filter_var($user->contact, FILTER_VALIDATE_EMAIL) ? $user->contact : 'N/A',
            ],
            'special_reference' => $reference,
            'notification_url' => config('services.paymob.notification_url'),
            'redirection_url' => url('/api/payment-redirect'),
        ];
    }
}
