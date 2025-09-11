<div class="space-y-6"
     x-data
     x-on:leave-status-updated.window="$wire.$refresh()"
     wire:key="pol-approvals-main"
>
    <div class="flex justify-between">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            <div class="w-1/4">
                <flux:select label="Employee" wire:model="filters.employee_id" wire:change="applyFilters">
                    <option value="">All Employees</option>
                    @foreach($listsForFields['employees'] ?? [] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-1/4">
                <flux:input type="date" label="From" wire:model.live.debounce.500ms="filters.from_date" wire:change="applyFilters" />
            </div>
            <div class="w-1/4">
                <flux:input type="date" label="To" wire:model.live.debounce.500ms="filters.to_date" wire:change="applyFilters" />
            </div>
            <div class="w-1/4 flex items-end">
                <flux:button variant="outline" wire:click="clearFilters" icon="x-circle">Clear</flux:button>
            </div>
        </div>
    </flux:card>

    <flux:table :paginate="$this->list">
        <flux:table.columns class="table-cell-wrap">
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Work Date</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Remarks</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows class="table-cell-wrap">
            @foreach($this->list as $item)
                <div wire:key="pol-row-{{ $item->id }}" class="contents">
                    <flux:table.row class="table-cell-wrap">
                        <flux:table.cell>{{ $item->employee->fname ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->work_date ? \Carbon\Carbon::parse($item->work_date)->format('jS F Y') : 'N/A' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="blue" variant="solid">Present on Leave</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $item->attend_remarks }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                           <flux:button size="sm" variant="primary" wire:click="showActionModal({{ $item->id }})">Action</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                </div>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="mdl-pol-action" wire:model.live="isActionModalOpen">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Present-on-Leave Action</flux:heading>
                <flux:subheading>Approve to mark Present and credit back leave, or Reject to keep POL.</flux:subheading>
            </div>
            <flux:textarea
                label="Remarks"
                wire:model="formData.remarks"
                placeholder="Enter remarks (optional)"
                rows="4"
            />
            <div class="flex justify-end space-x-4">
                <flux:button type="button" variant="ghost" wire:click="closeModal">Cancel</flux:button>
                <flux:button type="button" variant="danger" wire:click="handleAction('reject', {{ $selectedAttendanceId ?? 'null' }})">Reject</flux:button>
                <flux:button type="button" variant="primary" wire:click="handleAction('accept', {{ $selectedAttendanceId ?? 'null' }})">Approve</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

