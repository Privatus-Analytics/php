<?php

declare(strict_types=1);

namespace Privatus\Analytics;

/**
 * Small text and HTML helpers shared by the tag and the attribute helpers.
 *
 * @internal
 */
final class Html
{
    public const MAX_PROPS = 30;
    public const MAX_KEY = 64;
    public const MAX_VALUE = 500;
    public const MAX_LIST = 30;

    /** HTML-escapes a value for use inside a double-quoted attribute. */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Turns ['src' => 'x', 'defer' => true] into 'defer src="x"'. true prints the bare name.
     *
     * @param array<string, scalar> $attributes
     */
    public static function attributes(array $attributes): string
    {
        $parts = [];
        foreach ($attributes as $name => $value) {
            if (!self::validAttributeName($name)) {
                continue;
            }
            if ($value === true) {
                $parts[] = $name;
                continue;
            }
            $text = is_bool($value) ? 'false' : (string) $value;
            $parts[] = $name . '="' . self::escape($text) . '"';
        }

        return implode(' ', $parts);
    }

    public static function validAttributeName(string $name): bool
    {
        return preg_match('/^[A-Za-z_:][A-Za-z0-9_.:-]*$/', $name) === 1;
    }

    /** Cuts a string to $max characters (UTF-8 aware, bytes when the text is not valid UTF-8). */
    public static function cut(string $value, int $max): string
    {
        if (preg_match('/^.{0,' . $max . '}/su', $value, $match) === 1) {
            return $match[0];
        }

        return substr($value, 0, $max);
    }

    /**
     * Normalises a property key: lowercase, anything but a-z 0-9 _ becomes _, at most 64 characters.
     * Returns '' when nothing usable is left.
     */
    public static function key(string|int $key): string
    {
        $key = strtolower((string) $key);
        $clean = preg_replace('/[^a-z0-9_]/u', '_', $key);
        if ($clean === null) {
            $clean = (string) preg_replace('/[^a-z0-9_]/', '_', $key);
        }

        return self::cut($clean, self::MAX_KEY);
    }

    /**
     * Makes JSON safe to place inside a <script> element.
     */
    public static function scriptSafeJson(mixed $value): string
    {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION,
        );
        if ($json === false) {
            return '{}';
        }

        $u = '\\u';

        return strtr($json, [
            '<' => $u . '003c',
            '>' => $u . '003e',
            '&' => $u . '0026',
            "\u{2028}" => $u . '2028',
            "\u{2029}" => $u . '2029',
        ]);
    }

    /** Wraps inline JavaScript in a script element, with a nonce when there is one. */
    public static function inlineScript(string $javascript, ?string $nonce): string
    {
        $open = $nonce !== null && $nonce !== '' ? '<script nonce="' . self::escape($nonce) . '">' : '<script>';

        return $open . $javascript . '</script>';
    }
}
