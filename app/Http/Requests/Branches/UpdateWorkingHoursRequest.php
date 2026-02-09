<?php

declare(strict_types=1);

namespace App\Http\Requests\Branches;

use App\Modules\Branch\Models\Branch;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return $this->user()?->can('manageWorkingHours', $branch) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'data.*.opens_at' => ['nullable', 'date_format:H:i:s'],
            'data.*.closes_at' => ['nullable', 'date_format:H:i:s'],
            'data.*.is_closed' => ['required', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $this->input('data', []);

            foreach ($data as $index => $dayData) {
                $isClosed = filter_var($dayData['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $opensAt = $dayData['opens_at'] ?? null;
                $closesAt = $dayData['closes_at'] ?? null;

                if ($isClosed) {
                    // If closed, opens_at and closes_at must be null
                    if ($opensAt !== null) {
                        $validator->errors()->add(
                            "data.{$index}.opens_at",
                            'Opening time must be null when the day is marked as closed.'
                        );
                    }
                    if ($closesAt !== null) {
                        $validator->errors()->add(
                            "data.{$index}.closes_at",
                            'Closing time must be null when the day is marked as closed.'
                        );
                    }
                } else {
                    // If open, opens_at and closes_at are required
                    if ($opensAt === null) {
                        $validator->errors()->add(
                            "data.{$index}.opens_at",
                            'Opening time is required when the day is not closed.'
                        );
                    }
                    if ($closesAt === null) {
                        $validator->errors()->add(
                            "data.{$index}.closes_at",
                            'Closing time is required when the day is not closed.'
                        );
                    }
                }
            }
        });
    }
}
