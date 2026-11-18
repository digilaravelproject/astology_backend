<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeStorageImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize {--path=blogs/images : Relative directory inside storage/app/public} {--max-kb=300 : Only optimize images heavier than this threshold}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress heavy storage images in-place without altering filenames or breaking database URLs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $relPath = trim($this->option('path'), '/\\');
        $maxKb = (int) $this->option('max-kb');
        $targetDir = storage_path('app/public/' . $relPath);

        if (!File::isDirectory($targetDir)) {
            $this->error("Directory does not exist: {$targetDir}");
            return Command::FAILURE;
        }

        $this->info("Scanning images in: {$targetDir} (Files > {$maxKb} KB)");

        $files = File::allFiles($targetDir);
        $optimizedCount = 0;
        $savedBytes = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                continue;
            }

            $sizeBytes = $file->getSize();
            $sizeKb = $sizeBytes / 1024;

            if ($sizeKb < $maxKb) {
                continue;
            }

            $filePath = $file->getRealPath();
            $raw = @file_get_contents($filePath);
            if (empty($raw)) {
                continue;
            }

            $img = @imagecreatefromstring($raw);
            if ($img === false) {
                continue;
            }

            $w = imagesx($img);
            $h = imagesy($img);
            $maxDim = 1400;

            if ($w > $maxDim || $h > $maxDim) {
                if ($w >= $h) {
                    $newW = $maxDim;
                    $newH = (int) round(($h / $w) * $maxDim);
                } else {
                    $newH = $maxDim;
                    $newW = (int) round(($w / $h) * $maxDim);
                }
                $resized = imagecreatetruecolor($newW, $newH);
                if ($ext === 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                imagedestroy($img);
                $img = $resized;
            }

            // Save in-place with optimal compression
            ob_start();
            if ($ext === 'png') {
                imagepng($img, null, 8); // Compression level 8
            } elseif ($ext === 'webp') {
                imagewebp($img, null, 82);
            } else {
                imagejpeg($img, null, 82);
            }
            $compressedBuffer = ob_get_clean();
            imagedestroy($img);

            $newSize = strlen($compressedBuffer);
            if ($newSize > 0 && $newSize < $sizeBytes) {
                file_put_contents($filePath, $compressedBuffer);
                $saved = $sizeBytes - $newSize;
                $savedBytes += $saved;
                $optimizedCount++;

                $oldKb = round($sizeKb, 1);
                $afterKb = round($newSize / 1024, 1);
                $this->line("✓ [{$file->getFilename()}]: {$oldKb}KB -> {$afterKb}KB (Saved " . round($saved / 1024, 1) . "KB)");
            }
        }

        $totalSavedMb = round($savedBytes / (1024 * 1024), 2);
        $this->info("\n=======================================================");
        $this->info("Successfully optimized {$optimizedCount} images!");
        $this->info("Total bandwidth & disk space saved: {$totalSavedMb} MB");
        $this->info("=======================================================");

        return Command::SUCCESS;
    }
}
