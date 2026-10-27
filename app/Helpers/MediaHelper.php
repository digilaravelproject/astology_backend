<?php

namespace App\Helpers;

class MediaHelper
{
    public static function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's a full URL, extract the path after /storage/
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($path, PHP_URL_PATH);
            if ($parsed) {
                $path = ltrim($parsed, '/');
            }
        }

        $path = ltrim($path, '/');

        $path = preg_replace('#^storage/#', '', $path);

        $path = preg_replace('#^public/#', '', $path);

        return $path;
    }

    public static function getFullUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            if (str_contains($path, 'localhost') || str_contains($path, '127.0.0.1')) {
                $parsed = parse_url($path, PHP_URL_PATH);
                $path = $parsed ? ltrim($parsed, '/') : $path;
            } else {
                // Ensure HTTPS for mobile clients (Android blocks cleartext HTTP)
                if (str_starts_with($path, 'http://')) {
                    $path = 'https://' . substr($path, 7);
                }
                return $path;
            }
        }

        $path = ltrim($path, '/');

        $path = preg_replace('#^storage/#', '', $path);

        $path = preg_replace('#^public/#', '', $path);

        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '//')) {
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            if (!app()->runningInConsole() && request()) {
                $reqHost = request()->getHttpHost();
                if ($reqHost && !str_contains($reqHost, 'localhost') && !str_contains($reqHost, '127.0.0.1')) {
                    $appUrl = request()->schemeAndHttpHost();
                }
            }
            $url = $appUrl . '/' . ltrim($url, '/');
        } elseif ($url && (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1'))) {
            if (!app()->runningInConsole() && request()) {
                $reqHost = request()->getHttpHost();
                if ($reqHost && !str_contains($reqHost, 'localhost') && !str_contains($reqHost, '127.0.0.1')) {
                    $parsed = parse_url($url, PHP_URL_PATH);
                    $url = request()->schemeAndHttpHost() . ($parsed ? '/' . ltrim($parsed, '/') : '');
                }
            }
        }

        // Always enforce HTTPS for non-local URLs so mobile apps don't drop image rendering
        if ($url && str_starts_with($url, 'http://') && !str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1')) {
            $url = 'https://' . substr($url, 7);
        }

        return $url;
    }

    /**
     * Process and store a chat attachment.
     * If the file is an image (jpg, jpeg, png, bmp, webp, etc.), it auto-orients from EXIF,
     * constrains maximum dimension (max 1600px) to prevent mobile OOM, and converts to
     * a lightweight, highly compressed WebP format (80-82% quality).
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int|string $userId
     * @return array ['path' => string, 'url' => string, 'is_image' => bool, 'ext' => string]
     */
    public static function processAndStoreChatAttachment($file, $userId): array
    {
        $origName = strtolower($file->getClientOriginalName() ?? '');
        $clientMime = strtolower($file->getClientMimeType() ?? '');
        $detectedMime = strtolower($file->getMimeType() ?? '');
        $origExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $isImage = str_starts_with($detectedMime, 'image/')
            || str_starts_with($clientMime, 'image/')
            || in_array($origExt, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'bmp'])
            || str_contains($origName, '.jpg')
            || str_contains($origName, '.jpeg')
            || str_contains($origName, '.png')
            || str_contains($origName, '.webp');

        $storedPath = null;
        $finalExt = $origExt ?: ($isImage ? 'jpg' : 'bin');

        if ($isImage && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            try {
                $rawContent = file_get_contents($file->getRealPath());
                if (!empty($rawContent)) {
                    $img = @imagecreatefromstring($rawContent);
                    if ($img !== false) {
                        // Fix EXIF orientation if available
                        if (function_exists('exif_read_data')) {
                            $exif = @exif_read_data($file->getRealPath());
                            if (!empty($exif['Orientation'])) {
                                switch ((int) $exif['Orientation']) {
                                    case 3:
                                        $rotated = imagerotate($img, 180, 0);
                                        if ($rotated !== false) {
                                            imagedestroy($img);
                                            $img = $rotated;
                                        }
                                        break;
                                    case 6:
                                        $rotated = imagerotate($img, -90, 0);
                                        if ($rotated !== false) {
                                            imagedestroy($img);
                                            $img = $rotated;
                                        }
                                        break;
                                    case 8:
                                        $rotated = imagerotate($img, 90, 0);
                                        if ($rotated !== false) {
                                            imagedestroy($img);
                                            $img = $rotated;
                                        }
                                        break;
                                }
                            }
                        }

                        // Resize if exceeds max dimension (1600px)
                        $w = imagesx($img);
                        $h = imagesy($img);
                        $maxDim = 1600;
                        if ($w > $maxDim || $h > $maxDim) {
                            if ($w >= $h) {
                                $newW = $maxDim;
                                $newH = (int) round(($h / $w) * $maxDim);
                            } else {
                                $newH = $maxDim;
                                $newW = (int) round(($w / $h) * $maxDim);
                            }
                            $resized = imagecreatetruecolor($newW, $newH);
                            imagealphablending($resized, false);
                            imagesavealpha($resized, true);
                            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                            imagedestroy($img);
                            $img = $resized;
                        }

                        // Output to WebP buffer
                        ob_start();
                        imagewebp($img, null, 82);
                        $webpBuffer = ob_get_clean();
                        imagedestroy($img);

                        if (!empty($webpBuffer)) {
                            $filename = \Illuminate\Support\Str::random(40) . '.webp';
                            $targetPath = "chat-attachments/{$userId}/{$filename}";
                            \Illuminate\Support\Facades\Storage::disk('public')->put($targetPath, $webpBuffer);
                            $storedPath = $targetPath;
                            $finalExt = 'webp';
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[MediaHelper] WebP conversion fallback: ' . $e->getMessage());
            }
        }

        // Fallback if not an image or if WebP conversion was not possible
        if (!$storedPath) {
            if ($isImage && !in_array($finalExt, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'bmp'])) {
                $finalExt = 'jpg';
            }
            $filename = \Illuminate\Support\Str::random(40) . '.' . $finalExt;
            $storedPath = $file->storeAs("chat-attachments/{$userId}", $filename, 'public');
        }

        $fullUrl = self::getFullUrl($storedPath);

        return [
            'path'     => $storedPath,
            'url'      => $fullUrl,
            'is_image' => $isImage,
            'ext'      => $finalExt,
        ];
    }
}
