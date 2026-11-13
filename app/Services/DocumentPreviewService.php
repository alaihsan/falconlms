<?php

namespace App\Services;

use App\Models\LearningItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DocumentPreviewService
{
    /**
     * Content types of attachments the browser can display directly, keyed by extension.
     *
     * @var array<string, string>
     */
    private const array INLINE_CONTENT_TYPES = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'mp4' => 'video/mp4',
        'mp3' => 'audio/mpeg',
    ];

    /**
     * Office extensions that are converted to PDF before being displayed.
     *
     * @var list<string>
     */
    private const array OFFICE_EXTENSIONS = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];

    /**
     * Describe how an item's attachment is previewed: pdf, image, video, audio, office, or null when it cannot be.
     */
    public function kind(LearningItem $item): ?string
    {
        if ($item->attachment_path === null) {
            return null;
        }

        $extension = $this->extension($item);

        if (in_array($extension, self::OFFICE_EXTENSIONS, true)) {
            return 'office';
        }

        return match (self::INLINE_CONTENT_TYPES[$extension] ?? null) {
            'application/pdf' => 'pdf',
            'image/png', 'image/jpeg' => 'image',
            'video/mp4' => 'video',
            'audio/mpeg' => 'audio',
            default => null,
        };
    }

    /**
     * Resolve the file to display inline for an item, converting office documents to PDF on first use.
     *
     * @return array{path: string, content_type: string}|null Null when no preview can be produced.
     */
    public function resolve(LearningItem $item): ?array
    {
        $kind = $this->kind($item);
        $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);

        if ($kind === null || ! $disk->exists((string) $item->attachment_path)) {
            return null;
        }

        if ($kind !== 'office') {
            return [
                'path' => $disk->path((string) $item->attachment_path),
                'content_type' => self::INLINE_CONTENT_TYPES[$this->extension($item)],
            ];
        }

        $previewPath = $this->convertedPdfPath($item);

        return $previewPath === null
            ? null
            : ['path' => $disk->path($previewPath), 'content_type' => 'application/pdf'];
    }

    /**
     * Get the cached PDF rendition of an office attachment, creating it with LibreOffice when missing.
     */
    private function convertedPdfPath(LearningItem $item): ?string
    {
        $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);
        $previewPath = (string) $item->previewPath();

        if ($disk->exists($previewPath)) {
            return $previewPath;
        }

        try {
            return Cache::lock("document-preview:{$previewPath}", 120)->block(100, function () use ($disk, $item, $previewPath): ?string {
                if ($disk->exists($previewPath)) {
                    return $previewPath;
                }

                return $this->convert($disk->path((string) $item->attachment_path), $previewPath) ? $previewPath : null;
            });
        } catch (Throwable $exception) {
            Log::warning('Document preview conversion failed.', [
                'learning_item_id' => $item->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert an office document to PDF with LibreOffice and store it at the preview path.
     */
    private function convert(string $sourcePath, string $previewPath): bool
    {
        $workDirectory = storage_path('app/private/library-previews/tmp-'.Str::random(16));
        File::ensureDirectoryExists($workDirectory);

        try {
            $result = Process::timeout((int) config('services.libreoffice.timeout'))->run([
                (string) config('services.libreoffice.binary'),
                '--headless',
                '--norestore',
                "-env:UserInstallation=file://{$workDirectory}/profile",
                '--convert-to',
                'pdf',
                '--outdir',
                $workDirectory,
                $sourcePath,
            ]);

            $convertedFile = $workDirectory.'/'.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';

            if (! $result->successful() || ! File::exists($convertedFile)) {
                Log::warning('LibreOffice did not produce a PDF preview.', [
                    'source' => $sourcePath,
                    'exit_code' => $result->exitCode(),
                    'error' => Str::limit($result->errorOutput(), 500),
                ]);

                return false;
            }

            return Storage::disk(LearningItem::ATTACHMENT_DISK)->put($previewPath, File::get($convertedFile));
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    /**
     * Get the lowercase extension of the stored attachment.
     */
    private function extension(LearningItem $item): string
    {
        return Str::lower(pathinfo((string) $item->attachment_path, PATHINFO_EXTENSION));
    }
}
