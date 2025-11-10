<?php

namespace App\Livewire\Saas;

use App\Models\Saas\College;
use App\Models\Saas\CollegeEmployee;
use App\Models\Hrms\Employee;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Flux;

class CollegeEmployees extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $firmId = null;
    public $isEditing = false;
    public $selectedBatchId = null;
    public $showItemsModal = false;

    // Form properties
    public $selectedCollegeId = null;
    public $selectedEmployeeIds = [];
    public $employeeSearch = '';
    public $collegeSearch = '';

    // Field configuration
    public array $fieldConfig = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
        'action' => ['label' => 'Action', 'type' => 'select', 'listKey' => 'actions'],
        'created_at' => ['label' => 'Created Date', 'type' => 'date'],
        'items_count' => ['label' => 'Items Count', 'type' => 'badge'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
    ];

    protected function rules()
    {
        return [
            'selectedCollegeId' => 'required|exists:college,id',
            'selectedEmployeeIds' => 'required|array|min:1',
            'selectedEmployeeIds.*' => 'exists:employees,id',
        ];
    }

    public function mount($firmId = null)
    {
        $this->firmId = $firmId;
        $this->formData['firm_id'] = $this->getCurrentFirmId();
        $this->initListsForFields();
        $this->resetPage();

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'action', 'created_at', 'items_count'];
        $this->visibleFilterFields = ['title', 'modulecomponent'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = [
            'college_employee_assignment' => 'College Employee Assignment',
        ];

        $this->listsForFields['actions'] = [
            'bulk_assignment' => 'Bulk Assignment',
        ];
    }

    // Helper method to get the current firm ID
    protected function getCurrentFirmId()
    {
        return $this->firmId ?: Session::get('firm_id');
    }

    #[Computed]
    public function colleges()
    {
        return College::query()
            ->where('firm_id', $this->getCurrentFirmId())
            ->when($this->collegeSearch, fn($query, $value) => 
                $query->where(function($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                      ->orWhere('code', 'like', "%{$value}%")
                      ->orWhere('city', 'like', "%{$value}%");
                }))
            ->orderBy('name', 'asc')
            ->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->where('firm_id', $this->getCurrentFirmId())
            ->where('is_inactive', false)
            ->when($this->employeeSearch, fn($query, $value) => 
                $query->where(function($q) use ($value) {
                    $q->where('fname', 'like', "%{$value}%")
                      ->orWhere('lname', 'like', "%{$value}%")
                      ->orWhere('email', 'like', "%{$value}%")
                      ->orWhere('phone', 'like', "%{$value}%");
                }))
            ->orderBy('fname', 'asc')
            ->get();
    }

    public function selectAllEmployees()
    {
        $this->selectedEmployeeIds = $this->employees->pluck('id')->map(fn($id) => (string)$id)->toArray();
    }

    public function deselectAllEmployees()
    {
        $this->selectedEmployeeIds = [];
    }

    public function store()
    {
        try {
            $this->validate();

            $college = College::findOrFail($this->selectedCollegeId);
            $actualSelectedEmployeeIds = array_map('intval', $this->selectedEmployeeIds);

            if (empty($actualSelectedEmployeeIds)) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Validation Error',
                    text: 'Please select at least one employee.',
                );
                return;
            }

            DB::beginTransaction();
            try {
                // Start the batch operation
                $batchTitle = "College Employee Assignment - College: {$college->name}";
                
                $batch = BulkOperationService::start(
                    'college_employee_assignment',
                    'bulk_assignment',
                    $batchTitle
                );

                // Get existing assignments for this college
                $existingAssignments = CollegeEmployee::where('college_id', $this->selectedCollegeId)
                    ->whereIn('employee_id', $actualSelectedEmployeeIds)
                    ->pluck('employee_id')
                    ->toArray();

                $createdCount = 0;
                $skippedCount = 0;

                // Process each selected employee
                foreach ($actualSelectedEmployeeIds as $employeeId) {
                    if (in_array($employeeId, $existingAssignments)) {
                        // Employee already assigned - skip
                        $skippedCount++;
                        continue;
                    }
                    
                    // Create new assignment
                    try {
                        $collegeEmployee = CollegeEmployee::create([
                            'college_id' => $this->selectedCollegeId,
                            'employee_id' => $employeeId,
                        ]);
                        
                        BulkOperationService::logInsert($batch, $collegeEmployee);
                        $createdCount++;
                    } catch (\Exception $e) {
                        // Skip if duplicate (race condition)
                        $skippedCount++;
                        continue;
                    }
                }

                if ($createdCount === 0 && $skippedCount > 0) {
                    // All employees were already assigned - rollback the transaction
                    DB::rollBack();
                    Flux::toast(
                        variant: 'warning',
                        heading: 'Info',
                        text: 'All selected employees are already assigned to this college.',
                    );
                    return;
                }

                DB::commit();
                $this->selectedBatchId = $batch->id;

                $message = "Successfully assigned {$createdCount} employee(s) to college.";
                if ($skippedCount > 0) {
                    $message .= " {$skippedCount} employee(s) were already assigned.";
                }
                
                Flux::toast(
                    heading: 'Success',
                    text: $message,
                );

                $this->resetForm();
                $this->modal('mdl-assignment')->close();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to assign employees: ' . $e->getMessage(),
            );
        }
    }

    public function resetForm()
    {
        $this->selectedCollegeId = null;
        $this->selectedEmployeeIds = [];
        $this->employeeSearch = '';
        $this->collegeSearch = '';
        $this->isEditing = false;
        $this->formData = [
            'id' => null,
            'firm_id' => $this->getCurrentFirmId(),
        ];
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
        return Batch::query()
            ->where('modulecomponent', 'college_employee_assignment')
            ->when($this->filters['title'] ?? null, fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->withCount('items as items_count')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function showBatchItems($batchId)
    {
        try {
            $batch = Batch::with([
                'items' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])->findOrFail($batchId);

            $this->selectedBatchId = $batch->id;
            $this->showItemsModal = true;
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load batch items: ' . $e->getMessage(),
            );
        }
    }

    public function closeItemsModal()
    {
        $this->showItemsModal = false;
        $this->selectedBatchId = null;
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with('items')->findOrFail($batchId);

                // Process items in reverse order
                foreach ($batch->items()->latest('id')->get() as $item) {
                    $modelClass = $item->model_type;
                    
                    switch ($item->operation) {
                        case 'insert':
                            // Delete the created record
                            if ($model = $modelClass::find($item->model_id)) {
                                $model->delete();
                            }
                            break;

                        case 'update':
                            // Restore original data if possible
                            if ($model = $modelClass::find($item->model_id)) {
                                $originalData = json_decode($item->original_data, true);
                                if ($originalData) {
                                    $model->update($originalData);
                                }
                            }
                            break;
                    }
                }

                // Update batch action
                $batch->update(['action' => "{$batch->action}_rolled_back"]);
            });

            Flux::toast(
                heading: 'Success',
                text: 'College employee assignments rolled back successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback: ' . $e->getMessage(),
            );
        }
    }

    public function delete($id)
    {
        try {
            $batch = Batch::findOrFail($id);

            if ($batch->items()->count() > 0) {
                $batch->items()->delete();
            }

            $batch->delete();

            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Batch has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete: ' . $e->getMessage(),
            );
        }
    }

    public function updatedEmployeeSearch()
    {
        // Clear selection when search changes significantly
        // Optionally keep selection, but for simplicity we can reset
    }

    public function updatedCollegeSearch()
    {
        // Reset college selection if it doesn't match search
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/college-employees.blade.php'), [
            'colleges' => $this->colleges,
            'employees' => $this->employees,
        ]);
    }
}
