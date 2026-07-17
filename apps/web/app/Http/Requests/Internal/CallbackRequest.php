<?php

declare(strict_types=1);

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication for this route is the internal.signed middleware
        // (HMAC signature), not a user session — always true here.
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required', 'string'],
            'campaign_id' => ['required', 'integer'],
            'status' => ['required', 'string', 'in:processing,partially_completed,completed,failed'],
            'progress' => ['required', 'array'],
            'progress.total_items' => ['required', 'integer', 'min:0'],
            'progress.processed_items' => ['required', 'integer', 'min:0'],
            'progress.successful_items' => ['required', 'integer', 'min:0'],
            'progress.failed_items' => ['required', 'integer', 'min:0'],
            'leads' => ['array'],
            // Every field CallbackLead (Python) can send needs a rule here —
            // FormRequest::validated() silently drops any input key that
            // has no matching rule, even a permissive 'nullable' one.
            'leads.*.company_name' => ['required_with:leads', 'string'],
            'leads.*.phone' => ['nullable', 'string'],
            'leads.*.website_url' => ['nullable', 'string'],
            'leads.*.address' => ['nullable', 'string'],
            'leads.*.province' => ['nullable', 'string'],
            'leads.*.business_type' => ['nullable', 'string'],
            'leads.*.source' => ['required_with:leads', 'string'],
            'leads.*.source_place_id' => ['nullable', 'string'],
            'leads.*.website_issue' => ['nullable', 'string'],
            'leads.*.issue_codes' => ['array'],
            'leads.*.audit_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'leads.*.confidence_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'leads.*.issues' => ['array'],
            'leads.*.evidence' => ['array'],
            'leads.*.raw_source_data' => ['array'],
            'errors' => ['array'],
            'errors.*.stage' => ['nullable', 'string'],
            'errors.*.message' => ['nullable', 'string'],
            'errors.*.context' => ['array'],
            'usage_logs' => ['array'],
            'usage_logs.*.provider' => ['required_with:usage_logs', 'string'],
            'usage_logs.*.endpoint' => ['required_with:usage_logs', 'string'],
            'usage_logs.*.request_hash' => ['required_with:usage_logs', 'string'],
            'usage_logs.*.response_status' => ['nullable', 'integer'],
            'usage_logs.*.credit_used' => ['nullable', 'integer', 'min:0'],
            'usage_logs.*.duration_ms' => ['nullable', 'integer', 'min:0'],
            'usage_logs.*.is_cached' => ['boolean'],
            'usage_logs.*.error_message' => ['nullable', 'string'],
            'usage_logs.*.requested_at' => ['nullable', 'string'],
        ];
    }
}
