<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SanitizesFilters;
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
    use SanitizesFilters;

    public function index(Request $request)
    {
        $filters = $this->filters($request, [
            'user_id' => 'nullable|integer',
            'subject_type' => 'nullable|string|max:100',
            'action' => 'nullable|string|max:30',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        // Several rows are routinely written within the same second (a
        // document plus its HSN backfill, a settings save), so the id is the
        // tiebreaker — without it pages could repeat or skip rows.
        $query = ActivityLog::with('user')->latest('created_at')->latest('id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $from = $filters['date_from'] ?? today()->subDays(30)->format('Y-m-d');
        $to = $filters['date_to'] ?? null;

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
