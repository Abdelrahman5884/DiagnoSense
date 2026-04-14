<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ChangeDoctorPasswordRequest;
use App\Http\Requests\DeleteDoctorAccountRequest;
use App\Http\Requests\GetDoctorInformationRequest;
use App\Http\Requests\UpdateDoctorInformationRequest;
use App\Http\Resources\DoctorResource;
use App\Http\Responses\ApiResponse;

class DoctorController extends Controller
{
    public function edit(GetDoctorInformationRequest $request)
    {
        $user = auth()->user();
        $user['specialization'] = $user->doctor->specialization;

        return ApiResponse::success('Doctor Information', new DoctorResource($user), 200);
    }

    public function update(UpdateDoctorInformationRequest $request)
    {
        $user = auth()->user();
        $user->update([
            'name' => $request->name,
        ]);
        $request->specialization ? $user->doctor()->update([
            'specialization' => $request->specialization,
        ]) : null;

        return ApiResponse::success('Doctor Information Updated Successfully', null, 200);
    }

    public function changePassword(ChangeDoctorPasswordRequest $request)
    {
        auth()->user()->update([
            'password' => $request->new_password,
        ]);

        return ApiResponse::success('Password Changed Successfully', null, 200);
    }

    public function destroy(DeleteDoctorAccountRequest $request)
    {
        $currentUser = auth()->user();
        $currentUser->doctor()->delete();
        $currentUser->tokens()->delete();
        $currentUser->delete();

        return ApiResponse::success('Account Deleted Successfully', null, 200);
    }
}
