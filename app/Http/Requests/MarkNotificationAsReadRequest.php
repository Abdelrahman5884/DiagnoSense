<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationAsReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $notification = $this->route('notification');

        return $this->user()->doctor->notifications()->where('id', $notification->id)->exists();
    }
}
