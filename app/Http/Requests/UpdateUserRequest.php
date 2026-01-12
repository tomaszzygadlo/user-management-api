<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="UpdateUserRequest",
 *     title="UpdateUserRequest",
 *     description="Update user request body",
 *     @OA\Property(property="first_name", type="string", maxLength=255, example="John"),
 *     @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
 *     @OA\Property(property="phone_number", type="string", maxLength=20, example="+1234567890"),
 *     @OA\Property(
 *         property="emails",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *             @OA\Property(property="is_primary", type="boolean", example=true),
 *             @OA\Property(property="delete", type="boolean", example=false)
 *         )
 *     )
 * )
 */
class UpdateUserRequest extends FormRequest
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
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
                'max:20',
            ],
            'emails' => ['sometimes', 'array', 'min:1', 'max:10'],
            'emails.*.id' => ['sometimes', 'integer', 'exists:emails,id'],
            'emails.*.email' => [
                'required_without:emails.*.delete',
                // Use DNS check only in production/staging, not in testing
                app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns',
                'max:255',
                'distinct',
            ],
            'emails.*.is_primary' => ['sometimes', 'boolean'],
            'emails.*.delete' => ['sometimes', 'boolean'],
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
            'first_name.required' => 'First name cannot be empty.',
            'last_name.required' => 'Last name cannot be empty.',
            'phone_number.required' => 'Phone number cannot be empty.',
            'phone_number.regex' => 'Invalid phone number format.',
            'emails.min' => 'At least one email address is required.',
            'emails.*.email.required' => 'Email address is required.',
            'emails.*.email.email' => 'Invalid email address format.',
            'emails.*.email.distinct' => 'Duplicate email addresses are not allowed.',
            'emails.*.id.exists' => 'Email not found.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure only one email is marked as primary if emails are being updated
            if ($this->has('emails')) {
                $nonDeletedEmails = collect($this->emails)
                    ->reject(fn($email) => ($email['delete'] ?? false) === true);

                $primaryCount = $nonDeletedEmails
                    ->filter(fn($email) => ($email['is_primary'] ?? false) === true)
                    ->count();

                if ($primaryCount > 1) {
                    $validator->errors()->add(
                        'emails',
                        'Only one email can be marked as primary.'
                    );
                }

                // Ensure at least one email remains after deletion
                if ($nonDeletedEmails->isEmpty()) {
                    $validator->errors()->add(
                        'emails',
                        'At least one email must remain.'
                    );
                }

                // Ensure at least one email is primary after deletion
                if ($nonDeletedEmails->isNotEmpty() && $primaryCount === 0) {
                    $validator->errors()->add(
                        'emails',
                        'At least one email must be marked as primary.'
                    );
                }
            }
        });
    }
}
