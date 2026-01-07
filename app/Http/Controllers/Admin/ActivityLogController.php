<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($value = $request->query('action')) {
            $query->where('action', $value);
        }

        if ($value = $request->query('model_type')) {
            $query->where('model_type', 'like', "%{$value}%");
        }

        if ($value = $request->query('model_id')) {
            $query->where('model_id', $value);
        }

        if ($value = $request->query('user_id')) {
            $query->where('user_id', $value);
        }

        if ($value = $request->query('date_from')) {
            try {
                $from = Carbon::parse($value)->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        if ($value = $request->query('date_to')) {
            try {
                $to = Carbon::parse($value)->endOfDay();
                $query->where('created_at', '<=', $to);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        $logs = $query->orderByDesc('created_at')->paginate(25);

        $filters = $request->only(['action', 'model_type', 'model_id', 'user_id', 'date_from', 'date_to']);

        return view('admin.activity_logs.index', compact('logs', 'filters'));
    }

    public function show($id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);

        return view('admin.activity_logs.show', compact('log'));
    }
}
