<?php

declare(strict_types=1);

namespace Privatus\Analytics;

/**
 * Page properties sent with every pageview and event on a page, such as plan or role.
 *
 * Never put names, emails, user ids or anything else that points to one person in here.
 * Use groups (plan, role), not people.
 */
final class PageProps
{
    public const FUNCTION_NAME = 'privatusPageProps';

    /**
     * Clean keys, JSON-friendly values, at most 30 properties.
     * Numbers and booleans stay as they are, string lists stay lists (at most 30 items).
     *
     * @param array<string|int, mixed> $props
     *
     * @return array<string, string|int|float|bool|list<string|int|float|bool>>
     */
    public static function normalize(array $props): array
    {
        $clean = [];
        foreach ($props as $key => $value) {
            $key = Html::key($key);
            $value = self::value($value);
            if ($key === '' || $value === null) {
                continue;
            }
            if (!array_key_exists($key, $clean) && count($clean) >= Html::MAX_PROPS) {
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * The properties as JSON that is safe inside a script element.
     *
     * @param array<string|int, mixed> $props
     */
    public static function json(array $props): string
    {
        $clean = self::normalize($props);

        return $clean === [] ? '{}' : Html::scriptSafeJson($clean);
    }

    /**
     * The inline script that defines window.privatusPageProps. '' when there are no properties.
     *
     * @param array<string|int, mixed> $props
     */
    public static function script(array $props, ?string $nonce = null): string
    {
        $clean = self::normalize($props);
        if ($clean === []) {
            return '';
        }

        return Html::inlineScript(
            'window.' . self::FUNCTION_NAME . '=function(){return ' . Html::scriptSafeJson($clean) . '}',
            $nonce,
        );
    }

    /**
     * @return string|int|float|bool|list<string|int|float|bool>|null
     */
    private static function value(mixed $value): string|int|float|bool|array|null
    {
        if (!is_array($value)) {
            return self::scalar($value);
        }
        if (!array_is_list($value)) {
            return null;
        }

        $items = [];
        foreach ($value as $item) {
            $item = self::scalar($item);
            if ($item !== null && count($items) < Html::MAX_LIST) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private static function scalar(mixed $value): string|int|float|bool|null
    {
        return match (true) {
            is_bool($value), is_int($value) => $value,
            is_float($value) => is_finite($value) ? $value : null,
            is_string($value) => Html::cut($value, Html::MAX_VALUE),
            $value instanceof \Stringable => Html::cut((string) $value, Html::MAX_VALUE),
            default => null,
        };
    }
}
