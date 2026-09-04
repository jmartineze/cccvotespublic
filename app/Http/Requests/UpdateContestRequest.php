<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->actingAsAdmin();
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', 'in:draft,active,closed'],
            'contest_type' => ['required', 'in:image,character_scenario'],
            // Special prizes stay editable at any point in the contest.
            'special_prizes' => ['nullable', 'array'],
            'special_prizes.*.id' => ['nullable', 'integer'],
            'special_prizes.*.name' => ['required', 'string', 'max:255'],
            'special_prizes.*.description' => ['nullable', 'string', 'max:500'],
        ];

        if (! $this->route('contest')->hasVotes()) {
            $rules['criteria'] = ['required', 'array', 'min:1'];
            $rules['criteria.*.name'] = ['required', 'string', 'max:255'];
            $rules['criteria.*.description'] = ['nullable', 'string', 'max:255'];
            $rules['criteria.*.max_score'] = ['required', 'integer', 'min:1', 'max:100'];
            $rules['criteria.*.tiebreak_order'] = ['nullable', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->route('contest')->hasVotes()) {
                return;
            }

            $orders = collect($this->input('criteria', []))
                ->pluck('tiebreak_order')
                ->filter();

            if ($orders->count() !== $orders->unique()->count()) {
                $validator->errors()->add('criteria', 'Tiebreak order values must be unique across criteria.');
            }
        });
    }
}
