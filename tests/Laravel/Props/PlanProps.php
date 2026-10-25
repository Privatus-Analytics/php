<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Laravel\Props;

use Illuminate\Http\Request;

final class PlanProps
{
    /**
     * @return array<string, string>
     */
    public function __invoke(Request $request): array
    {
        return ['plan' => 'team'];
    }
}
