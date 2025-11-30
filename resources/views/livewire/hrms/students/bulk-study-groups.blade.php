<div class="space-y-6">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <flux:modal.trigger name="mdl-new-group" class="flex justify-end">
            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                New Study Group
            </flux:button>
        </flux:modal.trigger>
    </div>
    <flux:separator class="mt-2 mb-2" />

    {{-- Filters --}}
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4">
                        @switch($cfg['type'])
                            @case('select')
                                <flux:select
                                    variant="listbox"
                                    searchable
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                >
                                    <flux:select.option value="">All {{ $cfg['label'] }}</flux:select.option>
                                    @if(isset($cfg['options']))
                                        @foreach($cfg['options'] as $val => $lab)
                                            <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                        @endforeach
                                    @elseif(isset($cfg['listKey']))
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <flux:select.option value="{{ $val }}">{{ $lab }}</flux:select.option>
                                        @endforeach
                                    @endif
                                </flux:select>
                                @break

                            @default
                                <flux:input
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                        @endswitch
                    </div>
                @endif
            @endforeach

            <flux:button.group>
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters" icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="bars-3"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="table-cells"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    {{-- Filter Fields Show/Hide Modal --}}
    <flux:modal name="mdl-show-hide-filters" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Filters</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($filterFields as $field => $cfg)
                        <flux:checkbox 
                            :checked="in_array($field, $visibleFilterFields)" 
                            label="{{ $cfg['label'] }}" 
                            wire:click="toggleFilterColumn('{{ $field }}')" 
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    {{-- Columns Show/Hide Modal --}}
    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($fieldConfig as $field => $cfg)
                        <flux:checkbox 
                            :checked="in_array($field, $visibleFields)" 
                            label="{{ $cfg['label'] }}" 
                            wire:click="toggleColumn('{{ $field }}')" 
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    {{-- New Study Group Modal --}}
    <flux:modal name="mdl-new-group" @close="resetNewGroup" position="right" class="max-w-none" variant="flyout">
        <form wire:submit.prevent="createGroup">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">New Study Group</flux:heading>
                    <flux:subheading>Create a new study group</flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input label="Group Name" wire:model="newGroupData.name" placeholder="Enter group name" />
                    <flux:select label="Study Centre" wire:model="newGroupData.study_centre_id" placeholder="Select study centre">
                        <option value="">Select study centre</option>
                        @foreach($listsForFields['studyCentres'] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select
                        label="Coach"
                        variant="listbox"
                        searchable
                        placeholder="Select coach"
                        wire:model="newGroupData.coach_id"
                    >
                        <flux:select.option value="">Select coach</flux:select.option>
                        @foreach($listsForFields['coaches'] as $id => $name)
                            <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="flex items-end">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="newGroupData.is_active" value="1"
                                   class="form-checkbox h-5 w-5 text-blue-600">
                            <span>Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Create Group
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Data Table --}}
    <flux:table :paginate="$this->list" class="w-full table-auto border border-gray-200 text-sm">
        <flux:table.columns>
            <flux:table.column class="p-2 text-left">{{ $labelHeader }}</flux:table.column>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column class="p-2 text-left">
                        {{ $cfg['label'] }}
                    </flux:table.column>
                @endif
            @endforeach
            <flux:table.column class="p-2 text-left">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $group)
                <flux:table.row :key="$group->id" class="border-t">
                    <flux:table.cell class="p-2">
                        {{ collect($labelFields)->map(fn($f) => data_get($group, $f))->filter()->implode(' ') }}
                    </flux:table.cell>

                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            @php
                                switch ($field) {
                                    case 'study_centre_id':
                                        $display = $group->study_centre->name ?? '—';
                                        break;
                                    case 'coach_id':
                                        $display = $group->coach
                                            ? (optional($group->coach->emp_job_profile)->employee_code ?? 'N/A') . ' — ' . trim($group->coach->fname . ' ' . $group->coach->lname)
                                            : '—';
                                        break;
                                    case 'is_active':
                                        $display = $group->is_active ? 'Active' : 'Inactive';
                                        break;
                                    case 'student_count':
                                        $display = $group->computed_student_count ?? 0;
                                        break;
                                    default:
                                        $display = data_get($group, $field, '—');
                                }
                            @endphp
                            <flux:table.cell class="p-2">
                                @if($field === 'student_count')
                                    <flux:badge size="sm" color="blue" inset="top bottom">
                                        {{ $display }}
                                    </flux:badge>
                                @else
                                    <span class="text-gray-900 font-medium">{{ $display !== '' ? $display : '—' }}</span>
                                @endif
                            </flux:table.cell>
                        @endif
                    @endforeach

                    <flux:table.cell class="p-2">
                        <div class="flex justify-center space-x-2">
                            <flux:button 
                                variant="primary" 
                                size="sm" 
                                icon="user-group"
                                wire:click="showManageStudents({{ $group->id }})"
                                tooltip="Manage Students"
                            />
                            <flux:modal.trigger name="delete-group-{{ $group->id }}">
                                <flux:button variant="danger" size="sm" icon="trash" tooltip="Delete"></flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-group-{{ $group->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete study group?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete "{{ $group->name }}".</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteGroup({{ $group->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Manage Students Modal --}}
    <flux:modal name="manage-students-modal" wire:model="showStudentModal" title="Manage Students" class="max-w-4xl">
        @if($selectedGroupId)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Students Linked to this Study Centre</flux:heading>
                    <flux:text class="text-gray-600">
                        Students listed below are automatically associated via their study centre assignments.
                    </flux:text>
                </div>

                <flux:input
                    type="search"
                    placeholder="Search students (name or Aadhaar)"
                    wire:model.live="studentSearch"
                    class="w-full"
                >
                    <x-slot:prefix>
                        <flux:icon name="magnifying-glass" class="w-5 h-5 text-gray-400"/>
                    </x-slot:prefix>
                    @if($studentSearch)
                        <x-slot:suffix>
                            <flux:button
                                wire:click="clearStudentSearch"
                                variant="ghost"
                                size="xs"
                                icon="x-mark"
                                class="text-gray-400 hover:text-gray-600"
                            />
                        </x-slot:suffix>
                    @endif
                </flux:input>

                <div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                    @forelse($availableStudents as $student)
                        <div class="p-3 border border-gray-100 rounded-lg flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ $student['label'] }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $student['meta'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-500 text-sm">
                            No students available for this centre.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button variant="ghost" wire:click="closeStudentModal">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>

