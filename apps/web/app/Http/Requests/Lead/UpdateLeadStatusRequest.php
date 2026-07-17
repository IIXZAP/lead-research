<?php

declare(strict_types=1);

namespace App\Http\Requests\Lead;

use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(LeadStatus::class)],
        ];
    }
}
