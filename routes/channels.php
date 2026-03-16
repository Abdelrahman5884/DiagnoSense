<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chatbot-answer.{doctorId}', function ($user, $doctorId) {
    return $user->doctor_id === (int) $doctorId;
});
