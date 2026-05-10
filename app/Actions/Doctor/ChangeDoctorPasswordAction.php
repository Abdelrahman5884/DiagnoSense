<?php

namespace App\Actions\Doctor;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChangeDoctorPasswordAction
{
    public function execute(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            $user->update([
                'password' => $newPassword,
            ]);
            $user->tokens()->delete();
        });
    }
}
