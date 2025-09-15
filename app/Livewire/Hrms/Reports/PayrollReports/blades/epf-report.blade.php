<div class="space-y-6">
    <flux:heading size="lg">EPF Report</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:date-picker label="Salary Period" with-today mode="range" with-presets wire:model="filters.date_range"/>

        <flux:select label="Employee" variant="listbox" multiple searchable wire:model.defer="filters.employee_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['employees'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Department" variant="listbox" multiple searchable wire:model.defer="filters.department_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['departments'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Location" variant="listbox" multiple searchable wire:model.defer="filters.joblocation_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['locations'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Employment Type" variant="listbox" multiple searchable wire:model.defer="filters.employment_type_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['employment_types'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select label="Salary Execution Group" variant="listbox" wire:model.defer="filters.salary_execution_group_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['salary_execution_groups'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="flex justify-end space-x-2">
        <flux:button wire:click="export" variant="primary">Export to Excel</flux:button>
        <flux:button wire:click="exportText" variant="outline">Export ECR Text</flux:button>
    </div>
</div>

