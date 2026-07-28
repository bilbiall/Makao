<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\ActivityLog;
use Carbon\Carbon;

class Logs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Logs';
    protected static ?string $slug = 'logs';
    protected static ?string $navigationGroup = 'Analytics';
    protected static string $view = 'filament.pages.logs';

    /**
     * Role-based access: Caretaker cannot access Logs.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'manager']);
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'Unauthorized');
        }

        $this->log_action = request()->query('log_action');
        $this->log_search = request()->query('log_search');
        $this->log_from = request()->query('log_from');
        $this->log_to = request()->query('log_to');

        $this->buildLogs();
    }

    public function buildLogs(): void
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($this->log_action) {
            $query->where('action', $this->log_action);
        }

        if ($this->log_search) {
            $term = '%' . $this->log_search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('details', 'like', $term)
                  ->orWhereHas('user', function ($q2) use ($term) {
                      $q2->where('name', 'like', $term)
                         ->orWhere('email', 'like', $term);
                  });
            });
        }

        if ($this->log_from) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->log_from));
        }

        if ($this->log_to) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->log_to));
        }

        $this->logs = $query->limit(200)->get();
    }

    public function updatedLogAction(): void
    {
        $this->buildLogs();
    }

    public function updatedLogSearch(): void
    {
        $this->buildLogs();
    }

    public function updatedLogFrom(): void
    {
        $this->buildLogs();
    }

    public function updatedLogTo(): void
    {
        $this->buildLogs();
    }
}
