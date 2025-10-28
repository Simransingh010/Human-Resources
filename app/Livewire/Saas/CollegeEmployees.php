<?php

namespace App\Livewire\Saas;

use App\Models\Saas\College;
use App\Models\Saas\CollegeEmployee;
use App\Models\Hrms\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Flux;

class CollegeEmployees extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $firmId = null;

    // Search and filter properties
    public $collegeSearch = '';
    public $employeeSearch = '';
    public $selectedCollegeId = null;
    public $selectedEmployeeIds = [];
    public $selectAll = false;
    public $showAssignedOnly = false;

    // Cache keys
    protected $collegeCacheKey = 'colleges_list';
    protected $employeeCacheKey = 'employees_list';
    protected $cacheTtl = 300; // 5 minutes

    // Field configuration
    public array $collegeFieldConfig = [
        'name' => ['label' => 'College Name', 'type' => 'text'],
        'code' => ['label' => 'Code', 'type' => 'text'],
        'city' => ['label' => 'City', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    public array $employeeFieldConfig = [
        'fname' => ['label' => 'First Name', 'type' => 'text'],
        'lname' => ['label' => 'Last Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
    ];

    public function mount($firmId = null)
    {
        $this->firmId = $firmId;
        $this->resetPage();
    }

    // Helper method to get the current firm ID
    protected function getCurrentFirmId()
    {
        return $this->firmId ?: Session::get('firm_id');
    }

    #[Computed]
    public function colleges()
    {
        return Cache::remember(
            $this->collegeCacheKey . '_' . $this->getCurrentFirmId(),
            $this->cacheTtl,
            fn() => College::query()
                ->where('firm_id', $this->getCurrentFirmId())
                ->when($this->collegeSearch, fn($query, $value) => 
                    $query->where(function($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('code', 'like', "%{$value}%")
                          ->orWhere('city', 'like', "%{$value}%");
                    }))
                ->orderBy('name', 'asc')
                ->get()
        );
    }

    #[Computed]
    public function employees()
    {
        $cacheKey = $this->employeeCacheKey . '_' . $this->getCurrentFirmId() . '_' . md5($this->employeeSearch . '_' . $this->showAssignedOnly);
        
        return Cache::remember(
            $cacheKey,
            $this->cacheTtl,
            fn() => Employee::query()
                ->where('firm_id', $this->getCurrentFirmId())
                ->when($this->employeeSearch, fn($query, $value) => 
                    $query->where(function($q) use ($value) {
                        $q->where('fname', 'like', "%{$value}%")
                          ->orWhere('lname', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%");
                    }))
                ->when($this->showAssignedOnly && $this->selectedCollegeId, function($query) {
                    $query->whereHas('collegeEmployees', function($q) {
                        $q->where('college_id', $this->selectedCollegeId);
                    });
                })
                ->orderBy('fname', 'asc')
                ->get()
        );
    }

    #[Computed]
    public function assignedEmployees()
    {
        if (!$this->selectedCollegeId) {
            return collect();
        }

        return Cache::remember(
            'assigned_employees_' . $this->selectedCollegeId,
            $this->cacheTtl,
            fn() => Employee::query()
                ->where('firm_id', $this->getCurrentFirmId())
                ->whereHas('collegeEmployees', function($query) {
                    $query->where('college_id', $this->selectedCollegeId);
                })
                ->orderBy('fname', 'asc')
                ->get()
        );
    }

    public function selectCollege($collegeId)
    {
        $this->selectedCollegeId = $collegeId;
        $this->selectedEmployeeIds = [];
        $this->selectAll = false;
        $this->resetPage();
        
        // Clear cache for assigned employees
        Cache::forget('assigned_employees_' . $collegeId);
    }

    public function toggleEmployee($employeeId)
    {
        if (in_array($employeeId, $this->selectedEmployeeIds)) {
            $this->selectedEmployeeIds = array_filter($this->selectedEmployeeIds, fn($id) => $id != $employeeId);
        } else {
            $this->selectedEmployeeIds[] = $employeeId;
        }
        
        $this->updateSelectAllState();
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedEmployeeIds = $this->employees->pluck('id')->toArray();
        } else {
            $this->selectedEmployeeIds = [];
        }
    }

    public function updateSelectAllState()
    {
        $this->selectAll = count($this->selectedEmployeeIds) === $this->employees->count() && $this->employees->count() > 0;
    }

    public function assignEmployees()
    {
        if (!$this->selectedCollegeId || empty($this->selectedEmployeeIds)) {
            Flux::toast(
                variant: 'error',
                heading: 'Validation Error',
                text: 'Please select a college and at least one employee.',
            );
            return;
        }

        try {
            DB::transaction(function () {
                $college = College::findOrFail($this->selectedCollegeId);
                
                // Remove existing assignments for this college
                CollegeEmployee::where('college_id', $this->selectedCollegeId)->delete();
                
                // Create new assignments
                $assignments = collect($this->selectedEmployeeIds)->map(function ($employeeId) {
                    return [
                        'college_id' => $this->selectedCollegeId,
                        'employee_id' => $employeeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
                
                CollegeEmployee::insert($assignments->toArray());
            });

            // Clear relevant caches
            $this->clearCaches();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Employees assigned to college successfully.',
            );

            $this->selectedEmployeeIds = [];
            $this->selectAll = false;

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to assign employees. Please try again.',
            );
        }
    }

    public function removeEmployee($employeeId)
    {
        if (!$this->selectedCollegeId) {
            return;
        }

        try {
            CollegeEmployee::where('college_id', $this->selectedCollegeId)
                          ->where('employee_id', $employeeId)
                          ->delete();

            // Clear relevant caches
            $this->clearCaches();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Employee removed from college successfully.',
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to remove employee. Please try again.',
            );
        }
    }

    public function clearAllAssignments()
    {
        if (!$this->selectedCollegeId) {
            return;
        }

        try {
            CollegeEmployee::where('college_id', $this->selectedCollegeId)->delete();

            // Clear relevant caches
            $this->clearCaches();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'All employees removed from college successfully.',
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to remove all employees. Please try again.',
            );
        }
    }

    public function clearCaches()
    {
        Cache::forget($this->collegeCacheKey . '_' . $this->getCurrentFirmId());
        Cache::forget($this->employeeCacheKey . '_' . $this->getCurrentFirmId());
        Cache::forget('assigned_employees_' . $this->selectedCollegeId);
        
        // Clear all employee cache variations
        $cachePattern = $this->employeeCacheKey . '_' . $this->getCurrentFirmId() . '_*';
        // Note: In a real application, you might want to use Redis or implement a more sophisticated cache clearing mechanism
    }

    public function clearFilters()
    {
        $this->collegeSearch = '';
        $this->employeeSearch = '';
        $this->showAssignedOnly = false;
        $this->selectedEmployeeIds = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function updatedCollegeSearch()
    {
        $this->resetPage();
    }

    public function updatedEmployeeSearch()
    {
        $this->resetPage();
    }

    public function updatedShowAssignedOnly()
    {
        $this->selectedEmployeeIds = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/college-employees.blade.php'), [
            'colleges' => $this->colleges,
            'employees' => $this->employees,
            'assignedEmployees' => $this->assignedEmployees,
        ]);
    }
}
