<?php

declare(strict_types=1);

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $organizationId = auth()->user()->organization_id;
        $assetId = $this->route('asset')->id;

        return [
            'customer_id' => ['sometimes', 'uuid', 'exists:customers,id'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'oem_id' => ['nullable', 'uuid', 'exists:oems,id'],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'type_id' => ['nullable', 'uuid', 'exists:types,id'],
            'status_id' => ['nullable', 'uuid', 'exists:statuses,id'],

            // Asset identification
            'serial_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('assets', 'serial_number')
                    ->where('organization_id', $organizationId)
                    ->ignore($assetId)
                    ->whereNull('deleted_at'),
            ],
            'asset_tag' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'part_number' => ['nullable', 'string', 'max:255'],

            // Asset details
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'installation_date' => ['nullable', 'date'],
            'warranty_start_date' => ['nullable', 'date'],
            'warranty_end_date' => ['nullable', 'date', 'after_or_equal:warranty_start_date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'current_value' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],

            'is_active' => ['boolean'],
            'metadata' => ['nullable', 'array'],

            // Relationship data
            'tags' => ['nullable', 'array'],
            'tags.*' => ['required_with:tags', 'uuid', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Selected customer does not exist.',

            'serial_number.unique' => 'Serial number must be unique within your organization.',

            'name.string' => 'Asset name must be a string.',
            'name.max' => 'Asset name cannot exceed 255 characters.',

            'purchase_date.before_or_equal' => 'Purchase date cannot be in the future.',
            'warranty_end_date.after_or_equal' => 'Warranty end date must be after or equal to warranty start date.',

            'purchase_price.numeric' => 'Purchase price must be a valid number.',
            'purchase_price.min' => 'Purchase price cannot be negative.',
            'current_value.numeric' => 'Current value must be a valid number.',
            'current_value.min' => 'Current value cannot be negative.',

            'tags.*.exists' => 'One or more selected tags do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'location_id' => 'location',
            'oem_id' => 'OEM',
            'product_id' => 'product',
            'type_id' => 'asset type',
            'status_id' => 'status',
            'serial_number' => 'serial number',
            'asset_tag' => 'asset tag',
            'model_number' => 'model number',
            'part_number' => 'part number',
            'name' => 'asset name',
            'description' => 'description',
            'purchase_date' => 'purchase date',
            'installation_date' => 'installation date',
            'warranty_start_date' => 'warranty start date',
            'warranty_end_date' => 'warranty end date',
            'purchase_price' => 'purchase price',
            'current_value' => 'current value',
            'is_active' => 'active status',
            'metadata' => 'metadata',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Ensure tags array is properly structured
        if ($this->has('tags') && ! is_array($this->tags)) {
            $this->merge(['tags' => []]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that customer belongs to the same organization
            if ($this->has('customer_id')) {
                $this->validateCustomerOrganization($validator);
            }

            // Validate that related entities belong to the same organization
            $this->validateRelatedEntitiesOrganization($validator);
        });
    }

    /**
     * Validate that customer belongs to the user's organization.
     */
    private function validateCustomerOrganization($validator): void
    {
        $organizationId = auth()->user()->organization_id;

        if ($this->customer_id) {
            $customer = \App\Domain\Customer\Models\Customer::find($this->customer_id);

            if (! $customer || $customer->organization_id !== $organizationId) {
                $validator->errors()->add('customer_id', 'Customer does not belong to your organization.');
            }
        }
    }

    /**
     * Validate that related entities belong to the user's organization.
     */
    private function validateRelatedEntitiesOrganization($validator): void
    {
        $organizationId = auth()->user()->organization_id;

        // Validate location
        if ($this->location_id) {
            $location = \App\Domain\Location\Models\Location::find($this->location_id);
            if (! $location || $location->organization_id !== $organizationId) {
                $validator->errors()->add('location_id', 'Location does not belong to your organization.');
            }
        }

        // Validate tags
        if ($this->has('tags') && is_array($this->tags)) {
            $tagIds = collect($this->tags)->filter();
            if ($tagIds->isNotEmpty()) {
                $validTags = \App\Domain\Shared\Models\Tag::whereIn('id', $tagIds)
                    ->where('organization_id', $organizationId)
                    ->count();

                if ($validTags !== $tagIds->count()) {
                    $validator->errors()->add('tags', 'Some tags do not belong to your organization.');
                }
            }
        }
    }
}
