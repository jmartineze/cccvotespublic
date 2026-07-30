<?php

namespace App\Http\Requests;

use App\Models\Contest;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAnyAdmin();
    }

    public function rules(): array
    {
        return [
            'contest_id' => ['required', 'integer'],
            'discord_user' => ['required', 'string', 'max:255'],
            'character_name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'backstory' => ['required', 'string'],
            'scenario_description' => ['nullable', 'string'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Trans'])],
            'style' => ['required', Rule::in(['Anime', 'Realistic'])],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['max:10240', 'mimes:jpeg,jpg,png,gif,webp,mp4,webm,mov'],
        ];
    }

    public function withValidator($validator): void
    {
        $submission = $this->route('submission');

        $validator->after(function ($validator) use ($submission) {
            $contestId = $this->input('contest_id');
            $discordUser = $this->input('discord_user');
            $gender = $this->input('gender');

            $contest = $contestId ? Contest::find($contestId) : null;

            if ($contestId && ! $contest) {
                $validator->errors()->add('contest_id', 'Invalid contest.');
            }

            if ($contestId && $discordUser && $gender) {
                $exists = Submission::where('contest_id', $contestId)
                    ->where('discord_user', $discordUser)
                    ->where('gender', $gender)
                    ->where('id', '!=', $submission->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'discord_user',
                        "This Discord user already has a {$gender} submission in this contest."
                    );
                }
            }

            if ($contest && $contest->isCharacterScenario() && ! $this->filled('scenario_description')) {
                $validator->errors()->add('scenario_description', 'Scenario description is required for character/scenario contests.');
            }
        });
    }
}
