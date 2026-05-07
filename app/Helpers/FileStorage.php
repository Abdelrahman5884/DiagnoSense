<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class FileStorage
{
    public static function store($file, string $path, string $name): string
    {
        return Storage::disk(config('filesystems.default'))
            ->putFileAs($path, $file, $name);
    }
}
