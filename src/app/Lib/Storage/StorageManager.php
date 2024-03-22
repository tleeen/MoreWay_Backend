<?php

namespace App\Lib\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageManager
{
    /**
     * @param string $path
     * @param UploadedFile $uploadedFile
     * @return void
     */
    public function store(string $path, UploadedFile $uploadedFile): void
    {
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
        Storage::put($path, $uploadedFile);
    }
}
