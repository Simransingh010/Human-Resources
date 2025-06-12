<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Livewire\WithPagination;

class BulkEmployeeJobProfiles extends Component
{
    use WithPagination;

    public int    $perPage       = 10;
    public string $labelHeader   = 'Employee';
    public array  $labelFields   = ['fname','lname'];

    public array  $fieldConfig   = [
        'employee_code'      => ['label'=>'Employee Code','type'=>'text'],
        'department_id'      => ['label'=>'Department','type'=>'select','listKey'=>'departmentlist'],
        'designation_id'     => ['label'=>'Designation','type'=>'select','listKey'=>'designationlist'],
        'employment_type_id' => ['label'=>'Employment Type','type'=>'select','listKey'=>'employmentTypelist'],
        'joblocation_id'     => ['label'=>'Job Location','type'=>'select','listKey'=>'joblocationlist'],
        'doh'                => ['label'=>'Join Date','type'=>'date'],
        'uanno'              => ['label'=>'UAN No.','type'=>'text'],
        'esicno'             => ['label'=>'ESIC No.','type'=>'text'],
        'pran_number'        => ['label'=>'PRAN Number','type'=>'text'],
        'paylevel'           => ['label'=>'Pay Level','type'=>'text'],
        'rf_id'              => ['label'=>'RF ID','type'=>'text'],
        'pan_number'         => ['label'=>'PAN Number','type'=>'text'],
        'biometric_emp_code' => ['label'=>'Biometric Code','type'=>'text'],
    ];

    // ── NEW ── define exactly which fields appear in the filter bar
    public array $filterFields   = [
        // employee table field:
        'fname'             => ['label'=>'First Name','type'=>'text','source'=>'employee'],
        'employee_code'      => ['label'=>'Employee Code','type'=>'text','source'=>'profile'],
        'department_id'      => ['label'=>'Department','type'=>'select','listKey'=>'departmentlist','source'=>'profile'],
        'designation_id'     => ['label'=>'Designation','type'=>'select','listKey'=>'designationlist','source'=>'profile'],
        'employment_type_id' => ['label'=>'Employment Type','type'=>'select','listKey'=>'employmentTypelist','source'=>'profile'],
        'joblocation_id'     => ['label'=>'Job Location','type'=>'select','listKey'=>'joblocationlist','source'=>'profile'],
        'doh'                => ['label'=>'Join Date','type'=>'date','source'=>'profile'],
        'uanno'              => ['label'=>'UAN No.','type'=>'text','source'=>'profile'],
        'esicno'             => ['label'=>'ESIC No.','type'=>'text','source'=>'profile'],
        'pran_number'        => ['label'=>'PRAN Number','type'=>'text','source'=>'profile'],
        'paylevel'           => ['label'=>'Pay Level','type'=>'text','source'=>'profile'],
        'rf_id'              => ['label'=>'RF ID','type'=>'text','source'=>'profile'],
        'pan_number'         => ['label'=>'PAN Number','type'=>'text','source'=>'personal'],
        'biometric_emp_code' => ['label'=>'Biometric Code','type'=>'text','source'=>'profile'],
    ];

    public array $listsForFields = [];
    public array $bulkupdate     = [];

    // ← NEW: holds current filter values
    public array $filters        = [];

    // ← NEW: which columns to show (you already have this)
    public array $visibleFields  = [];
    public array $visibleFilterFields = [];


    public function mount()
    {
        $this->resetPage();
        $firmId = session('firm_id');

        $this->listsForFields = [
            'departmentlist'     => \App\Models\Settings\Department::where('firm_id',$firmId)->pluck('title','id')->toArray(),
            'designationlist'    => \App\Models\Settings\Designation::where('firm_id',$firmId)->pluck('title','id')->toArray(),
            'employmentTypelist' => \App\Models\Settings\EmploymentType::where('firm_id',$firmId)->pluck('title','id')->toArray(),
            'joblocationlist'    => \App\Models\Settings\Joblocation::where('firm_id',$firmId)->pluck('name','id')->toArray(),
        ];

        // show all columns by default
//        $this->visibleFields = array_keys($this->fieldConfig);
        $this->visibleFields=['employee_code','department_id','designation_id','employment_type_id'];
        $this->visibleFilterFields=['fname','employee_code','department_id','designation_id','employment_type_id'];



        // initialize filters to empty
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }
    // New: make sure filters always reset to page 1
    public function applyFilters()
    {
        // reset back to page 1 whenever filters change
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        $firmId = session('firm_id');

        $query = Employee::with(['emp_job_profile', 'emp_personal_detail'])
            ->where('firm_id', $firmId);

        // 1) First, apply any filters that live on the employees table
        foreach ($this->filterFields as $field => $cfg) {
            $value = $this->filters[$field] ?? null;
            if ($value === '' || $value === null) {
                continue;
            }

            if ($cfg['source'] === 'employee') {
                // text vs select vs date on employees
                if ($cfg['type'] === 'select') {
                    $query->where($field, $value);
                } elseif ($cfg['type'] === 'date') {
                    $query->whereDate($field, $value);
                } else {
                    $query->where($field, 'like', "%{$value}%");
                }
            } elseif ($cfg['source'] === 'personal') {
                $query->whereHas('emp_personal_detail', function ($q) use ($field, $cfg, $value) {
                    if ($cfg['type'] === 'select') {
                        $q->where($field, $value);
                    } elseif ($cfg['type'] === 'date') {
                        $q->whereDate($field, $value);
                    } else {
                        $q->where($field, 'like', "%{$value}%");
                    }
                });
            }
        }

        // 2) Next, apply each profile-table filter individually via whereHas
        foreach ($this->filterFields as $field => $cfg) {
            $value = $this->filters[$field] ?? null;
            if ($value === '' || $value === null) {
                continue;
            }

            if ($cfg['source'] === 'profile') {
                $query->whereHas('emp_job_profile', function ($q) use ($field, $cfg, $value) {
                    if ($cfg['type'] === 'select') {
                        $q->where($field, $value);
                    } elseif ($cfg['type'] === 'date') {
                        $q->whereDate($field, $value);
                    } else {
                        $q->where($field, 'like', "%{$value}%");
                    }
                });
            }
        }

        $employees = $query->paginate($this->perPage);

        // seed editing values as before
        foreach ($employees as $emp) {
            $profile = $emp->emp_job_profile;
            $personal = $emp->emp_personal_detail;
            foreach (array_keys($this->fieldConfig) as $field) {
                if ($field === 'biometric_emp_code') {
                    $this->bulkupdate[$emp->id][$field] = $personal?->biometric_emp_code ?? $profile?->biometric_emp_code;
                }
                if ($field === 'pan_number') {
                    $this->bulkupdate[$emp->id][$field] = $personal?->panno;
                } else {
                    $this->bulkupdate[$emp->id][$field] = $profile?->{$field};
                }
            }
        }

        return $employees;
    }

    public function list_old()
    {
        $firmId = session('firm_id');

        $query = Employee::with('emp_job_profile')
            ->where('firm_id', $firmId);

        // apply profile-field filters
        $query->whereHas('emp_job_profile', function($q) {
            foreach ($this->filters as $field => $value) {
                if ($value === '' || $value === null) continue;

                $cfg = $this->filterFields[$field];

                if ($cfg['type'] === 'date') {
                    $q->whereDate($field, $value);
                }
                elseif ($cfg['type'] === 'select') {
                    $q->where($field, $value);
                }
                else { // text, code, etc.
                    $q->where($field, 'like', "%{$value}%");
                }
            }
        });

        $employees = $query->paginate($this->perPage);

        // seed editing values
        foreach ($employees as $emp) {
            $profile = $emp->emp_job_profile;
            foreach (array_keys($this->fieldConfig) as $field) {
                $this->bulkupdate[$emp->id][$field] = $profile?->{$field};
            }
        }

        return $employees;
    }

    public function triggerUpdate(int $employeeId, string $field)
    {
        $value = $this->bulkupdate[$employeeId][$field] ?? null;

        if ($field === 'pan_number') {
            $personal = \App\Models\Hrms\EmployeePersonalDetail::firstOrCreate(
                ['employee_id' => $employeeId],
                ['firm_id'     => session('firm_id')]
            );
            $personal->panno = $value;
            $personal->save();
            return;
        }


        $profile = EmployeeJobProfile::firstOrCreate(
            ['employee_id' => $employeeId],
            ['firm_id'     => session('firm_id')]
        );

        $profile->$field = $value;
        $profile->save();
    }

    // ← NEW: reset all filters
    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->fieldConfig), '');
    }

    // New: flip a column on/off
    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            // remove it
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            // add it
            $this->visibleFields[] = $field;
        }
    }
    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            // remove it
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            // add it
            $this->visibleFilterFields[] = $field;
        }
    }

    public function render()
    {
        return view()->file(
            app_path('Livewire/Hrms/Onboard/blades/bulk-employee-job-profiles.blade.php')
        );
    }
}
