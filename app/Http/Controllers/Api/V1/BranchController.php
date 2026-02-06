<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domains\Branches\Models\Branch;
use App\Http\Resources\BranchResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends BaseApiController
{
    /**
     * Get list of active branches.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $branches = Branch::query()
            ->active()
            ->paginate($perPage);

        return ApiResponse::success(
            BranchResource::collection($branches)
        );
    }

    /**
     * Get a single branch.
     */
    public function show(Branch $branch): JsonResponse
    {
        return ApiResponse::success(
            new BranchResource($branch)
        );
    }
}
