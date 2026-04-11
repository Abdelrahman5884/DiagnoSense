<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Hash;

class RegisterController extends Controller
{
    public function register(RegistrationRequest $request, string $type)
    {
        $validated = $request->validated();
        $validated['type'] = $type;
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        if ($type === 'patient') {
            $user->patient()->create();
        } elseif ($type === 'doctor') {
            $user->doctor()->create();
            $user->is_active = true;
            $user->save();
        }
        $token = $user->createToken('register-token')->plainTextToken;
        $userId = $type === 'doctor' ? $user->doctor->id : $user->patient->id;
        $data = [
            'user' => [
                'id' => $userId,
                'name' => $user->name,
                'email' => $user->email ?? null,
                'phone' => $user->phone ?? null,
                'updated_at' => $user->updated_at->format('Y-m-d h:i:s'),
                'created_at' => $user->created_at->format('Y-m-d h:i:s'),
            ],
            'token' => $token,
        ];
        UserRegistered::dispatch($user);

        return ApiResponse::success('user registered successfully.', $data, 201);

    }
}
