<?php

declare(strict_types=1);

namespace App\Modules\Branch\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Branch\Models\Branch
 */
class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslated('name'),
            'address' => $this->getTranslated('address'),
            'description' => $this->getTranslated('description'),
            'is_active' => $this->is_active,
            'is_open_now' => $this->isOpenNow(),
            'created_at' => $this->created_at,
        ];
    }
}
