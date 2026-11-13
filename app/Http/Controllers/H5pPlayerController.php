<?php

namespace App\Http\Controllers;

use App\Models\LearningItem;
use App\Services\H5pPackageService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Serves H5P content to a sandboxed iframe.
 *
 * H5P packages carry their own JavaScript, so the player runs in an opaque origin without the
 * user's session. Access is granted by the expiring signature in the URL path instead of cookies.
 */
class H5pPlayerController extends Controller
{
    /**
     * Headers that keep every response isolated from the application origin.
     *
     * @var array<string, string>
     */
    private const array ISOLATION_HEADERS = [
        'Content-Security-Policy' => 'sandbox allow-scripts',
        'Access-Control-Allow-Origin' => '*',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'no-referrer',
    ];

    public function __construct(private H5pPackageService $packages) {}

    /**
     * Render the standalone player page for an H5P item.
     */
    public function player(LearningItem $learningItem, int $expires, string $signature): Response
    {
        $this->ensureValidSignature($learningItem, $expires, $signature);

        $base = Str::beforeLast(URL::current(), '/player');

        return response()
            ->view('h5p-player', [
                'title' => $learningItem->title,
                'options' => [
                    'h5pJsonPath' => "{$base}/content",
                    // Render straight into the page: the sandbox gives nested frames their own origin, which breaks the default iframe embed.
                    'embedType' => 'div',
                    'frameJs' => route('h5p.asset', ['path' => 'frame.bundle.js']),
                    'frameCss' => route('h5p.asset', ['path' => 'styles/h5p.css']),
                ],
                'playerScript' => route('h5p.asset', ['path' => 'main.bundle.js']),
            ])
            ->withHeaders(self::ISOLATION_HEADERS + ['Cache-Control' => 'private, no-store']);
    }

    /**
     * Serve one file of the extracted H5P package.
     */
    public function content(LearningItem $learningItem, int $expires, string $signature, string $path): BinaryFileResponse
    {
        $this->ensureValidSignature($learningItem, $expires, $signature);

        $file = $this->packages->resolveFile($learningItem, $path);

        if ($file === null) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->file($file['path'], self::ISOLATION_HEADERS + [
            'Content-Type' => $file['content_type'],
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Serve a file of the bundled H5P player (scripts, styles, fonts, images).
     */
    public function asset(string $path): BinaryFileResponse
    {
        $root = resource_path('h5p-standalone');
        $contentType = [
            'js' => 'text/javascript',
            'css' => 'text/css',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif',
            'png' => 'image/png',
        ][Str::lower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;

        if ($contentType === null || ! $this->packages->isSafeRelativePath($path) || ! is_file("{$root}/{$path}")) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->file("{$root}/{$path}", self::ISOLATION_HEADERS + [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Abort unless the URL carries an unexpired signature for this item.
     */
    private function ensureValidSignature(LearningItem $learningItem, int $expires, string $signature): void
    {
        if ($learningItem->type !== 'h5p'
            || $expires < now()->getTimestamp()
            || ! hash_equals($learningItem->h5pSignature($expires), $signature)) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Tautan pemutar H5P tidak berlaku atau sudah kedaluwarsa.');
        }
    }
}
