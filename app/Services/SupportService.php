<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportService
{
    public function createTicket(array $data, User $user): void
    {
        $doctor = $user->doctor;

        $attachmentPath = null;

        if (isset($data['attachment'])) {
            $file = $data['attachment'];

            $uniqueName = Str::uuid().'.'.$file->getClientOriginalExtension();

            try {
                $attachmentPath = Storage::disk('azure')
                    ->putFileAs('support-attachments', $file, $uniqueName);

            } catch (\Exception $e) {

                Log::warning('Azure upload failed, fallback to public disk', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);

                $attachmentPath = Storage::disk('public')
                    ->putFileAs('support-attachments', $file, $uniqueName);
            }
        }

        SupportTicket::create([
            'doctor_id' => $doctor->id,
            'name' => $data['name'] ?? $user->name,
            'contact' => $user->contact,
            'category' => $data['category'],
            'urgency' => $data['urgency'],
            'message' => $data['message'],
            'attachment_path' => $attachmentPath,
        ]);
    }
}
