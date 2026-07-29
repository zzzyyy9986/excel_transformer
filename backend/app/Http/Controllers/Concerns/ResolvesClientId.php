<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait ResolvesClientId
{
    protected function clientId(Request $request): ?string
    {
        $clientId = $request->header('X-Client-Id');

        if (! is_string($clientId) || ! Str::isUuid($clientId)) {
            return null;
        }

        return $clientId;
    }
}
