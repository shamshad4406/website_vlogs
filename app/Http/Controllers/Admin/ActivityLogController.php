<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AdminActivityLog::with('admin')
            ->when($request->filled('admin'), fn ($q) => $q->where('admin_id', $request->integer('admin')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->string('action').'%'))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')->endOfDay()))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $admins = User::whereHas('activityLogs')->orderBy('name')->get(['id', 'name']);

        $actions = AdminActivityLog::selectRaw('DISTINCT action')->orderBy('action')->pluck('action');

        return view('admin.activity-log', compact('logs', 'admins', 'actions'));
    }
}
