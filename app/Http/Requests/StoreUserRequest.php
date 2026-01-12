<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StoreUserRequest",
 *     title="StoreUserRequest",
 *     description="Store user request body",
 *     required={"first_name", "last_name", "phone_number", "emails"},
 *     @OA\Property(property="first_name", type="string", maxLength=255, example="John"),
 *     @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
 *     @OA\Property(property="phone_number", type="string", maxLength=20, example="+1234567890"),
 *     @OA\Property(
 *         property="emails",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *             @OA\Property(property="is_primary", type="boolean", example=true)
 *         )
 *     )
 * )
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
                'max:20',
            ],
            'emails' => ['required', 'array', 'min:1', 'max:10'],
            'emails.*.email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'distinct', // No duplicates in the request
            ],
            'emails.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Invalid phone number format.',
            'emails.required' => 'At least one email address is required.',
            'emails.min' => 'At least one email address is required.',
            'emails.*.email.required' => 'Email address is required.',
            'emails.*.email.email' => 'Invalid email address format.',
            'emails.*.email.distinct' => 'Duplicate email addresses are not allowed.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure at least one email is marked as primary
        if ($this->has('emails') && is_array($this->emails)) {
            $hasPrimary = collect($this->emails)->contains('is_primary', true);

            if (!$hasPrimary && count($this->emails) > 0) {
                $emails = $this->emails;
                $emails[0]['is_primary'] = true;
                $this->merge(['emails' => $emails]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure only one email is marked as primary
            if ($this->has('emails')) {
                $primaryCount = collect($this->emails)
                    ->filter(fn($email) => ($email['is_primary'] ?? false) === true)
                    ->count();

                if ($primaryCount > 1) {
                    $validator->errors()->add(
                        'emails',
                        'Only one email can be marked as primary.'
                    );
                }
            }
        });
    }
}
