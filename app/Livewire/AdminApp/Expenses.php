<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Expense;
use App\Support\StaffPermissions;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Expenses extends Component
{
    use WithPagination;
    use ExportsCsv;

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $expense_month = '';
    public $electricity = '';
    public $water = '';
    public $internet = '';
    public $maintenance = '';
    public $other = '';
    public string $notes = '';

    public function canManageExpenses(): bool
    {
        // Same visibility as ExpenseResource - operational/financial data, not for
        // Manager/Caretaker/Agent, unless a custom staff role explicitly grants it.
        return in_array(Auth::user()->role, ['admin', 'landlord'])
            || Auth::user()->hasPermission(StaffPermissions::MANAGE_EXPENSES);
    }

    protected function rules(): array
    {
        return [
            'expense_month' => 'required|date',
            'electricity' => 'nullable|numeric|min:0',
            'water' => 'nullable|numeric|min:0',
            'internet' => 'nullable|numeric|min:0',
            'maintenance' => 'nullable|numeric|min:0',
            'other' => 'nullable|numeric|min:0',
        ];
    }

    public function startCreate(): void
    {
        abort_unless($this->canManageExpenses(), 403);

        $this->reset(['editingId', 'electricity', 'water', 'internet', 'maintenance', 'other', 'notes']);
        $this->expense_month = now()->startOfMonth()->toDateString();
        $this->showForm = true;
    }

    public function startEdit(int $expenseId): void
    {
        abort_unless($this->canManageExpenses(), 403);

        $expense = Expense::findOrFail($expenseId);

        $this->editingId = $expense->id;
        $this->expense_month = $expense->expense_month->toDateString();
        $this->electricity = $expense->electricity;
        $this->water = $expense->water;
        $this->internet = $expense->internet;
        $this->maintenance = $expense->maintenance;
        $this->other = $expense->other;
        $this->notes = $expense->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canManageExpenses(), 403);

        $this->validate();

        $data = [
            'expense_month' => $this->expense_month,
            'electricity' => $this->electricity !== '' ? $this->electricity : 0,
            'water' => $this->water !== '' ? $this->water : 0,
            'internet' => $this->internet !== '' ? $this->internet : 0,
            'maintenance' => $this->maintenance !== '' ? $this->maintenance : 0,
            'other' => $this->other !== '' ? $this->other : 0,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            Expense::findOrFail($this->editingId)->update($data);
            session()->flash('expense-saved', 'Expense updated.');
        } else {
            Expense::create($data);
            session()->flash('expense-saved', 'Expense recorded.');
        }

        $this->showForm = false;
    }

    public function delete(int $expenseId): void
    {
        abort_unless($this->canManageExpenses(), 403);

        Expense::findOrFail($expenseId)->delete();
        session()->flash('expense-saved', 'Expense deleted.');
    }

    public function export()
    {
        abort_unless($this->canManageExpenses(), 403);

        $expenses = Expense::orderByDesc('expense_month')->get();

        return $this->streamCsv(
            'expenses.csv',
            ['Month', 'Electricity', 'Water', 'Internet', 'Maintenance', 'Other', 'Total', 'Notes'],
            $expenses->map(fn (Expense $expense) => [
                $expense->expense_month->format('Y-m'),
                $expense->electricity,
                $expense->water,
                $expense->internet,
                $expense->maintenance,
                $expense->other,
                $expense->total(),
                $expense->notes,
            ])
        );
    }

    public function render()
    {
        $expenses = Expense::orderByDesc('expense_month')->paginate(15);

        return view('livewire.admin-app.expenses', ['expenses' => $expenses])
            ->layout('components.layouts.app', ['title' => 'Expenses']);
    }
}
