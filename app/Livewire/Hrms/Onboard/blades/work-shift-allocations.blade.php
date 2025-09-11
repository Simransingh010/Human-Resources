<div>
<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <form wire:submit.prevent="applyFilters">
        <flux:heading level="3" size="lg">Work Shift Allocations</flux:heading>
        <flux:card size="sm" class="sm:p-2 !rounded-xl rounded-xl! mb-1 rounded-t-lg p-0 bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
            <div class="grid md:grid-cols-3 gap-2 items-end">
                <div>
                    <flux:input type="text" placeholder="Search employees" wire:model.debounce.500ms="filters.employees" wire:change="applyFilters"/>
                </div>
                <div>
                    <flux:select placeholder="Filter by Work Shift" wire:model="filters.work_shift_id" wire:change="applyFilters">
                        <flux:select.option value="">All Shifts</flux:select.option>
                        @foreach($this->listsForFields['work_shifts'] as $sid => $stitle)
                            <flux:select.option value="{{ $sid }}">{{ $stitle }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="filled" class="w-full px-2" tooltip="Clear filters" icon="x-circle" wire:click="clearFilters()"></flux:button>
                </div>
            </div>
        </flux:card>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
        @foreach ($this->employeeslist as $employee)
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-3 flex flex-col gap-3 hover:shadow-lg transition-all border border-zinc-100 dark:border-zinc-700" x-data="{ showAll: false }">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col">
                        <span class="font-semibold text-lg">{{ $employee->fname }} {{ $employee->mname }} {{ $employee->lname }}</span>
                        <span class="text-xs text-gray-500">
                            {{ $employee->emp_job_profile?->department?->title ?? '-' }}
                            @if(($employee->emp_job_profile?->department?->title ?? '-') !== '-' && ($employee->emp_job_profile?->designation?->title ?? '-') !== '-')
                                &bull;
                            @endif
                            {{ $employee->emp_job_profile?->designation?->title ?? '-' }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="primary" size="sm" icon="plus" wire:click="showManageModal({{ $employee->id }})">Allocate</flux:button>
                    </div>
                </div>

                <div class="space-y-2">
                    @forelse($employee->emp_work_shifts->take(3) as $alloc)
                        <div class="flex items-center justify-between p-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="flex flex-col text-sm">
                                <span class="font-medium">{{ $alloc->work_shift?->shift_title ?? 'Shift' }}</span>
                                @php
                                    $timeStr = null;
                                    $startStr = optional($alloc->start_date)?->toDateString();
                                    $endStr = $alloc->end_date ? optional($alloc->end_date)->toDateString() : null;
                                    if ($alloc->work_shift && $alloc->work_shift->work_shift_days) {
                                        foreach ($alloc->work_shift->work_shift_days as $d) {
                                            $dStr = optional($d->work_date)?->toDateString();
                                            if ($dStr && $dStr >= $startStr && (!$endStr || $dStr <= $endStr)) {
                                                $s = optional($d->start_time)?->format('h:i A');
                                                $e = optional($d->end_time)?->format('h:i A');
                                                if ($s && $e) { $timeStr = $s . ' - ' . $e; }
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                @if($timeStr)
                                    <span class="text-xs text-gray-600">{{ $timeStr }}</span>
                                @endif
                                <span class="text-xs text-gray-500">
                                    {{ optional($alloc->start_date)->format('d M Y') }} -
                                    {{ $alloc->end_date ? optional($alloc->end_date)->format('d M Y') : 'Open-ended' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:badge color="blue" size="sm">ID: {{ $alloc->id }}</flux:badge>
                                <flux:modal.trigger name="confirm-deallocate">
                                    <flux:button variant="danger" size="xs" icon="trash" wire:click.prevent="confirmDeallocate({{ $alloc->id }})"></flux:button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    @empty
                        <flux:badge color="zinc" size="sm">No allocations yet</flux:badge>
                    @endforelse
                    @if($employee->emp_work_shifts->count() > 3)
                        <div class="mt-1">
                            <flux:accordion transition>
                                <flux:accordion.item heading="Show more ({{ $employee->emp_work_shifts->count() - 3 }})">
                                    <div class="mt-2 space-y-2">
                                        @foreach($employee->emp_work_shifts->skip(3) as $alloc)
                                            <div class="flex items-center justify-between p-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                                <div class="flex flex-col text-sm">
                                                    <span class="font-medium">{{ $alloc->work_shift?->shift_title ?? 'Shift' }}</span>
                                                    @php
                                                        $timeStr = null;
                                                        $startStr = optional($alloc->start_date)?->toDateString();
                                                        $endStr = $alloc->end_date ? optional($alloc->end_date)->toDateString() : null;
                                                        if ($alloc->work_shift && $alloc->work_shift->work_shift_days) {
                                                            foreach ($alloc->work_shift->work_shift_days as $d) {
                                                                $dStr = optional($d->work_date)?->toDateString();
                                                                if ($dStr && $dStr >= $startStr && (!$endStr || $dStr <= $endStr)) {
                                                                    $s = optional($d->start_time)?->format('h:i A');
                                                                    $e = optional($d->end_time)?->format('h:i A');
                                                                    if ($s && $e) { $timeStr = $s . ' - ' . $e; }
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    @if($timeStr)
                                                        <span class="text-xs text-gray-600">{{ $timeStr }}</span>
                                                    @endif
                                                    <span class="text-xs text-gray-500">
                                                        {{ optional($alloc->start_date)->format('d M Y') }} -
                                                        {{ $alloc->end_date ? optional($alloc->end_date)->format('d M Y') : 'Open-ended' }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <flux:badge color="blue" size="sm">ID: {{ $alloc->id }}</flux:badge>
                                                    <flux:modal.trigger name="confirm-deallocate">
                                                        <flux:button variant="danger" size="xs" icon="trash" wire:click.prevent="confirmDeallocate({{ $alloc->id }})"></flux:button>
                                                    </flux:modal.trigger>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </flux:accordion.item>
                            </flux:accordion>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-center">
        {{ $this->employeeslist->links() }}
    </div>

    <flux:modal name="manage-work-shifts" title="Manage Work Shifts" class="p-6 min-w-[28rem]">
        <div class="space-y-4">
            <flux:field>
                <flux:label>Work Shift</flux:label>
                <flux:select wire:model.defer="allocateForm.work_shift_id">
                    <flux:select.option value="">Select shift</flux:select.option>
                    @foreach($this->listsForFields['work_shifts'] as $sid => $stitle)
                        <flux:select.option value="{{ $sid }}">{{ $stitle }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('allocateForm.work_shift_id')<flux:error name="allocateForm.work_shift_id" />@enderror
            </flux:field>
            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>Start date</flux:label>
                    <flux:input type="date" wire:model.defer="allocateForm.start_date" />
                    @error('allocateForm.start_date')<flux:error name="allocateForm.start_date" />@enderror
                </flux:field>
                <flux:field>
                    <flux:label>End date (optional)</flux:label>
                    <flux:input type="date" wire:model.defer="allocateForm.end_date" />
                    @error('allocateForm.end_date')<flux:error name="allocateForm.end_date" />@enderror
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer/>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="allocateShift" variant="primary" icon="check" wire:loading.attr="disabled">
                    <span wire:loading.remove>Allocate</span>
                    <span wire:loading>Allocating…</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="confirm-deallocate" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Deallocate work shift?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to deallocate this work shift.</p>
                    <p>This action cannot be reversed.</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer/>
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" icon="trash" wire:click="deallocateShift" wire:loading.attr="disabled">
                    <span wire:loading.remove>Deallocate</span>
                    <span wire:loading>Removing…</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
