<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    protected string $disk = 'public';

    public static function imageRules(int $maxKB = 2048): array
    {
        return ['image', 'mimes:jpeg,png,jpg,gif,webp', "max:{$maxKB}"];
    }

    public static function documentRules(int $maxKB = 5120): array
    {
        return ['file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKB}"];
    }

    public static function videoRules(int $maxKB = 51200): array
    {
        return ['file', 'mimes:mp4,webm,mov,avi', "max:{$maxKB}"];
    }

    public static function attachmentRules(int $maxKB = 10240): array
    {
        return ['file', "max:{$maxKB}"];
    }

    public function store(UploadedFile $file, string $directory, ?string $filename = null): string
    {
        return $filename
            ? $file->storeAs($directory, $filename, $this->disk)
            : $file->store($directory, $this->disk);
    }

    public function delete(string $path): bool
    {
        return $path && Storage::disk($this->disk)->exists($path)
            ? Storage::disk($this->disk)->delete($path)
            : false;
    }

    public function url(string $path): string
    {
        return $path ? Storage::disk($this->disk)->url($path) : '';
    }
}
