<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advertisement;

class AdvertisementController extends Controller
{
    public function index()
    {
        $ads = Advertisement::latest()->paginate(20);
        return view('admin.advertisements.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.advertisements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image',
            'link' => 'required|url',
            'position' => 'required|in:homepage,sidebar,video_page,category_page',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $imagePath = $request->file('image')->store('advertisements', 'public');

        Advertisement::create([
            'title' => $request->title,
            'image' => $imagePath,
            'link' => $request->link,
            'position' => $request->position,
            'status' => 'active',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement created successfully.');
    }

    public function edit(Advertisement $advertisement)
    {
        return view('admin.advertisements.edit', compact('advertisement'));
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image',
            'link' => 'required|url',
            'position' => 'required|in:homepage,sidebar,video_page,category_page',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $data = [
            'title' => $request->title,
            'link' => $request->link,
            'position' => $request->position,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('advertisements', 'public');
        }

        $advertisement->update($data);

        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement updated successfully.');
    }

    public function destroy(Advertisement $advertisement)
    {
        $advertisement->delete();
        return redirect()->route('admin.advertisements.index')->with('success', 'Advertisement deleted successfully.');
    }
}
