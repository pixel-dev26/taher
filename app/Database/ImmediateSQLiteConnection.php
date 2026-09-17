<?php

namespace App\Database;

use Illuminate\Database\SQLiteConnection;
use Throwable;

/**
 * SQLite starts every transaction as a reader and only takes the write lock
 * at the first write statement. Two staff saving at the same moment would
 * therefore both read (both see the stock as available, both compute the
 * same next document number), and then the second one to write fails
 * outright with "database is locked" — busy_timeout can't help, because
 * waiting would deadlock two readers each wanting to upgrade.
 *
 * Taking the write lock at the start of every transaction (what SQLite calls
 * BEGIN IMMEDIATE) makes the second writer queue behind the first, so reads
 * inside a transaction are authoritative and lockForUpdate() re-checks
 * actually mean something on SQLite.
 */
class ImmediateSQLiteConnection extends SQLiteConnection
{
    protected function createTransaction()
    {
        if ($this->transactions == 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->getPdo()->beginTransaction();
                $this->acquireWriteLock();
            } catch (Throwable $e) {
                $this->handleBeginTransactionException($e);
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->createSavepoint();
        }
    }

    /**
     * PDO only knows about a transaction it began itself, so rather than a
     * literal BEGIN IMMEDIATE (which would leave commit() complaining there
     * is no active transaction) the lock is taken by a write statement that
     * touches nothing — SQLite escalates to the RESERVED lock when a write
     * statement starts, regardless of how many rows it affects.
     */
    private function acquireWriteLock(): void
    {
        try {
            $this->getPdo()->exec('UPDATE migrations SET id = id WHERE 0');
        } catch (Throwable) {
            // A database without a migrations table yet (first install) just
            // falls back to the standard deferred behaviour.
        }
    }
}
