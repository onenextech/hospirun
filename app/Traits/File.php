<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

trait File
{
    public function putImage($path, $image, $format = 'webp', $resizableWidth = 800): string
    {
        $image = Image::make($image)->resize($resizableWidth, null, function ($constraint) {
            $constraint->aspectRatio();
        })->encode($format);

        $filename = Str::random(40) . ".$format";
        $fileNameWithPath = "$path/$filename";
        $purifiedFileNameWithPath = Str::replace('//', '/', $fileNameWithPath);

        Storage::put($purifiedFileNameWithPath, $image, [
            'Visibility' => 'public',
            'CacheControl' => config('filesystems.image-cache-control')
        ]);
        return $purifiedFileNameWithPath;
    }

    public function putFile($path, $file, $format): string
    {
        $filename = Str::random(40). ".$format";
        $path = "$path/";
        $purifiedPath = Str::replace('//', '/', $path);

        Storage::putFileAs($purifiedPath, $file, $filename, [
            'Visibility' => 'public',
            'CacheControl' => config('filesystems.image-cache-control')
        ]);
        return $purifiedPath.$filename;
    }

    public function deleteFile($filesOrFile) {
        if ($filesOrFile instanceof  Collection) {
            $filesOrFile->filter();
            Storage::delete($filesOrFile->toArray());
        }
        return Storage::delete($filesOrFile);
    }

    public function getURL($path): string
    {
        return Storage::url($path);
    }

    public function getURLWithoutStoragePath($path) {
        return Str::replace(Storage::url(''), '', $path);
    }
}
