<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileSystem
{
    public static function storeFile(UploadedFile $file, string $path, string $fileName = '', string $disk = 'azure') : string
    {
        return $fileName ?
            Storage::disk($disk)->putFileAs($path, $file, $fileName)
            : Storage::disk($disk)->putFile($path, $file);
    }

    public static function deleteFile(string $path, string $disk = 'azure') : bool
    {
        return Storage::disk($disk)->delete($path);
    }
    public static function getTempUrl(string $path , string $disk = 'azure') : string
    {
        return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(60));
    }
}
