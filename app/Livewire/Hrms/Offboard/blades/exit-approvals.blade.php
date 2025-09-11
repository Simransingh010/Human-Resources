<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-4xl font-extrabold tracking-tight text-slate-800">Exit Approval Tracker</h2>
        </div>
        <flux:input
            placeholder="Search employees..."
            icon="magnifying-glass"
            wire:model.live.debounce.400ms="search"
            class="w-96"
        />
    </div>

    <!-- Tabs -->
    <div class="flex gap-3">
        <button class="px-4 py-2 rounded-xl border text-sm font-medium shadow-sm transition {{ $filter==='all' ? 'bg-blue-100 text-blue-800 border-blue-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}" wire:click="setFilter('all')">All</button>
        <button class="px-4 py-2 rounded-xl border text-sm font-medium shadow-sm transition {{ $filter==='pending' ? 'bg-blue-100 text-blue-800 border-blue-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}" wire:click="setFilter('pending')">Pending</button>
        <button class="px-4 py-2 rounded-xl border text-sm font-medium shadow-sm transition {{ $filter==='in_progress' ? 'bg-blue-100 text-blue-800 border-blue-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}" wire:click="setFilter('in_progress')">In Progress</button>
        <button class="px-4 py-2 rounded-xl border text-sm font-medium shadow-sm transition {{ $filter==='completed' ? 'bg-blue-100 text-blue-800 border-blue-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}" wire:click="setFilter('completed')">Completed</button>
        <button class="px-4 py-2 rounded-xl border text-sm font-medium shadow-sm transition {{ $filter==='overdue' ? 'bg-blue-100 text-blue-800 border-blue-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}" wire:click="setFilter('overdue')">Overdue</button>
    </div>

    <!-- Grouped cards by exit -->
    <div class="space-y-6">
        @php
            $grouped = collect($this->steps->items() ?? [])->groupBy('exit_id');
        @endphp

        @forelse($grouped as $exitId => $rows)
            @php
                $first = $rows->first();
                $employee = $first->employee;
                $jp = optional($employee)->emp_job_profile;
                $exit = $first->exit;
                $total = $rows->count();
                $approved = $rows->where('status','approved')->count();
                $dueTs = $exit && $exit->last_working_day ? strtotime($exit->last_working_day) : null;
                $isOverdue = $dueTs && $dueTs < strtotime('today') && $approved < $total;
                $statusText = $approved === $total ? 'Completed' : ($isOverdue ? 'Overdue' : ($approved > 0 ? 'In Progress' : 'Pending'));
                if ($filter !== 'all') {
                    $map = [
                        'completed'   => ($approved === $total),
                        'in_progress' => ($approved > 0 && $approved < $total),
                        'pending'     => ($approved === 0 && $approved < $total),
                        'overdue'     => $isOverdue,
                    ];
                    if (($map[$filter] ?? false) === false) continue;
                }
                $next = $rows->where('status','!=','approved')->sortBy('flow_order')->first();
                $canAct = $next ? $this->canActOn($next->id) : false;
                $percent = $total ? intval(($approved/$total)*100) : 0;
                $r = 24; $c = 2 * 3.14159265 * $r; $offset = $c - ($c * $percent / 100);
                $avatar = $this->getEmployeeImageUrl($employee);
            @endphp
            <div class="rounded-2xl border border-slate-300 bg-white p-6 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-5">
                        <div class="h-12 w-12 rounded-2xl overflow-hidden shadow-sm bg-slate-200 flex items-center justify-center">
                            @if($avatar)
                                <img src="{{ $avatar }}" alt="Avatar" class="h-12 w-12 object-cover" />
                            @else
                                <span class="text-slate-700 font-semibold">{{ strtoupper(substr($employee->fname ?? 'E',0,1)) }}{{ strtoupper(substr($employee->lname ?? 'X',0,1)) }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-xl font-semibold text-slate-900 mx-1">{{ trim(($employee->fname ?? '').' '.($employee->lname ?? '')) }} @if($jp && $jp->employee_code)<span class="text-slate-500 text-sm">({{ $jp->employee_code }})</span>@endif</div>
                            <div class="text-slate-600 text-sm flex items-center gap-2 mx-1">
                                <span>{{ \App\Models\Hrms\EmployeeExit::EXIT_TYPES[$exit->exit_type] ?? $exit->exit_type }}</span>
                                <span>•</span>
                                <span>Due: {{ $exit->last_working_day ? date('jS M Y', strtotime($exit->last_working_day)) : '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 min-w-[140px] justify-end">
                        <div class="relative h-14 w-14">
                            <svg class="absolute mt-10 inset-0 -rotate-90" width="56" height="56" viewBox="0 0 56 56" aria-hidden="true">
                                <circle cx="28" cy="28" r="{{ $r }}" stroke="#E5E7EB" stroke-width="6" fill="none" />
                                <circle cx="28" cy="28" r="{{ $r }}" stroke="#1D4ED8" stroke-width="6" fill="none" stroke-linecap="round" stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $offset }}" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center text-slate-900 font-semibold text-sm">{{ $approved }}/{{ $total }}</div>
                        </div>
                        @switch($statusText)
                            @case('Completed')
                                <span class="px-3 py-1 rounded-full bg-green-200 text-green-900 text-sm font-medium">Completed</span>
                                @break
                            @case('In Progress')
                                <span class="px-3 py-1 rounded-full bg-amber-200 text-amber-900 text-sm font-medium">In Progress</span>
                                @break
                            @case('Overdue')
                                <span class="px-3 py-1 rounded-full bg-rose-200 text-rose-900 text-sm font-medium">Overdue</span>
                                @break
                            @default
                                <span class="px-3 py-1 rounded-full bg-blue-200 text-blue-900 text-sm font-medium">Pending</span>
                        @endswitch
                    </div>
                </div>

                <!-- Steps Inline List -->
                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach($rows->sortBy('flow_order') as $step)
                        @php $isNext = $next && $step->id === $next->id; @endphp
                        <div class="px-4 py-2 rounded-xl border text-sm font-medium flex items-center gap-3 shadow-sm {{ $step->status==='approved' ? 'bg-green-100 border-green-300 text-green-900' : ($isNext ? 'bg-blue-100 border-blue-300 text-blue-900' : 'border-slate-300 text-slate-800 bg-slate-50') }}">
                            <span>{{ \App\Models\Hrms\ExitApprovalStep::APPROVAL_TYPE_SELECT[$step->approval_type] ?? ucfirst(str_replace('_',' ',$step->approval_type)) }}</span>
                            @if($step->status==='approved')
                                <span class="ml-1 text-xs">✓</span>
                            @elseif($isNext && $canAct)
                                <span class="flex items-center gap-2">
                                    <flux:button size="xs" variant="success" wire:click="approveStep({{ $step->id }})">Approve</flux:button>
                                    <flux:button size="xs" variant="subtle" wire:click="rejectStep({{ $step->id }})">Reject</flux:button>
                                </span>
                            @elseif($isNext)
                                <span class="text-xs text-amber-700">Waiting for approver</span>
                            @else
                                @switch($step->status)
                                    @case('rejected')
                                        <span class="px-2 py-0.5 rounded-full bg-rose-200 text-rose-900 text-xs font-semibold">Rejected</span>
                                        @break
                                    @case('blocked')
                                        <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-800 text-xs font-semibold">Blocked</span>
                                        @break
                                    @case('in_progress')
                                        <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 text-xs font-semibold">In Progress</span>
                                        @break
                                    @case('pending')
                                    @default
                                        <span class="px-2 py-0.5 rounded-full bg-blue-200 text-blue-900 text-xs font-semibold">Pending</span>
                                @endswitch
                            @endif
                            <span>
                                <flux:button size="xs" color="zinc" wire:click="openChecklist({{ $step->id }})">Checklist</flux:button>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <flux:callout variant="secondary" icon="cube" inline>
                <flux:callout.heading>No approvals found.</flux:callout.heading>
            </flux:callout>
        @endforelse
    </div>

    <!-- Checklist Modal -->
    <flux:modal name="mdl-exit-checklist" title="Exit Checklist">
        <div class="space-y-4 p-4">
            @if(empty($checklistItems))
                <flux:callout icon="cube" variant="secondary" inline>
                    <flux:callout.heading>No checklist items found for this step.</flux:callout.heading>
                </flux:callout>
            @else
                <div class="space-y-2">
                    @foreach($checklistItems as $item)
                        <div class="flex items-start justify-between border border-slate-300 rounded-lg p-3 bg-white">
                            <div>
                                <div class="font-semibold text-slate-800">{{ $item['clearance_item'] }}</div>
                                @if(!empty($item['clearance_desc']))
                                    <div class="text-sm text-slate-600">{{ $item['clearance_desc'] }}</div>
                                @endif
                            </div>
                            <div>
                                @switch($item['status'])
                                    @case('cleared')
                                        <span class="px-2 py-1 rounded-full bg-green-200 text-green-900 text-xs font-medium">Cleared</span>
                                        @break
                                    @case('pending')
                                    @default
                                        <span class="px-2 py-1 rounded-full bg-blue-200 text-blue-900 text-xs font-medium">Pending</span>
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="primary" wire:click="$flux.modal('mdl-exit-checklist').close()">Close</flux:button>
                    <flux:button variant="danger" wire:click="markAllChecklistCleared">Mark All Cleared</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    <div>
        {{ $this->steps->links() }}
    </div>
</div>
