<?php

namespace App\Http\Requests\Creator;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('vlog'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],

            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:40'],

            'thumbnail' => [
                'nullable', 'image', 'mimes:jpeg,jpg,png,webp',
                'max:8192', 'dimensions:min_width=640,min_height=360',
            ],

            'location' => ['nullable', 'string', 'max:120'],
            'language' => ['required', 'in:en,hi,bn,ta,te,mr,gu,kn,ml,pa,other'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'allow_comments' => ['nullable', 'boolean'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('tags'))) {
            $this->merge([
                'tags' => collect(explode(',', $this->input('tags')))
                    ->map(fn ($t) => trim($t))
                    ->filter()
                    ->unique()
                    ->take(12)
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'thumbnail.dimensions' => 'The thumbnail must be at least 640x360 pixels (16:9 works best).',
        ];
    }
}
