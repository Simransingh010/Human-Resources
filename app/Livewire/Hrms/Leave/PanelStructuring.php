<?php

namespace App\Livewire\Hrms\Leave;

use Livewire\Component;

class PanelStructuring extends Component
{
    // Hierarchical navigation data
    public array $data = [
        'hrms' => [
            'name' => 'HRMS',
            'desc' => 'Human Resource Management System',
            'modules' => [
                'payroll' => [
                    'name' => 'Payroll',
                    'desc' => 'Payroll Management',
                    'sections' => [
                        'salary-structure' => [
                            'name' => 'Salary Structure',
                            'desc' => 'Define salary structure',
                            'components' => ['Earnings', 'Deductions', 'Allowances']
                        ],
                        'attendance' => [
                            'name' => 'Attendance',
                            'desc' => 'Attendance Tracking',
                            'components' => ['Timesheet', 'Leave', 'Overtime']
                        ]
                    ]
                ],
                'recruitment' => [
                    'name' => 'Recruitment',
                    'desc' => 'Recruitment Module',
                    'sections' => [
                        'job-postings' => [
                            'name' => 'Job Postings',
                            'desc' => 'Manage job postings',
                            'components' => ['Openings', 'Applications']
                        ]
                    ]
                ]
            ]
        ],
        'crm' => [
            'name' => 'CRM',
            'desc' => 'Customer Relationship Management',
            'modules' => []
        ],
        'erp' => [
            'name' => 'ERP',
            'desc' => 'Enterprise Resource Planning',
            'modules' => []
        ],
        'pm' => [
            'name' => 'Project Management',
            'desc' => 'Task and Project Tracking',
            'modules' => []
        ]
    ];

    // Current selection state
    public $selectedApplication = null;
    public $selectedModule = null;
    public $selectedSection = null;
    public $selectedComponent = null;

    // Collapsed state for columns
    public $collapsed = [1 => false, 2 => false, 3 => false, 4 => false];

    // Select application
    public function selectApplication($key)
    {
        $this->selectedApplication = $key;
        $this->selectedModule = null;
        $this->selectedSection = null;
        $this->selectedComponent = null;
    }

    // Select module
    public function selectModule($key)
    {
        $this->selectedModule = $key;
        $this->selectedSection = null;
        $this->selectedComponent = null;
    }

    // Select section
    public function selectSection($key)
    {
        $this->selectedSection = $key;
        $this->selectedComponent = null;
    }

    // Select component
    public function selectComponent($component)
    {
        $this->selectedComponent = $component;
    }

    // Toggle collapse for columns
    public function toggleCollapse($column)
    {
        $this->collapsed[$column] = !$this->collapsed[$column];
    }

    // Breadcrumb
    public function getBreadcrumbProperty()
    {
        $crumbs = [];
        if ($this->selectedApplication) {
            $crumbs[] = $this->data[$this->selectedApplication]['name'];
        }
        if ($this->selectedModule && isset($this->data[$this->selectedApplication]['modules'][$this->selectedModule])) {
            $crumbs[] = $this->data[$this->selectedApplication]['modules'][$this->selectedModule]['name'];
        }
        if ($this->selectedSection && isset($this->data[$this->selectedApplication]['modules'][$this->selectedModule]['sections'][$this->selectedSection])) {
            $crumbs[] = $this->data[$this->selectedApplication]['modules'][$this->selectedModule]['sections'][$this->selectedSection]['name'];
        }
        if ($this->selectedComponent) {
            $crumbs[] = $this->selectedComponent;
        }
        return $crumbs;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/panel-structuring.blade.php'));
    }
}
