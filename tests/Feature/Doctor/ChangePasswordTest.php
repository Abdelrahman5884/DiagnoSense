<?php

use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    $this->user = createUserWithType('doctor', 'menna@diagno.com');
    $this->user->update([
        'password' => Hash::make('Old_Password_123'),
    ]);

    $this->doctor = $this->user->doctor;
    actingAs($this->user);
});

describe('Doctor Change Password', function () {
    it('successfully changes password and invalidates tokens when data is valid', function () {
        $response = patchJson(route('doctor.password.update'), [
            'current_password' => 'Old_Password_123',
            'new_password' => 'New_Strong_Pass_456',
            'new_password_confirmation' => 'New_Strong_Pass_456',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);

        expect(Hash::check('New_Strong_Pass_456', $this->user->refresh()->password))->toBeTrue();

        expect($this->user->tokens()->count())->toBe(0);
    });
});

describe('Validation Errors', function () {

    it('fails password change with invalid data', function (array $payload, array $expectedErrors) {
        $response = patchJson(route('doctor.password.update'), $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Errors',
                'data' => $expectedErrors,
            ]);

        expect(Hash::check('Old_Password_123', $this->user->refresh()->password))->toBeTrue();

    })->with([
        'incorrect current password' => [
            ['current_password' => 'Wrong_Pass', 'new_password' => 'NewPass123', 'new_password_confirmation' => 'NewPass123'],
            ['current_password' => ['The current password is incorrect.']],
        ],
        'password confirmation mismatch' => [
            ['current_password' => 'Old_Password_123', 'new_password' => 'NewPass123', 'new_password_confirmation' => 'DifferentPass'],
            ['new_password' => ['The new password field confirmation does not match.']],
        ],
        'password too short' => [
            ['current_password' => 'Old_Password_123', 'new_password' => 'short', 'new_password_confirmation' => 'short'],
            ['new_password' => ['The new password field must be at least 8 characters.']],
        ],
    ]);
});
