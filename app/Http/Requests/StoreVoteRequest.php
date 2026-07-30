<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $contest = $this->route('contest');
        $rules = [];

        foreach ($contest->criteria as $criterion) {
            $rules["scores.{$criterion->id}"] = ['required', 'integer', 'min:0', "max:{$criterion->max_score}"];
        }

        $rules['comment'] = $contest->isCharacterScenario()
            ? ['required', 'string', 'max:2000']
            : ['nullable', 'string', 'max:2000'];

        return $rules;
    }
}
