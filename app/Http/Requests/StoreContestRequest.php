<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->actingAsAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', 'in:draft,active,closed'],
            'contest_type' => ['required', 'in:image,character_scenario'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['nullable', 'string', 'max:255'],
            'criteria.*.max_score' => ['required', 'integer', 'min:1', 'max:100'],
            'criteria.*.tiebreak_order' => ['nullable', 'integer', 'min:1'],
            'special_prizes' => ['nullable', 'array'],
            'special_prizes.*.id' => ['nullable', 'integer'],
            'special_prizes.*.name' => ['required', 'string', 'max:255'],
            'special_prizes.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $orders = collect($this->input('criteria', []))
                ->pluck('tiebreak_order')
                ->filter();

            if ($orders->count() !== $orders->unique()->count()) {
                $validator->errors()->add('criteria', 'Tiebreak order values must be unique across criteria.');
            }
        });
    }
}
