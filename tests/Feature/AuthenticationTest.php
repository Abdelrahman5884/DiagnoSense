<?php

beforeEach(function () {
    $doctorWithEmail = createUserWithType('doctor', 'testDoctor@gmail.com');
    $patientWithEmail = createUserWithType('patient', 'testPatient@gmail.com');
    $doctorWithPhone = createUserWithType('doctor', '01012345678');
    $patientWithPhone = createUserWithType('patient', '01012345679');

    $this->validData = [
        'doctor' => [
            'email' => [
                'contact' => $doctorWithEmail->contact,
                'password' => 'password',
            ],
            'phone' => [
                'contact' => $doctorWithPhone->contact,
                'password' => 'password',
            ]
        ],
        'patient' => [
            'email' => [
                'contact' => $patientWithEmail->contact,
                'password' => 'password',
            ],
            'phone' => [
                'contact' => $patientWithPhone->contact,
                'password' => 'password',
            ]
        ]
    ];
});

dataset('user_types', ['doctor', 'patient']);

dataset('invalid_credentials', [
    "email that doesn't exist" => [['contact' => 'nonExist@gmail.com']],
    "phone that doesn't exist" => [['contact' => '01012345677']],
    'wrong password' => [['password' => 'wrongPassword']],
]);

dataset('invalid_data', [
    'empty contact' => [['contact' => null] , ['contact' => ['The contact field is required.']]],
    'empty password' => [['password' => null], ['password' => ['The password field is required.']]],
]);

it('allow user to login', function (string $userType) {
    $dataSet = getDataSets($userType, $this);
    foreach ($dataSet as $data) {
        $response = $this->postJson('/api/v1/login/'. $userType, $data);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
            ]
        ]);
    }
})->with('user_types');


it('fails login user with invalid credentials', function (string $userType, array $invalidCredentials) {
    $dataSet = getDataSets($userType, $this);
    foreach ($dataSet as $data) {
        $response = $this->postJson('/api/v1/login/'. $userType, array_merge($data, $invalidCredentials));
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid credentials'
        ]);
    }
})->with('user_types', 'invalid_credentials');

it('fails login user with invalid data', function (string $userType, array $invalidData, array $expectedErrors) {
    $dataSet = getDataSets($userType, $this);
    foreach ($dataSet as $data) {
        $response = $this->postJson('/api/v1/login/'. $userType, array_merge($data, $invalidData));
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation Errors',
            'data' => $expectedErrors
        ]);
    }
})->with('user_types', 'invalid_data');

it('allow user to logout', function (string $userType) {
    $dataSet = getDataSets($userType, $this);
    foreach ($dataSet as $data) {
        $response = $this->postJson('/api/v1/login/'.$userType, $data);
        $token = $response->json('data.token');
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout/'.$userType)
            ->assertStatus(200);

        auth()->forgetGuards();

        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout/'.$userType);
        $response2->assertStatus(401);
    }
})->with('user_types');;

