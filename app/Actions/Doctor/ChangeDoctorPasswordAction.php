<?php

namespace App\Actions\Doctor;

use App\Models\User;

final class ChangeDoctorPasswordAction
{
    public function execute(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);
        $user->tokens()->delete();
    }
}
