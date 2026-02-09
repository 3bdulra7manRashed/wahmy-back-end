<?php

declare(strict_types=1);

namespace App\Modules\Branch\Controllers;

use App\Shared\Http\BaseApiController;
use App\Modules\Branch\Models\Branch;
use App\Modules\Branch\Resources\BranchResource;
use App\Shared\Http\ApiResponse;
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
