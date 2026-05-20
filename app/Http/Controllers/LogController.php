<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class LogController extends Controller
{
    public function showLogtView(Request $request)
    {
        Gate::authorize('view-activity-logs');

        $user = Auth::user();
        $logs = ActivityLog::latest()->get();

        return view('activity_log_table', compact('logs', 'user'));
    }
}
