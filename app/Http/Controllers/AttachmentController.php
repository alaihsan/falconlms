<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentKind;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    /**
     * Send a file after checking that the user may see what it belongs to.
     *
     * Only known safe types (images, audio, video, PDF) are shown inline.
     * Everything else is downloaded with a generic content type and a
     * sandboxing CSP, so an uploaded HTML file can never run as Falcon.
     */
    public function show(Request $request, Attachment $attachment): Response
    {
        Gate::authorize('view', $attachment);
        abort_unless($attachment->kind === AttachmentKind::File && $attachment->path, 404);

        $preview = $attachment->previewType();
        $inline = $preview !== null && ! $request->boolean('download');
        $name = (string) $attachment->original_name;
        $fallback = (string) preg_replace('/[^\x20-\x7E]|[%\/\\\\"]/', '_', Str::ascii($name));

        $headers = [
            'Content-Type' => $inline ? (string) $attachment->mime : 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $name, $fallback ?: 'file'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ];

        // Browsers refuse to render PDFs inside a sandbox, so only PDFs shown inline skip it.
        if (! ($inline && $preview === 'pdf')) {
            $headers['Content-Security-Policy'] = "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox";
        }

        if ($prefix = config('falcon.x_accel_redirect')) {
            return response('', 200, $headers + ['X-Accel-Redirect' => rtrim($prefix, '/').'/'.$attachment->path]);
        }

        return response()->file(Storage::disk((string) $attachment->disk)->path($attachment->path), $headers);
    }
}
