<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
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
        $customerId = $this->route('customer');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('customers', 'code')
                    ->where('organization_id', $organizationId)
                    ->ignore($customerId)
                    ->whereNull('deleted_at'),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],

            // Billing address fields
            'billing_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'billing_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],

            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],

            // Relationship data
            'contacts' => ['sometimes', 'nullable', 'array'],
            'contacts.*.id' => ['required_with:contacts', 'uuid', 'exists:contacts,id'],
            'contacts.*.contact_type' => ['required_with:contacts', 'string', 'in:general,primary,billing,technical'],
            'contacts.*.is_primary' => ['boolean'],

            'statuses' => ['sometimes', 'nullable', 'array'],
            'statuses.*.id' => ['required_with:statuses', 'uuid', 'exists:statuses,id'],
            'statuses.*.is_current' => ['boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'name.string' => 'Customer name must be a string.',
            'name.max' => 'Customer name cannot exceed 255 characters.',

            'code.unique' => 'Customer code must be unique within your organization.',
            'code.max' => 'Customer code cannot exceed 255 characters.',

            'email.email' => 'Please provide a valid email address.',
            'website.url' => 'Please provide a valid website URL.',

            'contacts.*.id.exists' => 'One or more selected contacts do not exist.',
            'contacts.*.contact_type.in' => 'Contact type must be one of: general, primary, billing, technical.',

            'statuses.*.id.exists' => 'One or more selected statuses do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'customer name',
            'code' => 'customer code',
            'email' => 'email address',
            'phone' => 'phone number',
            'website' => 'website URL',
            'industry' => 'industry',
            'description' => 'description',
            'billing_address' => 'billing address',
            'billing_city' => 'billing city',
            'billing_state' => 'billing state',
            'billing_country' => 'billing country',
            'billing_postal_code' => 'billing postal code',
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

        // Ensure contacts and statuses arrays are properly structured
        if ($this->has('contacts') && ! is_array($this->contacts)) {
            $this->merge(['contacts' => []]);
        }

        if ($this->has('statuses') && ! is_array($this->statuses)) {
            $this->merge(['statuses' => []]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that contacts belong to the same organization
            if ($this->has('contacts')) {
                $this->validateContactsOrganization($validator);
            }

            // Validate that statuses belong to the same organization and are customer statuses
            if ($this->has('statuses')) {
                $this->validateStatusesOrganization($validator);
            }

            // Ensure only one primary contact per type
            if ($this->has('contacts')) {
                $this->validatePrimaryContacts($validator);
            }
        });
    }

    /**
     * Validate that contacts belong to the user's organization.
     */
    private function validateContactsOrganization($validator): void
    {
        $organizationId = auth()->user()->organization_id;
        $contactIds = collect($this->contacts)->pluck('id')->filter();

        if ($contactIds->isNotEmpty()) {
            $validContacts = \App\Domain\Shared\Models\Contact::whereIn('id', $contactIds)
                ->where('organization_id', $organizationId)
                ->count();

            if ($validContacts !== $contactIds->count()) {
                $validator->errors()->add('contacts', 'Some contacts do not belong to your organization.');
            }
        }
    }

    /**
     * Validate that statuses belong to the user's organization and are customer statuses.
     */
    private function validateStatusesOrganization($validator): void
    {
        $organizationId = auth()->user()->organization_id;
        $statusIds = collect($this->statuses)->pluck('id')->filter();

        if ($statusIds->isNotEmpty()) {
            $validStatuses = \App\Domain\Shared\Models\Status::whereIn('id', $statusIds)
                ->where('organization_id', $organizationId)
                ->where('type', 'customer')
                ->count();

            if ($validStatuses !== $statusIds->count()) {
                $validator->errors()->add('statuses', 'Some statuses do not belong to your organization or are not customer statuses.');
            }
        }
    }

    /**
     * Validate that there's only one primary contact per contact type.
     */
    private function validatePrimaryContacts($validator): void
    {
        $contactTypes = collect($this->contacts)
            ->filter(fn ($contact) => $contact['is_primary'] ?? false)
            ->groupBy('contact_type');

        foreach ($contactTypes as $type => $contacts) {
            if ($contacts->count() > 1) {
                $validator->errors()->add(
                    'contacts',
                    "Only one primary contact is allowed per contact type. Multiple primary contacts found for type: {$type}."
                );
            }
        }
    }
}
