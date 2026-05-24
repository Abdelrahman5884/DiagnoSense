<?php

namespace App\Actions;

use App\Models\User;

final class UpdatePatientProfileAction
{
    public function execute(User $user, array $data): User
    {
        $user->update([
            'contact' => $data['contact'] ?? $user->contact,
        ]);

        return $user;
    }
}
