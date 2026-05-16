<?php

namespace App\Actions;

use App\Helpers\FileStorage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Str;

final class SupportTicketAction
{
    public function execute(array $data, User $user): void
    {
        $doctorId = $user->doctor?->id;
        $attachmentPath = null;

        if (isset($data['attachment'])) {
            $file = $data['attachment'];
            $uniqueName = Str::uuid().'.'.$file->getClientOriginalExtension();

            $attachmentPath = FileStorage::store($file, 'support-attachments', $uniqueName);
        }

        SupportTicket::create([
            'doctor_id' => $doctorId,
            'name' => $data['name'] ?? $user->name,
            'contact' => $user->contact,
            'category' => $data['category'],
            'urgency' => $data['urgency'],
            'message' => $data['message'],
            'attachment_path' => $attachmentPath,
        ]);
    }
}
