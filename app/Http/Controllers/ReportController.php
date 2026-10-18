<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function activity(Request $request): View
    {
        $activities = ActivityLog::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($filter) => $filter->where('action', 'like', $term)
                    ->orWhere('subject_type', 'like', $term)
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', $term)));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.activity', compact('activities'));
    }
}
