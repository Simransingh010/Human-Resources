<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\EmployeeAddress;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeAddresses extends Component
{
    use \Livewire\WithPagination;
    
    public array $addressStatuses = [];
    
    public $addressData = [
        'id' => null,
        'employee_id' => '',
        'country' => '',
        'state' => '',
        'city' => '',
        'town' => '',
        'postoffice' => '',
        'village' => '',
        'pincode' => '',
        'address' => '',
        'is_primary' => false,
        'is_permanent' => false,
        'is_inactive' => false,
    ];
    protected $listeners = ['modalClosed' => 'resetForm'];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3); // Similar to Employees component
        $this->loadAddressStatuses();
    }

    private function loadAddressStatuses()
    {
        $this->addressStatuses = EmployeeAddress::pluck('is_inactive', 'id')
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
    public function addresseslist()
    {
        return EmployeeAddress::query()
            ->with('employee')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function employeesList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname . ' (' . $employee->email . ')'
                ];
            });
    }

    public function fetchAddress($id)
    {
        $address = EmployeeAddress::findOrFail($id);
        $this->addressData = $address->toArray();
        $this->isEditing = true;
        $this->modal('mdl-address')->show();
    }

    public function saveAddress()
    {
        $validatedData = $this->validate([
            'addressData.employee_id' => 'required|exists:employees,id',
            'addressData.country' => 'required|string|max:255',
            'addressData.state' => 'required|string|max:255',
            'addressData.city' => 'required|string|max:255',
            'addressData.town' => 'nullable|string|max:255',
            'addressData.postoffice' => 'nullable|string|max:255',
            'addressData.village' => 'nullable|string|max:255',
            'addressData.pincode' => 'required|string|max:10',
            'addressData.address' => 'required|string',
            'addressData.is_primary' => 'boolean',
            'addressData.is_permanent' => 'boolean',
            'addressData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $address = EmployeeAddress::findOrFail($this->addressData['id']);
            $address->update($validatedData['addressData']);
            session()->flash('message', 'Address updated successfully.');
        } else {
            $validatedData['addressData']['firm_id'] = session('firm_id');
            EmployeeAddress::create($validatedData['addressData']);
            session()->flash('message', 'Address added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-address')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Address details have been updated successfully.',
        );
    }

    public function resetForm()
    {
        $this->addressData = [
            'id' => null,
            'employee_id' => '',
            'country' => '',
            'state' => '',
            'city' => '',
            'town' => '',
            'postoffice' => '',
            'village' => '',
            'pincode' => '',
            'address' => '',
            'is_primary' => false,
            'is_permanent' => false,
            'is_inactive' => false,

        ];

        $this->isEditing = false;
    }

    public function update_rec_status($addressId)
    {
        $address = EmployeeAddress::find($addressId);
        if ($address) {
            $address->is_inactive = !$address->is_inactive;
            $address->save();

            // Update the status in the local array
            $this->addressStatuses[$addressId] = !$address->is_inactive;

            Flux::toast(
                heading: 'Status Updated',
                text: $address->is_inactive ? 'Address has been deactivated.' : 'Address has been activated.'
            );
        }
    }

    public function deleteAddress($addressId)
    {
        $address = EmployeeAddress::find($addressId);
        if ($address) {
            $address->delete();
            Flux::toast(
                heading: 'Address Deleted',
                text: 'The address has been deleted successfully.',
            );
        }
        $this->modal('delete-address-' . $addressId)->close();
    }

    public function render()
    {
        $this->loadAddressStatuses();
        return view('livewire.hrms.onboard.employee-addresses', [
            'employees' => $this->employeesList
        ]);
    }
} 