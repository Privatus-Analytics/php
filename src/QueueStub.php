<?php

declare(strict_types=1);

namespace Privatus\Analytics;

/**
 * The official queue script. Calls such as privatus.track() made before the tracker
 * loads wait in a queue and are sent once it is ready.
 */
final class QueueStub
{
    public const JAVASCRIPT = "window.privatus=window.privatus||{q:[]};['track','pageview','props','optOut','optIn','ready'].forEach(function(m){window.privatus[m]=window.privatus[m]||function(){window.privatus.q.push([m,Array.prototype.slice.call(arguments)])}})";

    public static function render(?string $nonce = null): string
    {
        return Html::inlineScript(self::JAVASCRIPT, $nonce);
    }
}
