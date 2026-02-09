<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    /**
     * Default number of items per page.
     */
    protected int $defaultPerPage = 15;

    /**
     * Maximum number of items per page.
     */
    protected int $maxPerPage = 100;

    /**
     * Resolve the number of items per page from request.
     *
     * Ensures per_page is between 1 and maxPerPage.
     */
    protected function resolvePerPage(Request $request): int
    {
        return min(
            max(1, $request->integer('per_page', $this->defaultPerPage)),
            $this->maxPerPage
        );
    }
}
