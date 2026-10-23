<?php

namespace App\Models;

use App\Enums\AttachmentKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A file or link attached to an item or a submission.
 *
 * Every column is set by Falcon itself (with forceFill), never from a form.
 *
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property AttachmentKind $kind
 * @property string|null $disk
 * @property string|null $path
 * @property string|null $original_name
 * @property string|null $mime
 * @property int|null $size
 * @property string|null $url
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Attachment extends Model
{
    /**
     * File types the browser may show inline. Everything else is always downloaded,
     * so uploaded HTML or SVG can never run in Falcon's origin.
     */
    private const array PREVIEW_TYPES = [
        'image/png' => 'image',
        'image/jpeg' => 'image',
        'image/gif' => 'image',
        'image/webp' => 'image',
        'video/mp4' => 'video',
        'video/webm' => 'video',
        'audio/mpeg' => 'audio',
        'audio/ogg' => 'audio',
        'audio/wav' => 'audio',
        'audio/x-wav' => 'audio',
        'audio/mp4' => 'audio',
        'application/pdf' => 'pdf',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Attachment $attachment) {
            if ($attachment->kind === AttachmentKind::File && $attachment->disk && $attachment->path) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AttachmentKind::class,
            'size' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * How the file can be previewed in the browser: image, video, audio, pdf or null.
     */
    public function previewType(): ?string
    {
        return $this->kind === AttachmentKind::File ? (self::PREVIEW_TYPES[$this->mime] ?? null) : null;
    }

    /**
     * @return array{id: int, kind: string, name: string, size: int|null, preview: string|null, url: string}
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'name' => $this->kind === AttachmentKind::File ? (string) $this->original_name : (string) $this->url,
            'size' => $this->size,
            'preview' => $this->previewType(),
            'url' => $this->kind === AttachmentKind::File ? route('attachments.show', $this) : (string) $this->url,
        ];
    }
}
