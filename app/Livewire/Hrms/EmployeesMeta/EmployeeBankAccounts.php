<?php

namespace App\Livewire\Hrms\EmployeesMeta;

use Livewire\Component;
use App\Models\Hrms\EmployeeBankAccount;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeBankAccounts extends Component
{
    use \Livewire\WithPagination;
    public Employee $employee;
    public array $bankAccountStatuses = [];

    public $bankAccountData = [
        'id' => null,
        'employee_id' => '',
        'bank_name' => '',
        'branch_name' => '',
        'address' => '',
        'ifsc' => '',
        'bankaccount' => '',
        'is_primary' => false,
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($employeeId)
    {
        session()->put('firm_id', session('firm_id'));
        $this->employee = Employee::findOrFail($employeeId);
        $this->bankAccountsList();
        $this->loadBankAccountStatuses();
    }

    private function loadBankAccountStatuses()
    {
        $this->bankAccountStatuses = EmployeeBankAccount::pluck('is_inactive', 'id')
            ->map(function ($status) {
                return !$status; // Invert the is_inactive value for the switch
            })
            ->toArray();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function bankAccountsList()
    {
        return EmployeeBankAccount::query()
            ->with('employee')
            ->where('employee_id', $this->employee->id)
            ->get();
    }


    public function fetchBankAccount($id)
    {
        $bankAccount = EmployeeBankAccount::findOrFail($id);
        $this->bankAccountData = $bankAccount->toArray();
        $this->isEditing = true;
        $this->modal('mdl-bank-account')->show();
    }

    public function saveBankAccount()
    {
        $validatedData = $this->validate([
            'bankAccountData.bank_name' => 'required|string|max:255',
            'bankAccountData.branch_name' => 'required|string|max:255',
            'bankAccountData.address' => 'nullable|string',
            'bankAccountData.ifsc' => 'required|string|max:20',
            'bankAccountData.bankaccount' => 'required|string|max:50',
            'bankAccountData.is_primary' => 'boolean',
            'bankAccountData.is_inactive' => 'boolean',
        ]);
        $validatedData['bankAccountData']['employee_id'] = $this->employee->id;
        if ($this->isEditing) {
            $bankAccount = EmployeeBankAccount::findOrFail($this->bankAccountData['id']);
            $bankAccount->update($validatedData['bankAccountData']);
            session()->flash('message', 'Bank account updated successfully.');
        } else {
            $validatedData['bankAccountData']['firm_id'] = session('firm_id');
            EmployeeBankAccount::create($validatedData['bankAccountData']);
            session()->flash('message', 'Bank account added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-bank-account')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Bank account details have been updated successfully.',
        );
    }

    public function resetForm()
    {
        $this->bankAccountData = [
            'id' => null,
            'employee_id' => '',
            'bank_name' => '',
            'branch_name' => '',
            'address' => '',
            'ifsc' => '',
            'bankaccount' => '',
            'is_primary' => false,
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function update_rec_status($bankAccountId)
    {
        $bankAccount = EmployeeBankAccount::find($bankAccountId);
        if ($bankAccount) {
            $bankAccount->is_inactive = !$this->bankAccountStatuses[$bankAccountId];
            $bankAccount->save();
            Flux::toast(
                heading: 'Changes saved.',
                text: 'Bank Account Status Changed',
            );
        }
    }

    public function toggleStatus($accountId)
    {
        $bankAccount = EmployeeBankAccount::findOrFail($accountId);
        $bankAccount->is_inactive = !$bankAccount->is_inactive;
        $bankAccount->save();

        // Update the status in the local array
        $this->bankAccountStatuses[$accountId] = !$bankAccount->is_inactive;

        // Show toast notification with correct syntax
        Flux::toast(
            heading: 'Status Updated',
            text: $bankAccount->is_inactive ? 'Bank account has been deactivated.' : 'Bank account has been activated.'
        );
    }

    public function deleteBankAccount($accountId)
    {
        $bankAccount = EmployeeBankAccount::findOrFail($accountId);
        $employeeName = $bankAccount->employee->fname . ' ' . $bankAccount->employee->lname;

        // Delete the bank account
        $bankAccount->delete();

        // Show toast notification
        Flux::toast(
            heading: 'Bank Account Deleted',
            text: "Bank account for {$employeeName} has been deleted successfully."
        );
    }

//    public function render()
//    {
//        $this->loadBankAccountStatuses();
//        return view('livewire.hrms.employees-meta.employee-bank-accounts', [
//            'employees' => $this->employeesList
//        ]);
//    }
}