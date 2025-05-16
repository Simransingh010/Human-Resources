<?php

namespace App\Livewire\Hrms\WorkShifts;

use App\Models\Hrms\WorkShiftsAlgo;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\HolidayCalendar;
use App\Models\Hrms\WorkBreak;
use App\Models\Hrms\WorkShiftDay;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Flux;
use JsonException;
use Illuminate\Support\Facades\DB;
use App\Models\Batch;
use App\Models\Hrms\Holiday;

class WorkShiftsAlgos extends Component
{
    use WithPagination;

    public $selectedAlgoId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $batchStatuses = [];

    public $weekOffPattern = [
        'type' => '',
        'fixed_weekly' => [
            'off_days' => []
        ],
        'rotating' => [
            'cycle' => [0, 0, 0, 0, 0, 0, 0],
            'offset' => 0
        ],
        'holiday_calendar' => [
            'id' => null,
            'use_public_holidays' => true
        ],
        'exceptions' => []
    ];
    public $weekOffTypes = [
        'fixed_weekly' => 'Fixed Weekly',
        //        'rotating' => 'Rotating',
        'holiday_calendar' => 'Holiday Calendar',
        'combined' => 'Combined',
    ];

    public $formData = [
        'id' => null,
        'work_shift_id' => '',
        'start_date' => '',
        'end_date' => '',
        'start_time' => '',
        'end_time' => '',
        'week_off_pattern' => '',
        'work_breaks' => '',
        'holiday_calendar_id' => '',
        'allow_wfh' => false,
        'half_day_rule' => '',
        'overtime_rule' => '',
        'rules_config' => '',
        'late_panelty' => '',
        'comp_off' => '',
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;
    public $perPage = 10;

    // Week Off Pattern Configuration
    public $weekOffConfig = [
        'type' => 'fixed_weekly',
        'fixed_weekly' => [
            'off_days' => []
        ],
        'rotating' => [
            'cycle' => [],
            'offset' => 0
        ],
        'holiday_calendar' => [
            'id' => null,
            'use_public_holidays' => true
        ],
        'exceptions' => []
    ];

    public $weekDays = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday'
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'work_shift_id' => ['label' => 'Work Shift', 'type' => 'select', 'listKey' => 'work_shifts'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date'],
        'end_date' => ['label' => 'End Date', 'type' => 'date'],
        'start_time' => ['label' => 'Start Time', 'type' => 'time'],
        'week_off_pattern' => ['label' => 'Week Off Pattern', 'type' => 'text'],
        'end_time' => ['label' => 'End Time', 'type' => 'time'],

        'work_breaks' => ['label' => 'Work Breaks', 'type' => 'multiselect', 'listKey' => 'work_breaks'],
        'holiday_calendar_id' => ['label' => 'Holiday Calendar', 'type' => 'select', 'listKey' => 'holiday_calendars'],
        'allow_wfh' => ['label' => 'Allow WFH', 'type' => 'boolean'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    public array $filterFields = [
        'search_shift' => ['label' => 'Work Shift', 'type' => 'text'],
        'search_pattern' => ['label' => 'Week Off Pattern', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_shift' => '',
        'search_pattern' => '',
        'is_inactive' => '',
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];
    public array $listsForFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->getWorkShiftsForSelect();
        $this->getHolidayCalendarsForSelect();
        $this->getWorkBreaksForSelect();
        $this->loadBatchStatuses();

        // Set default visible fields and filters
        $this->visibleFields = ['work_shift_id', 'start_date', 'end_date', 'start_time', 'end_time', 'week_off_pattern', 'work_breaks', 'allow_wfh', 'is_inactive'];
        $this->visibleFilterFields = ['search_shift', 'search_pattern', 'is_inactive'];
    }

    private function getWorkShiftsForSelect()
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    private function getHolidayCalendarsForSelect()
    {
        $this->listsForFields['holiday_calendars'] = HolidayCalendar::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('title', 'id')
            ->toArray();
    }

    private function getWorkBreaksForSelect()
    {
        $this->listsForFields['work_breaks'] = WorkBreak::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->get()
            ->map(function ($break) {
                return [
                    'id' => $break->id,
                    'title' => $break->break_title . ' (' . $break->start_time->format('H:i') . ' - ' . $break->end_time->format('H:i') . ')'
                ];
            })
            ->pluck('title', 'id')
            ->toArray();
    }

    private function loadBatchStatuses()
    {
        // Get latest batch status for each algo
        $latestBatches = Batch::where('modulecomponent', 'work_shifts_algo')
            ->whereIn('action', ['sync_days', 'sync_days_rolled_back'])
            ->latest()
            ->get()
            ->groupBy('title') // Group by algo title which contains the algo ID
            ->map(function ($batches) {
                return $batches->first()->action;
            });

        $this->batchStatuses = $latestBatches->toArray();
    }

    public function getBatchStatus($algoId)
    {
        $key = "Sync Work Shift Days for Algo #{$algoId}";
        return $this->batchStatuses[$key] ?? null;
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShiftsAlgo::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_shift'], function ($query) {
                $query->whereHas('work_shift', function ($q) {
                    $q->where('shift_title', 'like', '%' . $this->filters['search_shift'] . '%');
                });
            })
            ->when($this->filters['search_pattern'], function ($query) {
                $query->where('week_off_pattern', 'like', '%' . $this->filters['search_pattern'] . '%');
            })
            ->with(['work_shift'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_id' => 'required|exists:work_shifts,id',
            'formData.start_date' => 'nullable|date',
            'formData.end_date' => 'nullable|date|after_or_equal:formData.start_date',
            'formData.start_time' => 'nullable|date_format:H:i',
            'formData.end_time' => 'nullable|date_format:H:i|after:formData.start_time',
            'formData.week_off_pattern' => 'nullable|string',
            'formData.work_breaks' => 'nullable|array',
            'formData.work_breaks.*' => 'exists:work_breaks,id',
            'formData.holiday_calendar_id' => 'nullable|exists:holiday_calendars,id',
            'formData.allow_wfh' => 'boolean',
            'formData.half_day_rule' => 'nullable|string',
            'formData.overtime_rule' => 'nullable|string',
            'formData.rules_config' => 'nullable|string',
            'formData.late_panelty' => 'nullable|string',
            'formData.comp_off' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ]);

        // Validate week off pattern structure
        if (!isset($this->weekOffPattern['type']) || !array_key_exists($this->weekOffPattern['type'], $this->weekOffTypes)) {
            $this->weekOffPattern['type'] = 'fixed_weekly';
        }

        // Ensure all required sections exist
        $this->weekOffPattern['fixed_weekly'] = $this->weekOffPattern['fixed_weekly'] ?? ['off_days' => []];
        $this->weekOffPattern['rotating'] = $this->weekOffPattern['rotating'] ?? ['cycle' => [], 'offset' => 0];
        $this->weekOffPattern['holiday_calendar'] = $this->weekOffPattern['holiday_calendar'] ?? ['id' => null, 'use_public_holidays' => true];
        $this->weekOffPattern['exceptions'] = $this->weekOffPattern['exceptions'] ?? [];

        // Clean up exceptions
        foreach ($this->weekOffPattern['exceptions'] as $i => $exception) {
            if (!isset($exception['date']) || empty($exception['date'])) {
                unset($this->weekOffPattern['exceptions'][$i]);
                continue;
            }
            $this->weekOffPattern['exceptions'][$i]['off'] = (bool) ($exception['off'] ?? true);
        }
        $this->weekOffPattern['exceptions'] = array_values($this->weekOffPattern['exceptions']);

        // Convert empty strings to null and handle work_breaks
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function ($val) {
                if ($val === '') {
                    return null;
                }
                // Convert work_breaks array to JSON string
                if (is_array($val) && isset($this->formData['work_breaks']) && $this->formData['work_breaks'] === $val) {
                    return !empty($val) ? json_encode($val) : null;
                }
                return $val;
            })
            ->toArray();

        // Add firm_id from session and week off pattern
        $validatedData['formData']['firm_id'] = session('firm_id');
        $validatedData['formData']['week_off_pattern'] = json_encode($this->weekOffPattern);

        if ($this->isEditing) {
            $algo = WorkShiftsAlgo::findOrFail($this->formData['id']);
            $algo->update($validatedData['formData']);
            $toastMsg = 'Work Shift Algorithm updated successfully';
        } else {
            WorkShiftsAlgo::create($validatedData['formData']);
            $toastMsg = 'Work Shift Algorithm added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-algo')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $algo = WorkShiftsAlgo::findOrFail($id);
        $this->formData = array_merge($algo->toArray(), [
            'work_breaks' => json_decode($algo->work_breaks ?? '[]', true) ?? [],
        ]);

        // Load week off pattern if it exists
        if ($algo->week_off_pattern) {
            try {
                $pattern = json_decode($algo->week_off_pattern, true);
                if (is_array($pattern)) {
                    // Ensure all required sections exist
                    $pattern['type'] = $pattern['type'] ?? 'fixed_weekly';
                    $pattern['fixed_weekly'] = $pattern['fixed_weekly'] ?? ['off_days' => []];
                    $pattern['rotating'] = $pattern['rotating'] ?? ['cycle' => [], 'offset' => 0];
                    $pattern['holiday_calendar'] = $pattern['holiday_calendar'] ?? ['id' => null, 'use_public_holidays' => true];
                    $pattern['exceptions'] = $pattern['exceptions'] ?? [];

                    $this->weekOffPattern = $pattern;
                }
            } catch (\Exception $e) {
                // If there's an error, use default pattern
                $this->resetForm();
            }
        } else {
            // If no pattern exists, use default
            $this->resetForm();
        }

        $this->isEditing = true;
        $this->modal('mdl-algo')->show();
    }

    public function delete($id)
    {
        $algo = WorkShiftsAlgo::findOrFail($id);
        $algo->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Shift Algorithm has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['allow_wfh'] = false;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;

        // Reset week off pattern to default state
        $this->weekOffPattern = [
            'type' => '',
            'fixed_weekly' => [
                'off_days' => []
            ],
            'rotating' => [
                'cycle' => [0, 0, 0, 0, 0, 0, 0],
                'offset' => 0
            ],
            'holiday_calendar' => [
                'id' => null,
                'use_public_holidays' => true
            ],
            'exceptions' => []
        ];
    }

    public function refreshStatuses()
    {
        $this->statuses = WorkShiftsAlgo::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
            ->toArray();
    }

    public function toggleStatus($algoId)
    {
        $algo = WorkShiftsAlgo::find($algoId);
        $algo->is_inactive = !$algo->is_inactive;
        $algo->save();

        $this->statuses[$algoId] = $algo->is_inactive;
        $this->refreshStatuses();
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

    public function configureWeekOffPattern($id)
    {
        try {
            $algo = WorkShiftsAlgo::findOrFail($id);

            // Initialize or load existing pattern
            $pattern = json_decode($algo->week_off_pattern, true) ?: [
                'type' => '',
                'fixed_weekly' => [
                    'off_days' => []
                ],
                'holiday_calendar' => [
                    'id' => $algo->holiday_calendar_id,
                    'use_public_holidays' => true
                ],
                'exceptions' => []
            ];

            // Update the model's week_off_pattern
            $algo->week_off_pattern = json_encode($pattern);
            $algo->save();

            $this->modal('mdl-week-off')->show();
            $this->selectedAlgoId = $id;
            $this->weekOffPattern = $pattern;

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load week off pattern configuration.',
            );
        }
    }

    public function saveWeekOffPattern()
    {
        try {
            if (!$this->selectedAlgoId) {
                throw new \Exception('No algorithm selected for update.');
            }

            $algo = WorkShiftsAlgo::findOrFail($this->selectedAlgoId);

            // Validate the pattern structure
            if (empty($this->weekOffPattern['type'])) {
                throw new \Exception('Please select a pattern type.');
            }

            if (!array_key_exists($this->weekOffPattern['type'], $this->weekOffTypes)) {
                throw new \Exception('Invalid pattern type selected.');
            }

            // Clean unused patterns based on current type
            $this->cleanUnusedPatterns($this->weekOffPattern['type']);

            // Validate based on type
            switch ($this->weekOffPattern['type']) {
                case 'fixed_weekly':
                    if (empty($this->weekOffPattern['fixed_weekly']['off_days'])) {
                        throw new \Exception('Please select at least one week off day.');
                    }
                    break;

                case 'holiday_calendar':
                    if (empty($this->weekOffPattern['holiday_calendar']['id'])) {
                        throw new \Exception('Please select a holiday calendar.');
                    }
                    break;

                case 'combined':
                    if (
                        empty($this->weekOffPattern['fixed_weekly']['off_days']) &&
                        empty($this->weekOffPattern['holiday_calendar']['id'])
                    ) {
                        throw new \Exception('For combined type, please configure either weekly off days or holiday calendar.');
                    }
                    break;
            }

            // Clean up exceptions
            if (!empty($this->weekOffPattern['exceptions'])) {
                foreach ($this->weekOffPattern['exceptions'] as $i => $exception) {
                    if (!isset($exception['date']) || empty($exception['date'])) {
                        unset($this->weekOffPattern['exceptions'][$i]);
                        continue;
                    }
                    $this->weekOffPattern['exceptions'][$i]['off'] = (bool) ($exception['off'] ?? true);
                }
                $this->weekOffPattern['exceptions'] = array_values($this->weekOffPattern['exceptions']);
            }

            // Save the pattern
            $algo->week_off_pattern = json_encode($this->weekOffPattern);
            $algo->save();

            $this->modal('mdl-week-off')->close();
            $this->selectedAlgoId = null;
            $this->resetWeekOffPattern();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Week off pattern updated successfully.',
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
        }
    }

    private function resetWeekOffPattern()
    {
        $this->weekOffPattern = [
            'type' => '',
            'fixed_weekly' => [
                'off_days' => []
            ],
            'rotating' => [
                'cycle' => [0, 0, 0, 0, 0, 0, 0],
                'offset' => 0
            ],
            'holiday_calendar' => [
                'id' => null,
                'use_public_holidays' => true
            ],
            'exceptions' => []
        ];
    }

    public function addException()
    {
        if (!isset($this->weekOffPattern['exceptions'])) {
            $this->weekOffPattern['exceptions'] = [];
        }
        $this->weekOffPattern['exceptions'][] = [
            'date' => '',
            'off' => true
        ];
    }

    public function removeException($index)
    {
        if (isset($this->weekOffPattern['exceptions'][$index])) {
            unset($this->weekOffPattern['exceptions'][$index]);
            $this->weekOffPattern['exceptions'] = array_values($this->weekOffPattern['exceptions']);
        }
    }

    public function toggleRotatingDay($index)
    {
        // Ensure the rotating cycle array exists and has 7 days
        if (!isset($this->weekOffPattern['rotating'])) {
            $this->weekOffPattern['rotating'] = [
                'cycle' => [0, 0, 0, 0, 0, 0, 0],
                'offset' => 0
            ];
        }

        if (!isset($this->weekOffPattern['rotating']['cycle']) || !is_array($this->weekOffPattern['rotating']['cycle'])) {
            $this->weekOffPattern['rotating']['cycle'] = [0, 0, 0, 0, 0, 0, 0];
        }

        // Ensure we have exactly 7 days
        if (count($this->weekOffPattern['rotating']['cycle']) !== 7) {
            $this->weekOffPattern['rotating']['cycle'] = array_pad(
                array_slice($this->weekOffPattern['rotating']['cycle'], 0, 7),
                7,
                0
            );
        }

        // Toggle the day (0 = work day, 1 = off day)
        $this->weekOffPattern['rotating']['cycle'][$index] =
            $this->weekOffPattern['rotating']['cycle'][$index] ? 0 : 1;
    }

    public function initializeRotatingPattern()
    {
        if ($this->weekOffPattern['type'] === 'rotating' || $this->weekOffPattern['type'] === 'combined') {
            if (!isset($this->weekOffPattern['rotating']['cycle']) || !is_array($this->weekOffPattern['rotating']['cycle'])) {
                $this->weekOffPattern['rotating']['cycle'] = [0, 0, 0, 0, 0, 0, 0];
            }

            // Ensure we have exactly 7 days
            if (count($this->weekOffPattern['rotating']['cycle']) !== 7) {
                $this->weekOffPattern['rotating']['cycle'] = array_pad(
                    array_slice($this->weekOffPattern['rotating']['cycle'], 0, 7),
                    7,
                    0
                );
            }

            // Ensure offset is within bounds
            $this->weekOffPattern['rotating']['offset'] =
                min(6, max(0, (int) ($this->weekOffPattern['rotating']['offset'] ?? 0)));
        }
    }

    public function updatedWeekOffPatternType($value)
    {
        // Store current off_days before cleaning
        $currentOffDays = $this->weekOffPattern['fixed_weekly']['off_days'] ?? [];

        if ($value === 'rotating' || $value === 'combined') {
            $this->initializeRotatingPattern();
        }

        $this->cleanUnusedPatterns($value);

        // If switching to fixed_weekly or combined, restore off_days
        if ($value === 'fixed_weekly' || $value === 'combined') {
            $this->weekOffPattern['fixed_weekly']['off_days'] = $currentOffDays;
        }
    }

    private function cleanUnusedPatterns($currentType)
    {
        // Only reset patterns that are not currently in use
        if ($currentType !== 'fixed_weekly' && $currentType !== 'combined') {
            // Don't reset fixed_weekly if it's the current type
            if ($this->weekOffPattern['type'] !== 'fixed_weekly') {
                $this->weekOffPattern['fixed_weekly'] = ['off_days' => []];
            }
        }

        if ($currentType !== 'rotating' && $currentType !== 'combined') {
            $this->weekOffPattern['rotating'] = ['cycle' => [0, 0, 0, 0, 0, 0, 0], 'offset' => 0];
        }

        if ($currentType !== 'holiday_calendar' && $currentType !== 'combined') {
            $this->weekOffPattern['holiday_calendar'] = ['id' => null, 'use_public_holidays' => true];
        }
    }

    public function toggleFixedWeekDay($dayNumber)
    {
        // Ensure the fixed_weekly structure exists
        if (!isset($this->weekOffPattern['fixed_weekly'])) {
            $this->weekOffPattern['fixed_weekly'] = ['off_days' => []];
        }

        if (!isset($this->weekOffPattern['fixed_weekly']['off_days'])) {
            $this->weekOffPattern['fixed_weekly']['off_days'] = [];
        }

        // Toggle the day in the off_days array
        if (in_array($dayNumber, $this->weekOffPattern['fixed_weekly']['off_days'])) {
            $this->weekOffPattern['fixed_weekly']['off_days'] = array_values(
                array_filter(
                    $this->weekOffPattern['fixed_weekly']['off_days'],
                    fn($day) => $day !== $dayNumber
                )
            );
        } else {
            $this->weekOffPattern['fixed_weekly']['off_days'][] = $dayNumber;
            sort($this->weekOffPattern['fixed_weekly']['off_days']);
        }
    }

    private function isDayOff(Carbon $date, array $weekOffPattern, $algoHolidayCalendarId = null): bool
    {
        // Check exceptions first (highest priority)
        if (!empty($weekOffPattern['exceptions'])) {
            foreach ($weekOffPattern['exceptions'] as $exception) {
                if ($date->format('Y-m-d') === Carbon::parse($exception['date'])->format('Y-m-d')) {
                    return $exception['off'];
                }
            }
        }

        $isOff = false;

        // Check holiday calendar (second priority)
        // First check the direct holiday_calendar_id from algo
        if ($algoHolidayCalendarId) {
            $holiday = Holiday::where('holiday_calendar_id', $algoHolidayCalendarId)
                ->where(function ($query) use ($date) {
                    $query->where(function ($q) use ($date) {
                        // Check for exact date match
                        $q->whereDate('start_date', '<=', $date)
                            ->whereDate('end_date', '>=', $date);
                    })->orWhere(function ($q) use ($date) {
                        // Check for annually repeating holidays
                        $q->where('repeat_annually', true)
                            ->whereMonth('start_date', $date->month)
                            ->whereDay('start_date', $date->day)
                            ->where(function ($subQ) use ($date) {
                            $subQ->whereNull('end_date')
                                ->orWhere(function ($endQ) use ($date) {
                                    $endQ->whereMonth('end_date', $date->month)
                                        ->whereDay('end_date', '>=', $date->day);
                                });
                        });
                    });
                })
                ->where('is_inactive', false)
                ->first();

            if ($holiday) {
                return true; // It's a holiday
            }
        }
        // Then check the holiday calendar from week_off_pattern if different
        elseif (in_array($weekOffPattern['type'], ['holiday_calendar', 'combined'])) {
            if (
                !empty($weekOffPattern['holiday_calendar']['id']) &&
                $weekOffPattern['holiday_calendar']['id'] !== $algoHolidayCalendarId
            ) {

                $calendarId = $weekOffPattern['holiday_calendar']['id'];
                $holiday = Holiday::where('holiday_calendar_id', $calendarId)
                    ->where(function ($query) use ($date) {
                        $query->where(function ($q) use ($date) {
                            $q->whereDate('start_date', '<=', $date)
                                ->whereDate('end_date', '>=', $date);
                        })->orWhere(function ($q) use ($date) {
                            $q->where('repeat_annually', true)
                                ->whereMonth('start_date', $date->month)
                                ->whereDay('start_date', $date->day);
                        });
                    })
                    ->where('is_inactive', false)
                    ->first();

                if ($holiday) {
                    return true;
                }
            }
        }

        // Check fixed weekly pattern (third priority)
        if (in_array($weekOffPattern['type'], ['fixed_weekly', 'combined'])) {
            $dayNumber = $date->dayOfWeek + 1; // Convert to 1-7 format (Monday-Sunday)
            if (in_array($dayNumber, $weekOffPattern['fixed_weekly']['off_days'])) {
                $isOff = true;
            }
        }

        return $isOff;
    }

    private function getDayStatus(Carbon $date, array $weekOffPattern, $algoHolidayCalendarId = null): array
    {
        $isOff = $this->isDayOff($date, $weekOffPattern, $algoHolidayCalendarId);

        // Default status for working day
        $status = [
            'day_status_main' => 'F', // Full Working
            'paid_percent' => 100
        ];

        // Check if it's a holiday from either source
        if ($isOff) {
            // Check if it's a holiday (either from algo's holiday_calendar_id or week_off_pattern)
            $isHoliday = false;

            if ($algoHolidayCalendarId) {
                $holiday = Holiday::where('holiday_calendar_id', $algoHolidayCalendarId)
                    ->where(function ($query) use ($date) {
                        $query->where(function ($q) use ($date) {
                            $q->whereDate('start_date', '<=', $date)
                                ->whereDate('end_date', '>=', $date);
                        })->orWhere(function ($q) use ($date) {
                            $q->where('repeat_annually', true)
                                ->whereMonth('start_date', $date->month)
                                ->whereDay('start_date', $date->day);
                        });
                    })
                    ->where('is_inactive', false)
                    ->first();

                if ($holiday) {
                    $isHoliday = true;
                }
            } elseif (
                in_array($weekOffPattern['type'], ['holiday_calendar', 'combined']) &&
                !empty($weekOffPattern['holiday_calendar']['id'])
            ) {
                $holiday = Holiday::where('holiday_calendar_id', $weekOffPattern['holiday_calendar']['id'])
                    ->where(function ($query) use ($date) {
                        $query->where(function ($q) use ($date) {
                            $q->whereDate('start_date', '<=', $date)
                                ->whereDate('end_date', '>=', $date);
                        })->orWhere(function ($q) use ($date) {
                            $q->where('repeat_annually', true)
                                ->whereMonth('start_date', $date->month)
                                ->whereDay('start_date', $date->day);
                        });
                    })
                    ->where('is_inactive', false)
                    ->first();

                if ($holiday) {
                    $isHoliday = true;
                }
            }

            if ($isHoliday) {
                $status = [
                    'day_status_main' => 'H', // Holiday
                    'paid_percent' => 100  // Paid holiday
                ];
            } else {
                $status = [
                    'day_status_main' => 'W', // Week Off
                    'paid_percent' => 0     // Unpaid
                ];
            }
        }

        return $status;
    }

    public function syncWorkShiftDays($algoId)
    {
        try {
            DB::beginTransaction();

            $algo = WorkShiftsAlgo::findOrFail($algoId);

            // Validate required fields
            if (!$algo->start_date || !$algo->end_date || !$algo->start_time || !$algo->end_time) {
                throw new \Exception('Start date, end date, start time and end time are required.');
            }

            // Check for overlapping algorithms for the same work shift
            $overlappingAlgo = WorkShiftsAlgo::where('work_shift_id', $algo->work_shift_id)
                ->where('id', '!=', $algoId)
                ->where(function ($query) use ($algo) {
                    $query->whereBetween('start_date', [$algo->start_date, $algo->end_date])
                        ->orWhereBetween('end_date', [$algo->start_date, $algo->end_date])
                        ->orWhere(function ($q) use ($algo) {
                            $q->where('start_date', '<=', $algo->start_date)
                                ->where('end_date', '>=', $algo->end_date);
                        });
                })
                ->first();

            if ($overlappingAlgo) {
                throw new \Exception(
                    "Cannot create overlapping work shift algorithms. " .
                    "There is already an algorithm for this work shift from " .
                    Carbon::parse($overlappingAlgo->start_date)->format('jS M Y') . " to " .
                    Carbon::parse($overlappingAlgo->end_date)->format('jS M Y')
                );
            }

            // Parse week off pattern
            $weekOffPattern = json_decode($algo->week_off_pattern, true);
            if (!$weekOffPattern || !isset($weekOffPattern['type'])) {
                throw new \Exception('Invalid week off pattern configuration.');
            }

            // Start bulk operation
            $batch = BulkOperationService::start(
                'work_shifts_algo',
                'sync_days',
                "Sync Work Shift Days for Algo #{$algoId}"
            );

            // Delete existing work shift days if any
            WorkShiftDay::where('work_shift_id', $algo->work_shift_id)
                ->whereBetween('work_date', [$algo->start_date, $algo->end_date])
                ->get()
                ->each(function ($day) use ($batch) {
                    $originalData = json_encode($day->getAttributes());

                    $batch->items()->create([
                        'operation' => 'delete',
                        'model_type' => get_class($day),
                        'model_id' => $day->id,
                        'original_data' => $originalData
                    ]);

                    $day->delete();
                });

            // Generate days
            $period = CarbonPeriod::create($algo->start_date, $algo->end_date);

            foreach ($period as $date) {
                $dayStatus = $this->getDayStatus($date, $weekOffPattern, $algo->holiday_calendar_id);

                $workShiftDay = new WorkShiftDay([
                    'firm_id' => session('firm_id'),
                    'work_shift_id' => $algo->work_shift_id,
                    'work_date' => $date,
                    'start_time' => $date->format('Y-m-d') . ' ' . $algo->start_time,
                    'end_time' => $date->format('Y-m-d') . ' ' . $algo->end_time,
                    'day_status_main' => $dayStatus['day_status_main'],
                    'paid_percent' => $dayStatus['paid_percent']
                ]);

                $workShiftDay->save();

                $batch->items()->create([
                    'operation' => 'insert',
                    'model_type' => get_class($workShiftDay),
                    'model_id' => $workShiftDay->id,
                    'new_data' => json_encode($workShiftDay->getAttributes())
                ]);
            }

            DB::commit();

            $this->loadBatchStatuses();

            Flux::toast(
                variant: 'success',
                heading: 'Sync Complete',
                text: 'Work shift days have been generated successfully.',
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                variant: 'error',
                heading: 'Sync Failed',
                text: $e->getMessage(),
            );
        }
    }

    public function rollbackSync($algoId)
    {
        try {
            // Find the latest sync batch for this algo
            $batch = Batch::where('modulecomponent', 'work_shifts_algo')
                ->where('action', 'sync_days')
                ->latest()
                ->first();

            if (!$batch) {
                throw new \Exception('No sync operation found to rollback.');
            }

            DB::transaction(function () use ($batch, $algoId) {
                // Get all model IDs that were created during this batch
                $createdIds = $batch->items()
                    ->where('operation', 'insert')
                    ->where('model_type', WorkShiftDay::class)
                    ->pluck('model_id')
                    ->toArray();

                if (!empty($createdIds)) {
                    // Hard delete all WorkShiftDay records created in this batch
                    WorkShiftDay::whereIn('id', $createdIds)->forceDelete();
                }

                // Mark batch as rolled back
                $batch->update(['action' => 'sync_days_rolled_back']);
            });

            // After successful rollback, update batch statuses
            $this->loadBatchStatuses();

            Flux::toast(
                variant: 'success',
                heading: 'Rollback Complete',
                text: 'All work shift days from this sync have been deleted.',
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Rollback Failed',
                text: $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shifts-algos.blade.php'));
    }
}