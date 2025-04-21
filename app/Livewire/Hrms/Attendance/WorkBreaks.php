<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\WorkBreak;
use Flux;

class WorkBreaks extends Component
{
    use \Livewire\WithPagination;

    public $breakData = [
        'id' => null,
        'firm_id' => null,
        'break_title' => '',
        'break_desc' => '',
        'start_time' => null,
        'end_time' => null,
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);

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
    public function breaksList()
    {
        return WorkBreak::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    public function fetchBreak($id)
    {
        $workBreak = WorkBreak::findOrFail($id);
        $this->breakData = $workBreak->toArray();
        $this->isEditing = true;
        $this->modal('mdl-break')->show();
    }

    public function saveBreak()
    {
        $validatedData = $this->validate([
            'breakData.break_title' => 'required|string|max:255',
            'breakData.break_desc' => 'nullable|string',
            'breakData.start_time' => 'required|date_format:H:i',
            'breakData.end_time' => 'required|date_format:H:i|after:breakData.start_time',
            'breakData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $workBreak = WorkBreak::findOrFail($this->breakData['id']);
            $workBreak->update($validatedData['breakData']);
//            dd($validatedData);
            session()->flash('message', 'Break updated successfully.');
        } else {
            $validatedData['breakData']['firm_id'] = session('firm_id');
            WorkBreak::create($validatedData['breakData']);
            dd($validatedData);
            session()->flash('message', 'Break added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-break')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Work break has been updated.',
        );
    }

    public function toggleStatus($breakId)
    {
        $workBreak = WorkBreak::findOrFail($breakId);
        $workBreak->update([
            'is_inactive' => !$workBreak->is_inactive
        ]);
        
        Flux::toast(
            heading: 'Status Updated',
            text: 'Break status has been changed.',
        );
    }
    public function deleteWorkBreak($id)
    {
            $policy = WorkBreak::findOrFail($id);
            $policy->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Work break deleted successfully.',
            );
    }
    public function resetForm()
    {
        $this->breakData = [
            'id' => null,
            'firm_id' => null,
            'break_title' => '',
            'break_desc' => '',
            'start_time' => null,
            'end_time' => null,
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.work-breaks');
    }
} 