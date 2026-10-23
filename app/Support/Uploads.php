<?php

namespace App\Support;

use App\Enums\AttachmentKind;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class Uploads
{
    /**
     * Validation rules for one uploaded file.
     *
     * `extensions` checks the name the user sees, `mimes` checks the actual
     * content, so a renamed file is rejected too.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        $extensions = implode(',', config('falcon.uploads.extensions'));

        return [
            'file',
            'max:'.config('falcon.uploads.max_kilobytes'),
            'extensions:'.$extensions,
            'mimes:'.$extensions,
        ];
    }

    /**
     * Store an uploaded file on the private disk and attach it to a model.
     */
    public static function attach(Model $attachable, UploadedFile $file, User $user): Attachment
    {
        $attachment = new Attachment;
        $attachment->forceFill([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'kind' => AttachmentKind::File,
            'disk' => 'local',
            'path' => $file->store('attachments/'.now()->format('Y/m'), 'local'),
            'original_name' => self::cleanName($file->getClientOriginalName()),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_by' => $user->id,
        ])->save();

        return $attachment;
    }

    /**
     * Attach a web link to a model.
     */
    public static function attachLink(Model $attachable, string $url, User $user): Attachment
    {
        $attachment = new Attachment;
        $attachment->forceFill([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'kind' => AttachmentKind::Link,
            'url' => $url,
            'created_by' => $user->id,
        ])->save();

        return $attachment;
    }

    /**
     * Keep only the file name itself, without folders or control characters.
     */
    public static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        return Str::limit($name === '' ? 'file' : $name, 200, '');
    }
}
