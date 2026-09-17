<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Admin-only (see the 'admin' middleware on this route). Every create,
 * update, login and (if it ever happens) delete across the app's business
 * records, written by App\Models\Concerns\LogsActivity.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $from = $request->get('date_from', today()->subDays(30)->format('Y-m-d'));
        $to = $request->get('date_to');

        $query->whereDate('created_at', '>=', $from);

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $entries = $query->paginate(50)->withQueryString();

        $users = User::orderBy('name')->get();

        // Distinct subject types actually logged, shown by their short name
        // (e.g. "Sku" not "App\Models\Sku") but filtered by the real value.
        $subjectTypes = ActivityLog::query()
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        return view('activity-log.index', compact('entries', 'users', 'subjectTypes', 'from', 'to'));
    }
}
