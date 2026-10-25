<?php

declare(strict_types=1);

namespace Privatus\Analytics\Laravel;

use Closure;
use Illuminate\Http\Request;
use Privatus\Analytics\HtmlInjector;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Adds the tag just before </head> in HTML pages.
 *
 * It only touches status 200 text/html responses that are not compressed, not Ajax
 * requests, not streamed, have a </head> and do not already have the tag.
 */
class InjectPrivatusAnalytics
{
    public function __construct(private readonly PrivatusManager $privatus)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // A tag that is off inserts nothing, not even the queue stub.
        if ($response instanceof Response && $this->privatus->enabled() && $this->shouldInject($request, $response)) {
            $html = HtmlInjector::inject((string) $response->getContent(), (string) $this->privatus->tag());
            if ($html !== null) {
                $response->setContent($html);
                $response->headers->set('Content-Length', (string) strlen($html));
            }
        }

        return $response;
    }

    private function shouldInject(Request $request, Response $response): bool
    {
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }
        if ($response->getStatusCode() !== 200 || $response->headers->has('Content-Encoding')) {
            return false;
        }
        if (!str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            return false;
        }
        if ($request->ajax()) {
            return false;
        }

        return $response->getContent() !== false;
    }
}
