<?php

declare(strict_types=1);

namespace Privatus\Analytics;

/**
 * Names an element for click maps.
 *
 *   ClickAttribute::render('pricing.cta') // data-privatus-click="pricing.cta"
 *
 * A name may use letters, digits and _ . : - and be 1 to 100 characters long.
 * Any other name prints nothing.
 */
final class ClickAttribute
{
    public const PATTERN = '/^[A-Za-z0-9_.:-]{1,100}$/D';

    public static function isValid(string $name): bool
    {
        return preg_match(self::PATTERN, $name) === 1;
    }

    public static function render(string $name): string
    {
        return self::isValid($name) ? Html::attributes(['data-privatus-click' => $name]) : '';
    }
}
