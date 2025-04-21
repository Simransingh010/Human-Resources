<?php

namespace App\Livewire\Hrms\WorkShiftMeta;

use Livewire\Component;
use App\Models\Hrms\WorkBreak;
use Flux;

class WorkShiftBreaks extends Component
{
    use \Livewire\WithPagination;

    public $breakData = [
        'id' => null,
        'firm_id' => null,
        'break_title' => '',
        'break_desc' => '',
        'start_time' => '',
        'end_time' => '',
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', session('firm_id'));
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
            ->get();
    }

    public function fetchBreak($id)
    {
        $break = WorkBreak::findOrFail($id);
        $this->breakData = $break->toArray();
        $this->isEditing = true;
        $this->modal('mdl-break')->show();
    }

    public function saveBreak()
    {
        $validatedData = $this->validate([
            'breakData.break_title' => 'required|string|max:255',
            'breakData.break_desc' => 'nullable|string',
            'breakData.start_time' => 'required',
            'breakData.end_time' => 'required|after:breakData.start_time',
            'breakData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $break = WorkBreak::findOrFail($this->breakData['id']);
            $break->update($validatedData['breakData']);
            $toast = 'Break updated successfully.';
        } else {
            $validatedData['breakData']['firm_id'] = session('firm_id');
            WorkBreak::create($validatedData['breakData']);
            $toast = 'Break added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-break')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    public function toggleStatus($breakId)
    {
        $break = WorkBreak::findOrFail($breakId);
        $break->update([
            'is_inactive' => !$break->is_inactive
        ]);
        
        Flux::toast(
            heading: 'Status Updated',
            text: 'Break status has been changed.',
        );
    }

    public function deleteBreak($breakId)
    {
        try {
            $break = WorkBreak::findOrFail($breakId);
            $break->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Break deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete Break.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->breakData = [
            'id' => null,
            'firm_id' => null,
            'break_title' => '',
            'break_desc' => '',
            'start_time' => '',
            'end_time' => '',
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.work-shift-meta.work-shift-breaks');
    }
} 