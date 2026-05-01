<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EpisodeDuplicateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'episode_uuid' => ['required', 'uuid', 'exists:episodes,episode_uuid'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['episode_uuid' => $this->route('episode_uuid')]);
    }
}
