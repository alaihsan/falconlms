<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans HTML from the material editor before it is stored, so it can be
 * shown again with v-html without letting anyone inject scripts.
 */
final class RichText
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim(strip_tags($html)) === '') {
            return null;
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('em')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('pre')
                ->allowElement('code')
                ->allowElement('hr')
                ->allowElement('a', ['href'])
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->forceAttribute('a', 'target', '_blank')
                ->withMaxInputLength(200_000),
        );
    }
}
