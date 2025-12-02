<div class="space-y-6"
     x-data
     x-on:leave-status-updated.window="$wire.$refresh()"
     x-effect="() => {
         console.log('DOM changed in Team Leaves component');
         console.log($el);
     }"
     x-init="
         window.addEventListener('console-log', (e) => {
             console.group(e.detail.message);
             if (e.detail.data) {
                 console.log(e.detail.data);
             }
             console.groupEnd();
         });
     "
     wire:key="team-leaves-main"
>
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        {{--        <flux:modal.trigger name="mdl-leave-request" class="flex justify-end">--}}
        {{--            <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">--}}
        {{--                New--}}
        {{--            </flux:button>--}}
        {{--        </flux:modal.trigger>--}}
    </div>
    <flux:separator class="mt-2 mb-2"/>
    <!-- Heading End -->

    <!-- Filters Start -->
    <flux:card>
        <flux:heading>Filters</flux:heading>
        <div class="flex flex-wrap gap-4">
            @foreach($filterFields as $field => $cfg)
                @if(in_array($field, $visibleFilterFields))
                    <div class="w-1/4" wire:key="filter-field-{{ $field }}">
                        @switch($cfg['type'])
                            @case('select')
                                <flux:input
                                    placeholder="All {{ $cfg['label'] }}"
                                    wire:model="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                                @break

                            @case('date')
                                <flux:date-picker selectable-header
                                    placeholder="Search {{ $cfg['label'] }}"
                                    wire:model.live.debounce.500ms="filters.{{ $field }}"
                                    wire:change="applyFilters"
                                />
                                @break

                            @case('number')
                                <flux:input
                                        type="number"
                                        placeholder="Search {{ $cfg['label'] }}"
                                        wire:model.live.debounce.500ms="filters.{{ $field }}"
                                        wire:change="applyFilters"
                                />
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
                <flux:button variant="outline" wire:click="clearFilters" tooltip="Clear Filters"
                             icon="x-circle"></flux:button>
                <flux:modal.trigger name="mdl-show-hide-filters">
                    <flux:button variant="outline" tooltip="Set Filters" icon="funnel"></flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="mdl-show-hide-columns">
                    <flux:button variant="outline" tooltip="Set Columns" icon="bars-3"></flux:button>
                </flux:modal.trigger>
            </flux:button.group>
        </div>
    </flux:card>

    <!-- Filter Fields Show/Hide Modal -->
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

    <!-- Columns Show/Hide Modal -->
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

    <!-- Add/Edit Leave Request Modal -->
    <flux:modal name="mdl-leave-request" @cancel="resetForm">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Leave Request @else Add Leave Request @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif leave request details.
                    </flux:subheading>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fieldConfig as $field => $cfg)
                        <div class="@if($cfg['type'] === 'textarea') col-span-2 @endif">

                            @switch($cfg['type'])
                                @case('select')
                                    <flux:select
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            :disabled="$field !== 'status'"
                                    >
                                        <option value="">Select {{ $cfg['label'] }}</option>
                                        @foreach($listsForFields[$cfg['listKey']] as $val => $lab)
                                            <option value="{{ $val }}">{{ $lab }}</option>
                                        @endforeach
                                    </flux:select>
                                    @break

                                @case('date')
                                    <flux:input
                                            type="date"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            :disabled="$field !== 'acted_at'"
                                    />
                                    @break

                                @case('number')
                                    <flux:input
                                            type="number"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            step="0.01"
                                            :disabled="$field !== 'approval_level'"
                                    />
                                    @break

                                @default
                                    <flux:input
                                            type="{{ $cfg['type'] }}"
                                            label="{{ $cfg['label'] }}"
                                            wire:model.live="formData.{{ $field }}"
                                            disabled
                                    />
                            @endswitch
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Data Table -->
    <flux:table :paginate="$this->list">
        <flux:table.columns class="table-cell-wrap">
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    @if($field !== 'status')
                        <flux:table.column wire:key="column-{{ $field }}">{{ $cfg['label'] }}</flux:table.column>
                    @endif
                @endif
            @endforeach
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows class="table-cell-wrap">
            @foreach($this->list as $item)
                <div wire:key="leave-row-wrapper-{{ $item->id }}" class="contents">
                    <flux:table.row class="table-cell-wrap">
                        @foreach($fieldConfig as $field => $cfg)
                            @if(in_array($field, $visibleFields))
                                @if($field !== 'status')
                                    <flux:table.cell class="table-cell-wrap">
                                        @switch($field)
                                            @case('employee_id')
                                                {{ $item->employee->fname ?? 'N/A' }}
                                                @break
                                            @case('leave_type_id')
                                                @php
                                                    $leaveTitle = $item->leave_type->leave_title ?? '';
                                                    $leaveTypeColor = match($leaveTitle) {
                                                        'Sick Leave' => 'amber',
                                                        'Casual Leave' => 'emerald', 
                                                        'Annual Leave' => 'indigo',
                                                        'Maternity Leave' => 'rose',
                                                        'Paternity Leave' => 'blue',
                                                        'Study Leave' => 'violet',
                                                        'Unpaid Leave' => 'slate',
                                                        'Compensatory Leave' => 'green',
                                                        'Bereavement Leave' => 'red',
                                                        'Earned Leave' => 'cyan',
                                                        'Medical Leave' => 'orange',
                                                        default => 'gray'
                                                    };
                                                @endphp
                                                <flux:badge color="{{ $leaveTypeColor }}" variant="solid">
                                                    {{ $item->leave_type->leave_title ?? 'N/A' }}
                                                </flux:badge>
                                                @break
                                            @case('apply_from')
                                                {{ $item->apply_from ? \Carbon\Carbon::parse($item->apply_from)->format('jS F Y') : 'N/A' }}
                                                @break
                                            @case('apply_to')
                                                {{ $item->apply_to ? \Carbon\Carbon::parse($item->apply_to)->format('jS F Y') : 'N/A' }}
                                                @break
                                            @case('reason')
                                                @if($item->reason)
                                                    <div x-data="{ expanded: false }" class="max-w-xs">
                                                        <p x-show="!expanded" class="text-sm text-gray-700 line-clamp-2">
                                                            {{ Str::limit($item->reason, 80) }}
                                                        </p>
                                                        <p x-show="expanded" x-cloak class="text-sm text-gray-700">
                                                            {{ $item->reason }}
                                                        </p>
                                                        @if(strlen($item->reason) > 80)
                                                            <button 
                                                                @click="expanded = !expanded" 
                                                                class="text-xs text-blue-600 hover:text-blue-800 mt-1"
                                                                x-text="expanded ? 'Show less' : 'Read more'"
                                                            ></button>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                                @break
                                            @default
                                                @if(in_array($field, ['apply_from', 'apply_to']) && $item->$field)
                                                    {{ \Carbon\Carbon::parse($item->$field)->format('jS F Y') }}
                                                @else
                                                    {{ $item->$field }}
                                                @endif
                                                
                                        @endswitch
                                    </flux:table.cell>
                                @endif
                            @endif
                        @endforeach
                        <flux:table.cell class="table-cell-wrap">
                            @php
                                $statusColor = match($item->status) {
                                    'applied' => 'blue',
                                    'reviewed' => 'cyan',
                                    'approved' => 'green',
                                    'approved_further' => 'emerald',
                                    'partially_approved' => 'lime',
                                    'rejected' => 'red',
                                    'cancelled_employee', 'cancelled_hr' => 'zinc',
                                    'modified' => 'orange',
                                    'escalated' => 'yellow',
                                    'delegated' => 'purple',
                                    'hold' => 'amber',
                                    'expired' => 'rose',
                                    'withdrawn' => 'gray',
                                    'auto_approved' => 'teal',
                                    default => 'zinc'
                                };
                            @endphp
                            <flux:badge color="{{ $statusColor }}" variant="solid">
                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex mx-1">


                                @if($this->canApproveLeave($item))
                                    @if(!in_array($item->status, ['approved', 'rejected', 'cancelled_employee', 'cancelled_hr']))
                                    <flux:button
                                    wire:click="edit({{ $item->id }})"
                                    color="blue"
                                    variant="primary"
                                    size="sm"

                                >
                                    Action
                                </flux:button>
                                        <div class="p-1"></div>

                                    @endif
                                @else
                                    <flux:badge color="blue" variant="solid" class="ml-2">
                                        View Only
                                    </flux:badge>
                                @endif
                                    <flux:button
                                            wire:click="showLeaveRequestEvents({{ $item->id }})"
                                            color="blue"
                                            size="sm"
                                            tooltip="View Leave Request Events"
                                    >
                                        Logs
                                    </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                </div>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Leave Action Modal -->
    <flux:modal name="mdl-leave-action" wire:model.live="showActionModal" @cancel="resetForm">
        <div class="space-y-6" wire:key="leave-action-modal">
            <div>
                <flux:heading size="lg">Leave Request Action</flux:heading>
                <flux:subheading>
                    Add remarks and choose an action for this leave request.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:textarea
                    label="Remarks"
                    wire:model="formData.remarks"
                    placeholder="Enter your remarks here..."
                    rows="4"
                />

                <div class="flex justify-end space-x-4">
                    <flux:button
                        type="button"
                        variant="ghost"
                        wire:click="closeModal"
                    >
                        Cancel
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="danger"
                        wire:click="handleAction('reject', {{ $id }})"
                    >
                        {{ ($formData['approval_level'] ?? 1) > 2 ? 'Reject' : 'Reject' }}
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="primary"
                        wire:click="handleAction('approve', {{ $id }})"
                    >
                        {{ ($formData['approval_level'] ?? 1) > 2 ? 'Accept' : 'Approve' }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    <!-- Leave Request Events Modal - Timeline View -->
    <flux:modal name="leave-request-events-modal" wire:model="showEventsModal" @cancel="closeEventsModal" class="max-w-2xl">
        @if($id)
            @php
                $leaveRequest = \App\Models\Hrms\EmpLeaveRequest::with(['employee', 'leave_type'])->find($id);
                $events = \App\Models\Hrms\LeaveRequestEvent::with('user')
                    ->where('emp_leave_request_id', $id)
                    ->orderBy('created_at', 'asc')
                    ->get();
                $employeeName = ($leaveRequest->employee->fname ?? '') . ' ' . ($leaveRequest->employee->lname ?? '');
                
                // Get all approval rules for this leave type to show all approvers
                $allApprovalRules = \App\Models\Hrms\LeaveApprovalRule::with(['user', 'employees'])
                    ->where('firm_id', session('firm_id'))
                    ->where('is_inactive', false)
                    ->where(function($q) use ($leaveRequest) {
                        $q->whereNull('leave_type_id')
                          ->orWhere('leave_type_id', $leaveRequest->leave_type_id);
                    })
                    ->whereHas('employees', function($q) use ($leaveRequest) {
                        $q->where('employee_id', $leaveRequest->employee_id);
                    })
                    ->orderBy('approval_level')
                    ->get();
                
                // Get approvals that have been made
                $approvals = \App\Models\Hrms\EmpLeaveRequestApproval::where('emp_leave_request_id', $id)->get();
                $approvedByUserIds = $approvals->pluck('approver_id')->toArray();
                
                // Build timeline items: events + pending approvers
                $timelineItems = collect();
                
                // Add application event first (from events or create one)
                $applicationEvent = $events->first(function($e) {
                    return $e->to_status === 'applied' || $e->from_status === null;
                });
                if ($applicationEvent) {
                    $createdAtTs = $applicationEvent->created_at 
                        ? \Carbon\Carbon::parse($applicationEvent->created_at)->timestamp 
                        : 0;
                    $timelineItems->push([
                        'type' => 'event',
                        'data' => $applicationEvent,
                        'level' => 0,
                        'sort_key' => '0_' . str_pad($createdAtTs, 12, '0', STR_PAD_LEFT)
                    ]);
                }
                
                // Group rules by level and add to timeline
                $rulesByLevel = $allApprovalRules->groupBy('approval_level');
                foreach ($rulesByLevel as $level => $rules) {
                    foreach ($rules as $rule) {
                        if (!$rule->user) continue;
                        
                        // Check if this approver has taken action
                        $approval = $approvals->firstWhere('approver_id', $rule->approver_id);
                        $relatedEvent = $events->first(function($e) use ($rule) {
                            return $e->user_id === $rule->approver_id && $e->to_status !== 'applied';
                        });
                        
                        if ($relatedEvent) {
                            // Approver has acted - show their event
                            $eventTs = $relatedEvent->created_at 
                                ? \Carbon\Carbon::parse($relatedEvent->created_at)->timestamp 
                                : 0;
                            $timelineItems->push([
                                'type' => 'event',
                                'data' => $relatedEvent,
                                'level' => $level,
                                'sort_key' => $level . '_' . str_pad($eventTs, 12, '0', STR_PAD_LEFT)
                            ]);
                        } else {
                            // Approver hasn't acted yet - show as pending
                            $timelineItems->push([
                                'type' => 'pending',
                                'data' => $rule,
                                'level' => $level,
                                'sort_key' => $level . '_999999999999'
                            ]);
                        }
                    }
                }
                
                // Sort by level then by timestamp
                $timelineItems = $timelineItems->sortBy('sort_key')->values();
            @endphp
            
            <div class="space-y-4">
                <!-- Header -->
                <div class="border-b pb-4">
                    <flux:heading size="lg">Approval History</flux:heading>
                    @if($leaveRequest)
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div><span class="text-gray-500">Employee:</span> <span class="font-medium">{{ $employeeName }}</span></div>
                                <div><span class="text-gray-500">Leave Type:</span> <flux:badge color="indigo" size="sm">{{ $leaveRequest->leave_type->leave_title ?? 'N/A' }}</flux:badge></div>
                                <div><span class="text-gray-500">Duration:</span> <span class="font-medium">{{ $leaveRequest->apply_days }} day(s)</span></div>
                                <div><span class="text-gray-500">Period:</span> <span class="font-medium">{{ \Carbon\Carbon::parse($leaveRequest->apply_from)->format('d M') }} - {{ \Carbon\Carbon::parse($leaveRequest->apply_to)->format('d M Y') }}</span></div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Timeline -->
                <div class="space-y-0">
                    @forelse($timelineItems as $index => $item)
                        @if($item['type'] === 'event')
                            @php
                                $event = $item['data'];
                                $isApproved = str_contains($event->to_status ?? '', 'approved');
                                $isRejected = $event->to_status === 'rejected';
                                $isApplication = $event->to_status === 'applied' || $event->from_status === null;
                                $userName = $event->user->name ?? 'System';
                                
                                if ($isApplication) {
                                    $actionText = "<strong>{$employeeName}</strong> applied for leave";
                                    $iconBg = 'bg-blue-100 border-blue-400';
                                    $iconColor = 'text-blue-600';
                                    $badgeColor = 'blue';
                                    $badgeText = 'Applied';
                                } elseif ($isApproved) {
                                    $actionText = "<strong>{$userName}</strong> approved this request";
                                    $iconBg = 'bg-green-100 border-green-400';
                                    $iconColor = 'text-green-600';
                                    $badgeColor = 'green';
                                    $badgeText = 'Approved';
                                } elseif ($isRejected) {
                                    $actionText = "<strong>{$userName}</strong> rejected this request";
                                    $iconBg = 'bg-red-100 border-red-400';
                                    $iconColor = 'text-red-600';
                                    $badgeColor = 'red';
                                    $badgeText = 'Rejected';
                                } else {
                                    $actionText = "<strong>{$userName}</strong> updated the status";
                                    $iconBg = 'bg-gray-100 border-gray-400';
                                    $iconColor = 'text-gray-600';
                                    $badgeColor = 'zinc';
                                    $badgeText = ucfirst(str_replace('_', ' ', $event->to_status ?? 'Updated'));
                                }
                            @endphp
                            
                            <div class="relative flex gap-4 pb-6">
                                @if(!$loop->last)
                                    <div class="absolute left-3 top-6 bottom-0 w-0.5 bg-gray-200"></div>
                                @endif
                                
                                <div class="relative z-10 flex-shrink-0 w-6 h-6 rounded-full {{ $iconBg }} border-2 flex items-center justify-center">
                                    @if($isApplication)
                                        <flux:icon.paper-airplane class="size-3 {{ $iconColor }}" />
                                    @elseif($isApproved)
                                        <flux:icon.check class="size-3 {{ $iconColor }}" />
                                    @elseif($isRejected)
                                        <flux:icon.x-mark class="size-3 {{ $iconColor }}" />
                                    @else
                                        <flux:icon.arrow-path class="size-3 {{ $iconColor }}" />
                                    @endif
                                </div>
                                
                                <div class="flex-1 bg-white border rounded-lg p-3 shadow-sm">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm text-gray-700">{!! $actionText !!}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                @if($item['level'] > 0)
                                                    <flux:badge color="zinc" size="sm" variant="outline">Level {{ $item['level'] }}</flux:badge>
                                                @endif
                                                <flux:badge color="{{ $badgeColor }}" size="sm">{{ $badgeText }}</flux:badge>
                                                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($event->created_at)->format('d M Y, h:i A') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($event->remarks)
                                        <div x-data="{ expanded: false }" class="mt-3 p-2 bg-gray-50 rounded border-l-2 border-gray-300">
                                            <p class="text-xs text-gray-500 mb-1">{{ $isApplication ? 'Reason:' : 'Remarks:' }}</p>
                                            <p x-show="!expanded" class="text-sm text-gray-700 line-clamp-2">{{ Str::limit($event->remarks, 100) }}</p>
                                            <p x-show="expanded" x-cloak class="text-sm text-gray-700">{{ $event->remarks }}</p>
                                            @if(strlen($event->remarks) > 100)
                                                <button @click="expanded = !expanded" class="text-xs text-blue-600 hover:text-blue-800 mt-1" x-text="expanded ? '↑ Less' : '↓ More'"></button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- Pending approver --}}
                            @php
                                $rule = $item['data'];
                                $approverName = $rule->user->name ?? 'Unknown';
                            @endphp
                            
                            <div class="relative flex gap-4 pb-6">
                                @if(!$loop->last)
                                    <div class="absolute left-3 top-6 bottom-0 w-0.5 bg-gray-200"></div>
                                @endif
                                
                                <div class="relative z-10 flex-shrink-0 w-6 h-6 rounded-full bg-amber-50 border-2 border-amber-300 border-dashed flex items-center justify-center">
                                    <flux:icon.clock class="size-3 text-amber-500" />
                                </div>
                                
                                <div class="flex-1 bg-amber-50/50 border border-dashed border-amber-200 rounded-lg p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm text-gray-600">
                                                <strong class="text-amber-700">{{ $approverName }}</strong> 
                                                <span class="text-gray-500">— awaiting action</span>
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <flux:badge color="zinc" size="sm" variant="outline">Level {{ $item['level'] }}</flux:badge>
                                                <flux:badge color="amber" size="sm" variant="outline">Pending</flux:badge>
                                                @if($rule->approval_mode)
                                                    <span class="text-xs text-gray-400">{{ ucfirst($rule->approval_mode) }} approval</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <flux:icon.document-text class="size-12 mx-auto text-gray-300 mb-2" />
                            <p>No activity recorded yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </flux:modal>
</div>
