<?php

declare(strict_types=1);

namespace Privatus\Analytics;

/**
 * Builds the data-privatus-event attributes for a button, link or form.
 *
 *   EventAttributes::render('Export CSV', ['format' => 'csv'])
 *   // data-privatus-event="Export CSV" data-privatus-prop-format="csv"
 *
 * On a <form> the event fires when the form is sent. The attributes print even when
 * the tag is turned off, so your development and production HTML match.
 */
final class EventAttributes
{
    public const MAX_NAME = 120;

    /**
     * @param array<string|int, mixed> $props
     */
    public static function render(string $name, array $props = []): string
    {
        $attributes = self::attributes($name, $props);

        return $attributes === [] ? '' : Html::attributes($attributes);
    }

    /**
     * The attributes as an array, for frameworks that build tags themselves.
     *
     * @param array<string|int, mixed> $props
     *
     * @return array<string, string>
     */
    public static function attributes(string $name, array $props = []): array
    {
        $name = Html::cut(trim($name), self::MAX_NAME);
        if ($name === '') {
            return [];
        }

        $attributes = ['data-privatus-event' => $name];
        foreach (self::props($props) as $key => $value) {
            $attributes['data-privatus-prop-' . $key] = $value;
        }

        return $attributes;
    }

    /**
     * Normalised event properties: clean keys, string values, at most 30.
     *
     * @param array<string|int, mixed> $props
     *
     * @return array<string, string>
     */
    public static function props(array $props): array
    {
        $clean = [];
        foreach ($props as $key => $value) {
            $key = Html::key($key);
            $text = self::value($value);
            if ($key === '' || $text === null) {
                continue;
            }
            if (!array_key_exists($key, $clean) && count($clean) >= Html::MAX_PROPS) {
                continue;
            }
            $clean[$key] = $text;
        }

        return $clean;
    }

    private static function value(mixed $value): ?string
    {
        if (is_array($value)) {
            if (!array_is_list($value)) {
                return null;
            }
            $items = [];
            foreach ($value as $item) {
                $text = self::scalar($item);
                if ($text !== null) {
                    $items[] = $text;
                }
            }

            return Html::cut(implode(',', $items), Html::MAX_VALUE);
        }

        $text = self::scalar($value);

        return $text === null ? null : Html::cut($text, Html::MAX_VALUE);
    }

    private static function scalar(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => is_finite($value) ? self::number($value) : null,
            is_scalar($value), $value instanceof \Stringable => (string) $value,
            default => null,
        };
    }

    private static function number(float $value): string
    {
        $text = (string) $value;

        return str_contains($text, 'E') ? rtrim(rtrim(sprintf('%.15F', $value), '0'), '.') : $text;
    }
}
