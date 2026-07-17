<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Campaign */
class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'business_keyword' => $this->business_keyword,
            'business_category' => $this->business_category,
            'province' => $this->province,
            'district' => $this->district,
            'radius_km' => $this->radius_km,
            'maximum_leads' => $this->maximum_leads,
            'status' => $this->status,
            'owner' => $this->whenLoaded('user', fn () => $this->user->name),
            'leads_count' => $this->whenCounted('leads'),
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
