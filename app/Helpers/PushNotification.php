<?php
namespace App\Helpers;

use App\Models\Patient;
use App\Notifications\PatientNotification;

class PushNotification
{
    public static function sendToPatient(Patient $patient, string $type, string $title, string $body): void
    {
        $user = $patient->relationLoaded('user') ? $patient->user : $patient->user()->first();

        if ($user && !empty($user->fcm_token)) {
            $user->notify(new PatientNotification($type, $title, $body));
        }
    }
}
