<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Campaign::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_keyword' => ['required', 'string', 'max:255'],   // ← ต้องมี rule เสมอ ไม่งั้นหายจาก validated()
            'business_category' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', Rule::in(config('provinces.thailand'))],
            'district' => ['nullable', 'string', 'max:255'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'integer', 'min:1', 'max:200'],
            'maximum_leads' => ['nullable', 'integer', 'min:1', 'max:500'],
            'include_businesses_without_website' => ['boolean'],
            'include_businesses_with_website' => ['boolean'],
            'minimum_rating' => ['nullable', 'numeric', 'between:0,5'],
            'minimum_review_count' => ['nullable', 'integer', 'min:0'],
            'search_language' => ['nullable', 'string', 'size:2'],
            'country' => ['nullable', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // business_category มาแทน business_keyword ในฟอร์มใหม่แล้ว
            // เติมค่าให้ business_keyword ก่อน validate เพื่อให้ 'required' ผ่าน
            // และให้ validated() คืนค่านี้กลับมาด้วย (สำคัญ! ไม่งั้น insert พังแบบที่เจอ)
            'business_keyword' => $this->input('business_category'),
            'country' => $this->input('country', 'th'),
            'search_language' => $this->input('search_language', 'th'),
            'radius_km' => $this->input('radius_km', 10),
            'maximum_leads' => $this->input('maximum_leads', 100),
            'include_businesses_without_website' => $this->boolean('include_businesses_without_website', true),
            'include_businesses_with_website' => $this->boolean('include_businesses_with_website', true),
        ]);
    }
}