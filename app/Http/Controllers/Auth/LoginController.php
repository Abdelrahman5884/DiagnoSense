<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(LoginRequest $request, string $type)
    {
        $validated = $request->validated();
        $fieldType = filter_var($validated['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($fieldType, $validated['identity'])
            ->where('type', $type)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials.', null, 401);
        }

        $token = $user->createToken('login-token')->plainTextToken;
        $userId = $type === "doctor" ? $user->doctor->id : $user->patient->id ;
        $user = [
            'id' => $userId,
            'name' => $user->name,
            'email' => $user->email ?? null,
            'phone' => $user->phone ?? null,
            'updated_at' => $user->updated_at->format('Y-m-d h:i:s'),
            'created_at' => $user->created_at->format('Y-m-d h:i:s'),
        ];
        $data = [
            'user' => $user,
            'token' => $token,
        ];

        return ApiResponse::success('Login successfully.', $data, 200);

    }
}
