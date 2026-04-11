<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.Doctor.{doctorId}', function ($user, $doctorId) {
    return $user->doctor && (int) $user->doctor->id === (int) $doctorId;
});

Broadcast::channel('chatbot-answer.{doctorId}', function ($user, $doctorId) {
    return $user->doctor_id === (int) $doctorId;
});
