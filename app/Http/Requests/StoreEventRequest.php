<?php

namespace App\Http\Requests;

use App\Traits\HandleJsonApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'title'             => [' required', 'string', 'max:255'],
            'ticket_price_kobo' => ['required', 'integer', 'min:1'],
            'scheduled_duration_minutes' => ['required', 'integer', 'min:1'],
            'scheduled_starts_at' => ['required', 'date', 'after:now'],
        ];
    }
}
