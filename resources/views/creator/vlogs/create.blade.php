@extends('layouts.creator')
@section('title', 'Upload a vlog')

@section('content')
<div class="mx-auto max-w-3xl"
     x-data="uploadWizard({ maxSizeMb: {{ $maxSizeMb }}, redirectUrl: @js(route('creator.vlogs.index')) })">

    {{-- Steps --}}
    <ol class="mb-8 flex items-center gap-2 text-sm">
        @foreach (['Upload', 'Details', 'Review and publish'] as $i => $label)
            <li class="flex flex-1 items-center gap-2">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold transition"
                      :class="step > {{ $i + 1 }}
                        ? 'bg-emerald-600 text-white'
                        : (step === {{ $i + 1 }} ? 'bg-brand-700 text-white' : 'bg-ink-200 text-ink-500 dark:bg-white/10 dark:text-ink-400')">
                    <template x-if="step > {{ $i + 1 }}"><span>&check;</span></template>
                    <template x-if="step <= {{ $i + 1 }}"><span>{{ $i + 1 }}</span></template>
                </span>
                <span class="hidden font-medium sm:block"
                      :class="step === {{ $i + 1 }} ? 'text-ink-900 dark:text-white' : 'text-ink-500 dark:text-ink-400'">
                    {{ $label }}
                </span>
                @unless ($loop->last)
                    <span class="h-px flex-1 bg-ink-200 dark:bg-white/10"></span>
                @endunless
            </li>
        @endforeach
    </ol>

    @unless ($ffmpegReady)
        <div class="mb-6 rounded-lg border border-sky-300 bg-sky-50 p-4 text-sm dark:border-sky-500/30 dark:bg-sky-500/10">
            <p class="font-semibold text-sky-900 dark:text-sky-200">FFmpeg is not installed on this server</p>
            <p class="mt-1 text-sky-800 dark:text-sky-300">
                Uploads still work and play normally. What you do not get: automatic thumbnails and adaptive
                quality. So a thumbnail is required below, and the duration is read in your browser.
            </p>
        </div>
    @endunless

    <form x-ref="form" method="POST" action="{{ route('creator.vlogs.store') }}"
          enctype="multipart/form-data" @submit.prevent>
        @csrf
        <input type="hidden" name="action" value="review">
        <input type="hidden" name="duration_seconds" :value="durationSeconds">

        {{-- Error from the wizard --}}
        <template x-if="error">
            <p class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
               x-text="error"></p>
        </template>

        {{-- STEP 1: the file --}}
        <section x-show="step === 1" class="panel space-y-5">
            <div class="flex gap-2 rounded-lg bg-ink-100 p-1 text-sm dark:bg-white/10">
                <button type="button" @click="source = 'file'"
                        class="flex-1 rounded-md px-3 py-2 font-medium transition"
                        :class="source === 'file' ? 'bg-white shadow-sm dark:bg-ink-900' : 'text-ink-500'">
                    Upload a file
                </button>
                <button type="button" @click="source = 'url'"
                        class="flex-1 rounded-md px-3 py-2 font-medium transition"
                        :class="source === 'url' ? 'bg-white shadow-sm dark:bg-ink-900' : 'text-ink-500'">
                    Link a video
                </button>
            </div>
            <input type="hidden" name="source" :value="source">

            {{-- File --}}
            <div x-show="source === 'file'">
                <div @dragover.prevent @drop.prevent="onDrop($event)"
                     class="rounded-card border-2 border-dashed border-ink-300 p-8 text-center transition hover:border-brand-500 dark:border-white/15">
                    <template x-if="! fileName">
                        <div>
                            <x-icon name="upload" class="mx-auto mb-3 h-10 w-10 text-ink-400" />
                            <p class="font-semibold">Drag a video here, or choose a file</p>
                            <p class="mt-1 text-sm text-ink-500 dark:text-ink-400">
                                MP4, MOV, AVI, WebM or MKV, up to {{ $maxSizeMb }} MB
                            </p>
                            <button type="button" @click="$refs.videoInput.click()" class="btn-primary mt-4">
                                Choose file
                            </button>
                        </div>
                    </template>

                    <template x-if="fileName">
                        <div class="text-left">
                            <video x-show="videoPreview" :src="videoPreview" controls
                                   class="mb-4 w-full rounded-lg bg-black" style="aspect-ratio: 16/9"></video>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold" x-text="fileName"></p>
                                    <p class="text-sm text-ink-500 dark:text-ink-400">
                                        <span x-text="fileSizeLabel"></span>
                                        <template x-if="durationLabel">
                                            <span> · <span x-text="durationLabel"></span></span>
                                        </template>
                                    </p>
                                </div>
                                <button type="button" @click="$refs.videoInput.click()" class="btn-secondary btn-sm">
                                    Choose another
                                </button>
                            </div>
                        </div>
                    </template>

                    <input x-ref="videoInput" type="file" name="video_file" accept="video/*"
                           @change="pickVideo($event)" class="hidden">
                </div>

                <x-input-error :messages="$errors->get('video_file')" class="mt-2" />
            </div>

            {{-- URL --}}
            <div x-show="source === 'url'" x-cloak>
                <label for="video_url" class="field-label">Video link</label>
                <input x-ref="videoUrl" id="video_url" type="url" name="video_url" value="{{ old('video_url') }}"
                       class="field" placeholder="https://www.youtube.com/watch?v=...">
                <p class="field-help">YouTube and Vimeo links are embedded with a privacy-friendly player.</p>
                <x-input-error :messages="$errors->get('video_url')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <button type="button" @click="next()" class="btn-primary">
                    Continue <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </div>
        </section>

        {{-- STEP 2: details --}}
        <section x-show="step === 2" x-cloak class="panel space-y-5">
            <div>
                <label for="title" class="field-label">Title</label>
                <input x-ref="title" id="title" type="text" name="title" value="{{ old('title') }}"
                       required minlength="5" maxlength="150" class="field"
                       placeholder="Spiti Valley alone in October: what nobody tells you">
                <p class="field-help">Say what the vlog actually shows. Clickbait gets rejected.</p>
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <label for="description" class="field-label">Description</label>
                <textarea x-ref="description" id="description" name="description" rows="6"
                          required minlength="20" maxlength="5000" class="field"
                          placeholder="What is in the vlog, where it was filmed, and anything a viewer needs to know.">{{ old('description') }}</textarea>
                <p class="field-help">At least 20 characters. This is also what search engines read.</p>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category_id" class="field-label">Category</label>
                    <select id="category_id" name="category_id" required class="field">
                        <option value="">Choose one</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>

                <div>
                    <label for="language" class="field-label">Language</label>
                    <select id="language" name="language" required class="field">
                        @foreach ($languages as $code => $label)
                            <option value="{{ $code }}" @selected(old('language', 'en') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="location" class="field-label">Place (optional)</label>
                <input id="location" type="text" name="location" value="{{ old('location') }}"
                       maxlength="120" class="field" placeholder="Spiti Valley, Himachal Pradesh">
                <p class="field-help">Used for place pages and search.</p>
            </div>

            {{-- Tags --}}
            <div x-data="tagInput({{ Illuminate\Support\Js::from(old('tags', [])) }})">
                <label for="tag-draft" class="field-label">Tags</label>
                <div class="field flex flex-wrap items-center gap-2 py-2">
                    <template x-for="tag in tags" :key="tag">
                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-800 dark:bg-brand-500/20 dark:text-brand-200">
                            <span x-text="tag"></span>
                            <button type="button" @click="remove(tag)" class="opacity-60 hover:opacity-100" aria-label="Remove tag">&times;</button>
                            <input type="hidden" name="tags[]" :value="tag">
                        </span>
                    </template>

                    <input id="tag-draft" type="text" x-model="draft" @keydown="onKey($event)" @blur="add()"
                           class="min-w-32 flex-1 border-0 bg-transparent p-0 text-sm focus:ring-0"
                           placeholder="Type a tag and press Enter">
                </div>
                <p class="field-help">Up to 12. Popular right now:
                    @foreach ($popularTags->take(6) as $tag)
                        <button type="button" @click="draft = @js($tag->name); add()"
                                class="font-medium text-brand-700 hover:underline dark:text-brand-300">{{ $tag->name }}</button>@unless($loop->last), @endunless
                    @endforeach
                </p>
            </div>

            {{-- Thumbnail --}}
            <div>
                <label for="thumbnail" class="field-label">
                    Thumbnail {{ $ffmpegReady ? '(optional)' : '(required)' }}
                </label>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="thumb w-48 shrink-0 bg-ink-100 dark:bg-white/5">
                        <template x-if="thumbPreview">
                            <img :src="thumbPreview" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="! thumbPreview">
                            <span class="grid h-full w-full place-items-center text-ink-400">
                                <x-icon name="image" class="h-7 w-7" />
                            </span>
                        </template>
                    </div>

                    <div class="min-w-48 flex-1">
                        <input id="thumbnail" type="file" name="thumbnail" accept="image/*"
                               @change="pickThumbnail($event)" @if (! $ffmpegReady) required @endif
                               class="field file:mr-3 file:rounded file:border-0 file:bg-brand-700 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                        <p class="field-help">16:9, at least 1280x720. A clear thumbnail is the single biggest factor in whether people click.</p>
                        <x-input-error :messages="$errors->get('thumbnail')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <button type="button" @click="back()" class="btn-secondary">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Back
                </button>
                <button type="button" @click="next()" class="btn-primary">
                    Continue <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </div>
        </section>

        {{-- STEP 3: visibility and publish --}}
        <section x-show="step === 3" x-cloak class="panel space-y-5">
            <div>
                <span class="field-label">Who can see it</span>
                <div class="space-y-2">
                    @foreach ([
                        ['public', 'Public', 'Anyone can find it, and it appears in the feed and in search.'],
                        ['unlisted', 'Unlisted', 'Only people with the link can watch. It stays out of the feed and search.'],
                        ['private', 'Private', 'Only you and the moderation team can open it.'],
                    ] as [$value, $label, $help])
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 text-sm transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 dark:border-white/10 dark:has-[:checked]:bg-brand-500/10">
                            <input type="radio" name="visibility" value="{{ $value }}"
                                   @checked(old('visibility', 'public') === $value)
                                   class="mt-0.5 border-ink-300 text-brand-700 focus:ring-brand-500">
                            <span>
                                <span class="font-semibold">{{ $label }}</span>
                                <span class="block text-xs text-ink-500 dark:text-ink-400">{{ $help }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm">
                <input type="checkbox" name="allow_comments" value="1" checked
                       class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                Allow comments on this vlog
            </label>

            <div x-data="{ schedule: false }">
                <label class="flex cursor-pointer items-center gap-3 text-sm">
                    <input type="checkbox" x-model="schedule"
                           class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                    Schedule the publish time
                </label>

                <div x-show="schedule" x-cloak class="mt-3">
                    <label for="scheduled_at" class="field-label">Publish at</label>
                    <input id="scheduled_at" type="datetime-local" name="scheduled_at"
                           min="{{ now()->addMinutes(10)->format('Y-m-d\TH:i') }}" class="field sm:max-w-xs">
                    <p class="field-help">
                        It still goes through review first. Once approved it publishes automatically at this time.
                    </p>
                    <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                </div>

                <div class="mt-6 rounded-lg bg-ink-100 p-4 text-sm dark:bg-white/5">
                    <p class="font-semibold">What happens next</p>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-ink-600 dark:text-ink-300">
                        <li>Your vlog goes into the review queue with status <strong>In review</strong>.</li>
                        <li>A person checks it, usually within 24 hours.</li>
                        <li>It is published, or returned with a specific reason you can fix and resubmit.</li>
                    </ol>
                </div>

                {{-- Upload progress --}}
                <div x-show="uploading" x-cloak class="mt-5">
                    <div class="mb-2 flex justify-between text-sm font-medium">
                        <span>Uploading, keep this tab open</span>
                        <span class="tabular-nums" x-text="progress + '%'"></span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-ink-200 dark:bg-white/10">
                        <div class="h-full rounded-full bg-brand-700 transition-all" :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-between gap-3">
                    <button type="button" @click="back()" class="btn-secondary" :disabled="uploading">
                        <x-icon name="chevron-left" class="h-4 w-4" /> Back
                    </button>

                    <span class="flex gap-2">
                        <button type="button" @click="submit('draft')" class="btn-secondary" :disabled="uploading">
                            Save as draft
                        </button>
                        <button type="button" @click="submit(schedule ? 'schedule' : 'review')"
                                class="btn-primary" :disabled="uploading">
                            <x-icon name="check" class="h-4 w-4" />
                            <span x-text="uploading ? 'Uploading…' : 'Submit for review'"></span>
                        </button>
                    </span>
                </div>
            </div>
        </section>
    </form>
</div>
@endsection
