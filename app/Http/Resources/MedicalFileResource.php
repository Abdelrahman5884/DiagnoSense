<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicalFileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->file_name,
            'date' => $this->created_at->format('M d, Y'),
            'size' => '2.3 MB',
            'extension' => Str::upper(pathinfo($this->file_name, PATHINFO_EXTENSION)),
            'download_url' => Storage::url($this->file_path),
        ];
    }
}
