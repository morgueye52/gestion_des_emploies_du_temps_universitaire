<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'academic_class_id' => ['required', 'exists:academic_classes,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'weekday' => ['required', 'integer', 'between:1,6'],
            'session_date' => ['nullable', 'date', Rule::requiredIf(! $this->filled('weekday'))],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'type' => ['required', Rule::in(['lecture', 'tutorial', 'practical'])],
        ];
    }
}
