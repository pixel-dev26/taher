<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/**
 * Records every create/update/delete on a model to the Activity Log,
 * Admin-only and append-only. Applied to business-record models (SKUs,
 * Godowns, GRNs, Dispatch Sheets, Transfers, Adjustments, Settings, Users) —
 * not their line-item child tables directly (see logCreatedWithItems() /
 * logChange() below for how a parent document's items still end up in the
 * log) and not stock_ledger, which is already its own append-only,
 * timestamped, attributed trail.
 *
 * A model using this trait may override:
 *   activityLogLabel(): string             human-readable reference, e.g. a
 *                                           document number or product code
 *                                           (default: the record's id)
 *   $activityLogHidden: array<string>      attribute names to always redact
 *                                           (default: none)
 *   activityLogShouldRedact(field): bool   for value-aware redaction an EAV
 *                                           row (see Setting) needs, where
 *                                           whether to hide a value depends
 *                                           on another column, not the
 *                                           field's name alone
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->recordActivity('created', null, $model->activityLogAttributes());
        });

        static::updated(function ($model) {
            $changedKeys = collect($model->getChanges())->keys()->diff(['created_at', 'updated_at']);

            if ($changedKeys->isEmpty()) {
                return;
            }

            $before = [];
            $after = [];

            foreach ($changedKeys as $key) {
                if ($model->activityLogShouldRedact($key)) {
                    $before[$key] = '[hidden]';
                    $after[$key] = '[hidden]';
                } else {
                    $before[$key] = $model->getOriginal($key);
                    $after[$key] = $model->getAttribute($key);
                }
            }

            $model->recordActivity('updated', $before, $after);
        });

        static::deleted(function ($model) {
            $model->recordActivity('deleted', $model->activityLogAttributes(), null);
        });
    }

    protected function recordActivity(string $action, ?array $before, ?array $after): void
    {
        $user = auth()->user();

        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'subject_label' => $this->activityLogLabel(),
            'changes' => array_filter(['before' => $before, 'after' => $after], fn ($v) => $v !== null),
        ]);
    }

    /**
     * For a parent/line-item document (GRN, Dispatch Sheet, Transfer,
     * Adjustment): the child rows are inserted in a follow-up step after the
     * parent row, so the automatic 'created' hook above fires too early to
     * see them — it would only ever capture the header columns. The
     * controller instead wraps its Model::create() call in
     * Model::withoutEvents() to suppress that premature entry, then calls
     * this once the full record (header + items) exists, so exactly one log
     * entry captures both. $extra is merged onto the header snapshot —
     * typically ['items' => 'GIP-001 x 40, GIP-002 x 20'].
     */
    public function logCreatedWithItems(array $extra = []): void
    {
        $this->recordActivity('created', null, $this->activityLogAttributes() + $extra);
    }

    /**
     * Same idea as logCreatedWithItems(), for an edit that swaps a
     * document's items (e.g. DispatchSheetController::update() deletes and
     * recreates every line). $before/$after are full snapshots (header
     * fields the caller cares about, plus an 'items' summary) rather than a
     * diff — simpler to build correctly than reconciling two diff sources,
     * and logged only when they actually differ.
     */
    public function logChange(string $action, ?array $before, ?array $after): void
    {
        if ($before === $after) {
            return;
        }

        $this->recordActivity($action, $before, $after);
    }

    /** Everything the automatic hooks would capture, for a caller building a manual entry. */
    public function activityLogSnapshot(): array
    {
        return $this->activityLogAttributes();
    }

    /** Every real column, redacted where the model asks for it. */
    protected function activityLogAttributes(): array
    {
        $attributes = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (in_array($key, ['created_at', 'updated_at'], true)) {
                continue;
            }

            $attributes[$key] = $this->activityLogShouldRedact($key) ? '[hidden]' : $value;
        }

        return $attributes;
    }

    /**
     * Whether this field's value should be hidden from the log. Default is
     * a fixed list of column names ($activityLogHidden — e.g. User's
     * password). A model where sensitivity depends on another column's
     * value (Setting, an EAV key/value row) overrides this instead.
     */
    protected function activityLogShouldRedact(string $field): bool
    {
        return in_array($field, $this->activityLogHidden ?? [], true);
    }

    public function activityLogLabel(): string
    {
        return (string) $this->getKey();
    }
}
