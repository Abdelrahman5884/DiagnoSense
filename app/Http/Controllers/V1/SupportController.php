<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreSupportRequest;
use App\Http\Helpers\ApiResponse;
use App\Models\SupportTeam;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    public function store(StoreSupportRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = auth()->user();
            $doctor = $user->doctor;
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $uniqueName = time().'_'.Str::random(5).'.'.$file->getClientOriginalExtension();
                $attachmentPath = Storage::disk('azure')->putFileAs('support-attachments', $file, $uniqueName);
            }

            $message = SupportTeam::create([
                'doctor_id' => $doctor->id,
                'name' => $validated['name'] ?? $user->name,
                'identity' => $user->email ?? $user->phone,
                'category' => $validated['category'],
                'urgency' => $validated['urgency'],
                'message' => $validated['message'],
                'attachment_path' => $attachmentPath,
            ]);

            return ApiResponse::success('Support message submitted successfully we will get back to you shortly.', null, 201);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to submit message: '.$e->getMessage(), null, 500);
        }
    }
}
