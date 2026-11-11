<?php

namespace App\Http\Requests\Creator;

use App\Models\Vlog;
use App\Services\VideoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Vlog::class);
    }

    public function rules(): array
    {
        $maxSize = (int) config('video.max_size', 524288);
        $mimes = implode(',', config('video.mimes', ['mp4']));
        $mimeTypes = implode(',', config('video.mime_types', ['video/mp4']));

        $thumbnailRule = app(VideoService::class)->ffmpegAvailable() ? 'nullable' : 'required';

        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],

            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['string', 'max:40'],

            // Source: either an uploaded file or an external URL, not both.
            'source' => ['required', 'in:file,url'],
            'video_file' => [
                'required_if:source,file', 'nullable', 'file',
                'mimes:'.$mimes, 'mimetypes:'.$mimeTypes, 'max:'.$maxSize,
            ],
            'video_url' => ['required_if:source,url', 'nullable', 'url', 'max:500'],

            'thumbnail' => [
                $thumbnailRule, 'image', 'mimes:jpeg,jpg,png,webp',
                'max:8192', 'dimensions:min_width=640,min_height=360',
            ],

            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:'.config('video.max_duration_seconds', 3600)],
            'location' => ['nullable', 'string', 'max:120'],
            'language' => ['required', 'in:en,hi,bn,ta,te,mr,gu,kn,ml,pa,other'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'allow_comments' => ['nullable', 'boolean'],

            'action' => ['required', 'in:draft,review,schedule'],
            'scheduled_at' => ['required_if:action,schedule', 'nullable', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('source') === 'url' && $this->hasFile('video_file')) {
                $v->errors()->add('video_url', 'Choose either a file upload or an external link, not both.');
            }

            if ($this->input('source') === 'url' && ! app(VideoService::class)->ffmpegAvailable()
                && ! $this->hasFile('thumbnail')) {
                $v->errors()->add('thumbnail', 'A thumbnail is required for external videos.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'video_file.mimetypes' => 'That file does not look like a video. Allowed types: MP4, MOV, AVI, WebM, MKV.',
            'video_file.max' => 'The video must be '.round(config('video.max_size') / 1024).' MB or smaller.',
            'thumbnail.dimensions' => 'The thumbnail must be at least 640x360 pixels (16:9 works best).',
            'thumbnail.required' => 'A thumbnail is required because automatic thumbnails are not available on this server.',
            'scheduled_at.after' => 'Pick a publish time in the future.',
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
}
