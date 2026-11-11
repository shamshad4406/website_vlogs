<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],

            'username' => [
                'nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:190',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            'bio' => ['nullable', 'string', 'max:500'],

            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096', 'dimensions:min_width=64,min_height=64'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Your username may use lowercase letters, numbers and underscores only.',
            'avatar.max' => 'The avatar must be 4 MB or smaller.',
        ];
    }
}
