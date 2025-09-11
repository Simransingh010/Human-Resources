<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewOverrideComponents extends Component
{
    public $payrollSlotId;
    public $slot;
    public $salary_execution_group_id;
    public $searchName = '';
    
    // Reset confirmation properties
    public $resetConfirmation = '';
    public $resetType = ''; // 'individual' or 'bulk'
    public $resetTargetId = null; // employee_id for individual, null for bulk
    public $resetReason = '';
    
    // Component selection properties
    public $selectedComponents = [];
    public $selectAllComponents = false;
    
    // Cache keys
    protected $cacheKey;
    protected $cacheDuration = 300; // 5 minutes
    
    // Validation rules
    protected $rules = [
        'resetConfirmation' => 'required|in:CANCEL',
    ];
    
    protected $messages = [
        'resetConfirmation.required' => 'Please type CANCEL to confirm.',
        'resetConfirmation.in' => 'Please type CANCEL in capital letters exactly.',

    ];
    
    // Data collections
    public $employees = [];
    public $components = [];
    public $overrideData = [];
    
    // Filtered data for display
    public $filteredEmployees = [];
    public $filteredComponents = [];

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->slot = PayrollSlot::findOrFail($payrollSlotId);
        $this->salary_execution_group_id = $this->slot->salary_execution_group_id;
        
        // Generate cache key for this specific slot
        $this->cacheKey = "override_components_slot_{$payrollSlotId}_firm_{$this->slot->firm_id}";
        
        $this->loadData();
    }

    protected function loadData()
    {
        // Try to get data from cache first
        $cachedData = Cache::get($this->cacheKey);
        
        if ($cachedData) {
            $this->employees = $cachedData['employees'];
            $this->components = $cachedData['components'];
            $this->overrideData = $cachedData['overrideData'];
        } else {
            $this->fetchDataFromDatabase();
            $this->cacheData();
        }
        
        $this->applyFilters();
    }

    protected function fetchDataFromDatabase()
    {
        // Get all override entries for this slot first
        $overrideEntries = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->where('entry_type', 'override')
            ->with(['employee', 'salary_component'])
            ->get();

        // Get unique employee IDs who actually have overrides
        $employeeIdsWithOverrides = $overrideEntries->pluck('employee_id')->unique();

        // Only get employees who have overrides
        if ($employeeIdsWithOverrides->count() > 0) {
            // Build optimized data structures
            $this->buildOptimizedDataStructures($overrideEntries, $employeeIdsWithOverrides);
        } else {
            // No overrides found, set empty collections
            $this->employees = collect();
            $this->components = collect();
            $this->overrideData = [];
        }
    }

    protected function buildOptimizedDataStructures($overrideEntries, $employeeIds)
    {
        // Build employees collection with key-value pairs (only those with overrides)
        $this->employees = Employee::whereIn('id', $employeeIds)
            ->with('emp_job_profile:id,employee_id,employee_code')
            ->select('id', 'fname', 'mname', 'lname')
            ->get()
            ->keyBy('id');

        // Build components collection with key-value pairs (only those with overrides)
        $componentIds = $overrideEntries->pluck('salary_component_id')->unique();
        $this->components = SalaryComponent::whereIn('id', $componentIds)
            ->select('id', 'title', 'nature', 'component_type')
            ->get()
            ->keyBy('id');

        // Build override data as nested arrays for fast access
        $this->overrideData = [];
        foreach ($overrideEntries as $entry) {
            $this->overrideData[$entry->employee_id][$entry->salary_component_id] = [
                'amount_full' => $entry->amount_full,
                'amount_payable' => $entry->amount_payable,
                'nature' => $entry->nature,
                'component_type' => $entry->component_type,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at
            ];
        }
    }

    protected function cacheData()
    {
        $dataToCache = [
            'employees' => $this->employees,
            'components' => $this->components,
            'overrideData' => $this->overrideData,
            'cached_at' => now()
        ];
        
        Cache::put($this->cacheKey, $dataToCache, $this->cacheDuration);
    }

    public function updatedSearchName()
    {
        $this->applyFilters();
    }

    protected function applyFilters()
    {
        if (empty(trim($this->searchName))) {
            $this->filteredEmployees = $this->employees;
        } else {
            $searchTerm = strtolower(trim($this->searchName));
            
            $this->filteredEmployees = $this->employees->filter(function ($employee) use ($searchTerm) {
                $fullName = strtolower(trim($employee->fname . ' ' . ($employee->mname ?? '') . ' ' . $employee->lname));
                $employeeCode = strtolower($employee->emp_job_profile->employee_code ?? '');
                
                return str_contains($fullName, $searchTerm) || 
                       str_contains($employeeCode, $searchTerm);
            });
        }
    }

    public function refreshData()
    {
        // Clear cache and reload data
        Cache::forget($this->cacheKey);
        $this->loadData();
        
        Flux::toast('Data refreshed successfully.');
    }

    /**
     * Reset individual employee overrides
     */
    public function resetIndividualOverrides($employeeId)
    {
        $this->resetType = 'individual';
        $this->resetTargetId = $employeeId;
        $this->resetConfirmation = '';
        $this->resetReason = '';
        
        // Initialize component selection for this employee
        $this->initializeComponentSelection($employeeId);
        
        $this->modal('reset-individual-confirmation')->show();
    }

    /**
     * Reset all overrides for the payroll slot
     */
    public function resetAllOverrides()
    {
        $this->resetType = 'bulk';
        $this->resetTargetId = null;
        $this->resetConfirmation = '';
        $this->resetReason = '';
        
        // Initialize component selection for all components
        $this->initializeComponentSelection();
        
        $this->modal('reset-override-confirmation')->show();
    }

    /**
     * Initialize component selection based on reset type
     */
    protected function initializeComponentSelection($employeeId = null)
    {
        if ($this->resetType === 'individual' && $employeeId) {
            // For individual reset, get components that this employee has overrides for
            $employeeComponents = collect($this->overrideData[$employeeId] ?? [])->keys();
            $this->selectedComponents = $employeeComponents->toArray();
        } else {
            // For bulk reset, select all components
            $this->selectedComponents = $this->components->keys()->toArray();
        }
        
        $this->updateSelectAllState();
    }

    /**
     * Update the select all checkbox state
     */
    public function updatedSelectAllComponents()
    {
        if ($this->selectAllComponents) {
            $this->selectedComponents = $this->components->keys()->toArray();
        } else {
            $this->selectedComponents = [];
        }
    }

    /**
     * Update select all state when individual components are selected/deselected
     */
    public function updatedSelectedComponents()
    {
        $this->updateSelectAllState();
    }

    /**
     * Update the select all checkbox state based on current selection
     */
    protected function updateSelectAllState()
    {
        $totalComponents = $this->components->count();
        $selectedCount = count($this->selectedComponents);
        
        if ($selectedCount === 0) {
            $this->selectAllComponents = false;
        } elseif ($selectedCount === $totalComponents) {
            $this->selectAllComponents = true;
        } else {
            $this->selectAllComponents = false;
        }
    }

    /**
     * Execute the reset operation
     */
    public function executeReset()
    {
        try {
            // Only validate for bulk operations
            if ($this->resetType === 'bulk') {
                $this->validate();
            }

            DB::transaction(function () {
                if ($this->resetType === 'individual') {
                    $this->resetEmployeeOverrides($this->resetTargetId);
                } else {
                    $this->resetAllEmployeeOverrides();
                }

                // Clear cache and reload data
                Cache::forget($this->cacheKey);
                $this->loadData();
            });

            // Close modal and show success message
            if ($this->resetType === 'individual') {
                $this->modal('reset-individual-confirmation')->close();
            } else {
                $this->modal('reset-override-confirmation')->close();
            }
            
            $this->resetResetProperties();
            
            Flux::toast(
                variant: 'success',
                heading: 'Overrides Reset Successfully',
                text: $this->resetType === 'individual' ? 'Employee overrides have been reset.' : 'All overrides have been reset.'
            );

        } catch (ValidationException $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Validation Error',
                text: $e->validator->errors()->first()
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Reset Failed',
                text: 'Failed to reset overrides: ' . $e->getMessage()
            );
        }
    }

    /**
     * Reset overrides for a specific employee
     */
    protected function resetEmployeeOverrides($employeeId)
    {
        if (empty($this->selectedComponents)) {
            throw new \Exception("No components selected for reset.");
        }

        $deletedCount = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->where('employee_id', $employeeId)
            ->where('entry_type', 'override')
            ->whereIn('salary_component_id', $this->selectedComponents)
            ->delete();

        if ($deletedCount === 0) {
            throw new \Exception("No override entries found for the selected components for this employee.");
        }
    }

    /**
     * Reset all overrides for the payroll slot
     */
    protected function resetAllEmployeeOverrides()
    {
        if (empty($this->selectedComponents)) {
            throw new \Exception("No components selected for reset.");
        }

        $deletedCount = PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->where('entry_type', 'override')
            ->whereIn('salary_component_id', $this->selectedComponents)
            ->delete();

        if ($deletedCount === 0) {
            throw new \Exception("No override entries found for the selected components for this payroll slot.");
        }
    }

    /**
     * Log the reset operation for audit trail
     */
   

    /**
     * Get count of affected entries for logging
     */
    protected function getAffectedEntriesCount()
    {
        // If no employees, components, or selected components, return 0
        if ($this->employees->count() === 0 || $this->components->count() === 0 || empty($this->selectedComponents)) {
            return 0;
        }

        if ($this->resetType === 'individual' && $this->resetTargetId) {
            return PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->where('employee_id', $this->resetTargetId)
                ->where('entry_type', 'override')
                ->whereIn('salary_component_id', $this->selectedComponents)
                ->count();
        } else {
            return PayrollComponentsEmployeesTrack::where('firm_id', Session::get('firm_id'))
                ->where('payroll_slot_id', $this->payrollSlotId)
                ->where('entry_type', 'override')
                ->whereIn('salary_component_id', $this->selectedComponents)
                ->count();
        }
    }

    /**
     * Reset reset-related properties
     */
    protected function resetResetProperties()
    {
        $this->resetConfirmation = '';
        $this->resetType = '';
        $this->resetTargetId = null;
        $this->resetReason = '';
        $this->selectedComponents = [];
        $this->selectAllComponents = false;
    }

    /**
     * Cancel reset operation
     */
    public function cancelReset()
    {
        $this->resetResetProperties();
        
        if ($this->resetType === 'individual') {
            $this->modal('reset-individual-confirmation')->close();
        } else {
            $this->modal('reset-override-confirmation')->close();
        }
    }

    public function getEmployeeOverrideAmount($employeeId, $componentId)
    {
        return $this->overrideData[$employeeId][$componentId]['amount_full'] ?? 0;
    }

    public function getEmployeeOverridePayable($employeeId, $componentId)
    {
        return $this->overrideData[$employeeId][$componentId]['amount_payable'] ?? 0;
    }

    public function hasOverrideEntry($employeeId, $componentId)
    {
        return isset($this->overrideData[$employeeId][$componentId]);
    }

    public function getOverrideEntryDate($employeeId, $componentId)
    {
        if (isset($this->overrideData[$employeeId][$componentId])) {
            return $this->overrideData[$employeeId][$componentId]['updated_at'] ?? 
                   $this->overrideData[$employeeId][$componentId]['created_at'];
        }
        return null;
    }

    /**
     * Check if employee has any override entries
     */
    public function hasAnyOverrideEntry($employeeId)
    {
        return isset($this->overrideData[$employeeId]) && !empty($this->overrideData[$employeeId]);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/review-override-components.blade.php'));
    }
}
