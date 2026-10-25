<?php

declare(strict_types=1);

namespace Privatus\Analytics;

/**
 * Puts the tag just before </head>. Used by the framework middleware.
 */
final class HtmlInjector
{
    /**
     * Returns the new HTML, or null when the page has no </head>, already has the tag,
     * or the tag is empty.
     */
    public static function inject(string $html, string $tag): ?string
    {
        if ($tag === '' || str_contains($html, Tag::MARKER)) {
            return null;
        }

        $position = stripos($html, '</head>');
        if ($position === false) {
            return null;
        }

        return substr($html, 0, $position) . $tag . "\n" . substr($html, $position);
    }
}
