<?php

namespace App\Livewire\Hrms\Leave\EmpLeaveBalance;

use App\Models\Hrms\EmpLeaveTransaction;
use App\Models\Hrms\EmpLeaveBalance;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpLeaveTransactions extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $balaId;

    // Field configuration for form and table
    public array $fieldConfig = [
        'leave_balance_id' => ['label' => 'Leave Balance', 'type' => 'select', 'listKey' => 'leave_balances', 'showInForm' => true],
        'transaction_type' => ['label' => 'Transaction Type', 'type' => 'select', 'listKey' => 'transaction_types', 'showInForm' => true],
        'transaction_date' => ['label' => 'Transaction Date', 'type' => 'datetime-local', 'showInForm' => true],
        'amount' => ['label' => 'Amount', 'type' => 'number', 'showInForm' => true],
        'reference_id' => ['label' => 'Reference ID', 'type' => 'number', 'showInForm' => true],
        'created_by' => ['label' => 'Created By', 'type' => 'select', 'listKey' => 'users', 'showInForm' => false],
        'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'showInForm' => false],
        'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'showInForm' => false],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'transaction_type' => ['label' => 'Transaction Type', 'type' => 'select', 'listKey' => 'transaction_types'],
        'transaction_date' => ['label' => 'Transaction Date', 'type' => 'date'],
        'amount' => ['label' => 'Amount', 'type' => 'number'],
        'created_by' => ['label' => 'Created By', 'type' => 'select', 'listKey' => 'users'],
        'created_at' => ['label' => 'Created At', 'type' => 'date'],
        'updated_at' => ['label' => 'Updated At', 'type' => 'date'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    const TRANSACTION_TYPES = [
        'CREDIT' => 'Credit',
        'DEBIT' => 'Debit',
        'LAPSE' => 'Lapse',
        'CARRY_FORWARD' => 'Carry Forward'
    ];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leave_balance_id' => null,
        'transaction_type' => '',
        'transaction_date' => '',
        'amount' => 0,
        'reference_id' => null,
        'created_by' => null,
    ];

    protected function rules()
    {
        return [
            'formData.leave_balance_id' => 'required|integer|exists:emp_leave_balances,id',
            'formData.transaction_type' => 'required|string',
            'formData.transaction_date' => 'required|date',
            'formData.amount' => 'required|numeric|min:0',
            'formData.reference_id' => 'nullable|integer',
            'formData.created_by' => 'nullable|integer|exists:users,id',
        ];
    }

    public function mount($balaId)
    {
        $this->balaId = $balaId;
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['transaction_type', 'transaction_date', 'amount', 'reference_id', 'created_by'];
        $this->visibleFilterFields = ['transaction_type', 'transaction_date', 'amount', 'created_by'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        if ($this->balaId) {
            $this->formData['leave_balance_id'] = $this->balaId;
        }
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_balances'] = EmpLeaveBalance::where('firm_id', session('firm_id'))
            ->pluck('id', 'id');
        $this->listsForFields['transaction_types'] = self::TRANSACTION_TYPES;
        $this->listsForFields['users'] = User::whereHas('firms', function($query) {
                $query->where('firms.id', session('firm_id'));
            })
            ->pluck('name', 'id');
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        return EmpLeaveTransaction::query()
            ->with(['user'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->balaId, fn($query) => 
                $query->where('leave_balance_id', $this->balaId))
            ->when($this->filters['transaction_type'], fn($query, $value) => 
                $query->where('transaction_type', $value))
            ->when($this->filters['transaction_date'], fn($query, $value) => 
                $query->whereDate('transaction_date', $value))
            ->when($this->filters['amount'], fn($query, $value) => 
                $query->where('amount', $value))
            ->when($this->filters['created_by'], fn($query, $value) => 
                $query->where('created_by', $value))
            ->when($this->filters['created_at'], fn($query, $value) => 
                $query->whereDate('created_at', $value))
            ->when($this->filters['updated_at'], fn($query, $value) => 
                $query->whereDate('updated_at', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        $validatedData['formData']['created_by'] = auth()->id();

        if ($this->isEditing) {
            $transaction = EmpLeaveTransaction::findOrFail($this->formData['id']);
            $transaction->update($validatedData['formData']);
            $toastMsg = 'Leave transaction updated successfully';
        } else {
            EmpLeaveTransaction::create($validatedData['formData']);
            $toastMsg = 'Leave transaction added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-transaction')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['amount'] = 0;
        if ($this->balaId) {
            $this->formData['leave_balance_id'] = $this->balaId;
        }
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $transaction = EmpLeaveTransaction::findOrFail($id);
        $this->formData = $transaction->toArray();
        if ($this->formData['transaction_date']) {
            $this->formData['transaction_date'] = date('Y-m-d\TH:i', strtotime($this->formData['transaction_date']));
        }
        $this->modal('mdl-leave-transaction')->show();
    }

    public function delete($id)
    {
        $transaction = EmpLeaveTransaction::findOrFail($id);
        $transaction->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave transaction has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/EmpLeaveBalance/blades/emp-leave-transactions.blade.php'));
    }
} 