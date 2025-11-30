<div class="space-y-6">
    <flux:heading size="lg">Student Attendance Summary</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <flux:date-picker
            label="Attendance Period"
            mode="range"
            with-today
            with-presets
            wire:model="filters.date_range"
        />

        <flux:select
            label="Students"
            variant="listbox"
            multiple
            searchable
            wire:model.defer="filters.student_ids"
        >
            <flux:select.option value="">All students</flux:select.option>
            @foreach($listsForFields['students'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            label="Study Centres"
            variant="listbox"
            multiple
            searchable
            wire:model.defer="filters.study_centre_ids"
        >
            <flux:select.option value="">All centres</flux:select.option>
            @foreach($listsForFields['studyCentres'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            label="Study Groups"
            variant="listbox"
            multiple
            searchable
            wire:model.defer="filters.study_group_ids"
        >
            <flux:select.option value="">All groups</flux:select.option>
            @foreach($listsForFields['studyGroups'] as $id => $name)
                <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            label="Attendance Status"
            variant="listbox"
            multiple
            searchable
            wire:model.defer="filters.status_codes"
        >
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach($listsForFields['statuses'] as $code => $label)
                <flux:select.option value="{{ $code }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            label="Search"
            placeholder="Name, email or phone"
            wire:model.defer="filters.search"
        />
    </div>

    <div class="flex justify-end gap-2">
        <flux:button variant="ghost" icon="arrow-uturn-left" wire:click="resetFilters">
            Reset
        </flux:button>
        <flux:button variant="outline" icon="arrow-path" wire:click="applyFilters">
            Apply Filters
        </flux:button>
        <flux:button variant="primary" icon="arrow-down-tray" wire:click="export">
            Export Excel
        </flux:button>
    </div>

    <flux:card class="space-y-6">
        <flux:icon name="information-circle" />
        Once you configure the filters, click <strong>Export Excel</strong> to generate a detailed attendance workbook.
        This screen intentionally shows no preview data—reports open directly in Excel for advanced analysis.
    </flux:card>
</div>