<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('campaign'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'business_keyword' => ['sometimes', 'required', 'string', 'max:255'],
            'business_category' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'maximum_leads' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'include_businesses_without_website' => ['boolean'],
            'include_businesses_with_website' => ['boolean'],
            'minimum_rating' => ['nullable', 'numeric', 'between:0,5'],
            'minimum_review_count' => ['nullable', 'integer', 'min:0'],
            'search_language' => ['sometimes', 'string', 'size:2'],
            'country' => ['sometimes', 'string', 'size:2'],
        ];
    }
}
