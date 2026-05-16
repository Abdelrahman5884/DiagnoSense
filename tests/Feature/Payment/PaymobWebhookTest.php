<?php

beforeEach(function () {
    $this->doctor = createUserWithType('doctor', fake()->unique()->safeEmail())->doctor;
});
function fakePaymobObj(int $doctorId): array
{
    return [
        'amount_cents' => 10000,
        'created_at' => '2024-01-01T00:00:00',
        'currency' => 'EGP',
        'error_occured' => false,
        'has_parent_transaction' => false,
        'id' => 123456,
        'integration_id' => 12345,
        'is_3d_secure' => false,
        'is_auth' => false,
        'is_capture' => false,
        'is_refunded' => false,
        'is_standalone_payment' => true,
        'is_voided' => false,
        'order' => [
            'id' => 98765,
            'merchant_order_id' => $doctorId.'-'.time(),
        ],
        'owner' => 111,
        'pending' => false,
        'source_data' => [
            'pan' => '2346',
            'sub_type' => 'MasterCard',
            'type' => 'card',
        ],
        'success' => true,
    ];
}
function generatePaymobHmac(array $obj): string
{
    $boolToString = function (bool $value) {
        if (is_string($value)) {
            return $value;
        }

        return $value ? 'true' : 'false';
    };

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

    return hash_hmac('sha512', $string, config('services.paymob.hmac_secret'));
}

it('valid webhook records transaction and updates wallet', function () {
    $obj = fakePaymobObj($this->doctor->id);
    $hmac = generatePaymobHmac($obj);
    $url = route('paymob.webhook').'?hmac='.$hmac;
    $response = $this->postJson($url, ['obj' => $obj]);
    $response->assertStatus(200);
    $this->assertDatabaseHas('transactions', [
        'doctor_id' => $this->doctor->id,
        'amount' => 100,
        'status' => 'completed',
    ]);
    $this->assertDatabaseHas('wallets', [
        'doctor_id' => $this->doctor->id,
        'balance' => 100,
    ]);
});

it('falis webhook if hmac is missing', function () {
    $obj = fakePaymobObj($this->doctor->id);
    $response = $this->postJson(route('paymob.webhook'), ['obj' => $obj]);
    $response->assertStatus(400);
});

it('fails webhook if hmac is invalid', function () {
    $obj = fakePaymobObj($this->doctor->id);
    $hmac = generatePaymobHmac($obj);
    $url = route('paymob.webhook').'?hmac='.$hmac.'invalid';
    $response = $this->postJson($url, ['obj' => $obj]);
    $response->assertStatus(401);
});
