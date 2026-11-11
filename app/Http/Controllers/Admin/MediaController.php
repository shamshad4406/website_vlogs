<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $media = MediaLibrary::with('uploader')
            ->when($request->filled('collection'), fn ($q) => $q->where('collection', $request->string('collection')))
            ->when($request->filled('q'), fn ($q) => $q->where('file_name', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $collections = MediaLibrary::selectRaw('DISTINCT collection')->orderBy('collection')->pluck('collection');

        return view('admin.media.index', [
            'media' => $media,
            'collections' => $collections,
            'totalSize' => (int) MediaLibrary::sum('file_size'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'max:10'],
            // SVG is excluded on purpose: an uploaded SVG can carry script.
            'files.*' => ['file', 'mimes:jpeg,jpg,png,webp,gif,pdf', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf', 'max:8192'],
            'collection' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9\-_]+$/i'],
        ]);

        $collection = $data['collection'] ?: 'general';
        $stored = 0;

        foreach ($request->file('files') as $file) {
            $path = $file->store('media/'.$collection, 'public');

            MediaLibrary::create([
                'uploaded_by' => $request->user()->id,
                'file_name' => mb_substr($file->getClientOriginalName(), 0, 190),
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'collection' => $collection,
            ]);

            $stored++;
        }

        ActivityLogger::log('media.upload', null, "Uploaded {$stored} file(s) to {$collection}");

        return back()->with('success', $stored.' file(s) uploaded.');
    }

    public function destroy(MediaLibrary $medium)
    {
        Storage::disk('public')->delete($medium->file_path);
        $name = $medium->file_name;
        $medium->delete();

        ActivityLogger::log('media.delete', null, 'Deleted media file '.$name);

        return back()->with('success', 'File deleted.');
    }
}
