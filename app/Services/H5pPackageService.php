<?php

namespace App\Services;

use App\Models\LearningItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class H5pPackageService
{
    /**
     * File extensions an H5P package may contain; anything else is dropped during extraction.
     *
     * @var list<string>
     */
    private const array ALLOWED_EXTENSIONS = [
        'json', 'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'tif', 'tiff', 'svg', 'webp',
        'eot', 'ttf', 'woff', 'woff2', 'otf', 'webm', 'mp4', 'ogg', 'mp3', 'm4a', 'wav',
        'txt', 'pdf', 'xml', 'csv', 'md', 'vtt', 'webvtt', 'gltf', 'glb',
    ];

    /**
     * Content types served for extracted package files, keyed by extension.
     *
     * @var array<string, string>
     */
    private const array CONTENT_TYPES = [
        'json' => 'application/json', 'js' => 'text/javascript', 'css' => 'text/css',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        'bmp' => 'image/bmp', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'svg' => 'image/svg+xml',
        'webp' => 'image/webp', 'eot' => 'application/vnd.ms-fontobject', 'ttf' => 'font/ttf',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'otf' => 'font/otf', 'webm' => 'video/webm',
        'mp4' => 'video/mp4', 'ogg' => 'audio/ogg', 'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4',
        'wav' => 'audio/wav', 'txt' => 'text/plain', 'pdf' => 'application/pdf', 'xml' => 'application/xml',
        'csv' => 'text/csv', 'md' => 'text/plain', 'vtt' => 'text/vtt', 'webvtt' => 'text/vtt',
        'gltf' => 'model/gltf+json', 'glb' => 'model/gltf-binary',
    ];

    private const int MAX_ENTRIES = 5000;

    private const int MAX_UNCOMPRESSED_BYTES = 524_288_000;

    /**
     * Extract a stored .h5p package into the item's content directory.
     *
     * @throws ValidationException When the package is not a usable H5P package.
     */
    public function extract(LearningItem $item): void
    {
        $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);
        $directory = (string) $item->h5pDirectory();
        $zip = new ZipArchive;

        if ($item->attachment_path === null || $zip->open($disk->path($item->attachment_path)) !== true) {
            throw $this->invalid('Berkas H5P tidak dapat dibuka. Pastikan berkas berformat .h5p yang valid.');
        }

        try {
            if ($zip->locateName('h5p.json') === false || $zip->locateName('content/content.json') === false) {
                throw $this->invalid('Berkas ini bukan paket H5P: h5p.json atau content/content.json tidak ditemukan.');
            }

            if ($zip->numFiles > self::MAX_ENTRIES) {
                throw $this->invalid('Paket H5P berisi terlalu banyak berkas.');
            }

            $entries = [];
            $totalBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = $stat === false ? '' : str_replace('\\', '/', $stat['name']);

                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                if (! $this->isSafeRelativePath($name)) {
                    throw $this->invalid('Paket H5P berisi nama berkas yang tidak aman.');
                }

                if (! in_array(Str::lower(pathinfo($name, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true)) {
                    continue;
                }

                $totalBytes += (int) $stat['size'];

                if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                    throw $this->invalid('Isi paket H5P terlalu besar setelah diekstrak.');
                }

                $entries[$index] = $name;
            }

            $disk->deleteDirectory($directory);

            foreach ($entries as $index => $name) {
                $stream = $zip->getStream((string) $zip->getNameIndex($index));

                if ($stream === false) {
                    throw $this->invalid('Paket H5P rusak dan tidak dapat diekstrak.');
                }

                $disk->writeStream("{$directory}/{$name}", $stream);
                fclose($stream);
            }
        } catch (ValidationException $exception) {
            $disk->deleteDirectory($directory);

            throw $exception;
        } finally {
            $zip->close();
        }
    }

    /**
     * Resolve an extracted package file for serving, or null when it does not exist or is not allowed.
     *
     * @return array{path: string, content_type: string}|null
     */
    public function resolveFile(LearningItem $item, string $relativePath): ?array
    {
        $directory = $item->h5pDirectory();
        $extension = Str::lower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if ($directory === null || ! $this->isSafeRelativePath($relativePath) || ! isset(self::CONTENT_TYPES[$extension])) {
            return null;
        }

        $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);
        $path = "{$directory}/{$relativePath}";

        return $disk->exists($path)
            ? ['path' => $disk->path($path), 'content_type' => self::CONTENT_TYPES[$extension]]
            : null;
    }

    /**
     * Determine whether a path stays inside its base directory.
     */
    public function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0") || preg_match('/^[a-zA-Z]:/', $path) === 1) {
            return false;
        }

        return ! in_array('..', explode('/', str_replace('\\', '/', $path)), true);
    }

    /**
     * Build the validation error reported for an unusable package.
     */
    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['attachment' => $message]);
    }
}
