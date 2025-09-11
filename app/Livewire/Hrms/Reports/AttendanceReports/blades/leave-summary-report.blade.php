<div class="space-y-6">
    <flux:heading size="lg">Leave Balance Summary (as of today)</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Employee -->
        <flux:select label="Employee" variant="listbox" multiple searchable  wire:model.defer="filters.employee_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['employees'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <!-- Department -->
        <flux:select label="Department" variant="listbox" multiple searchable  wire:model.defer="filters.department_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['departments'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <!-- Job Location -->
        <flux:select label="Location" variant="listbox" multiple searchable wire:model.defer="filters.joblocation_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['locations'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <!-- Employment Type -->
        <flux:select label="Employment Type" variant="listbox" multiple searchable  wire:model.defer="filters.employment_type_id">
            <flux:select.option value="">All</flux:select.option>
            @foreach($listsForFields['employment_types'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="flex justify-end space-x-2">
        <flux:button wire:click="export" variant="primary">
            Export to Excel
        </flux:button>
    </div>
</div>


