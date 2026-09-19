<?php

namespace App\Http\Requests;

use App\Traits\HandleJsonApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AuthenticateUserRequest extends FormRequest
{
    use HandleJsonApiRequest;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [$this->isJsonApiRequest() ? 'required' : 'sometimes', 'in:authenticate'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
