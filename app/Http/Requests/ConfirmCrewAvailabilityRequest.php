<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\HandleJsonApiRequest;
use App\Models\CrewAssignment;

class ConfirmCrewAvailabilityRequest extends FormRequest
{
    use HandleJsonApiRequest;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');
        return $assignment instanceof CrewAssignment && $assignment->crew_member_id === $this->user()->id;
    }



    public function rules(): array
    {
        return [
            //
        ];
    }
}
