<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        <div class="flex items-center gap-4">
            <h2 class="text-xl font-semibold">Employee Attendance</h2>
        </div>
        <div class="flex items-center gap-4">
            <flux:date-picker
                mode="range"
                presets="today yesterday thisWeek last7Days thisMonth yearToDate allTime"
                wire:model.live="dateRange"
                label="Select Date Range"
                class=""
                max-date="{{ now()->format('Y-m-d') }}"
            />
        </div>
    </div>

    <!-- Filter Section -->
    <div class="flex gap-4 mt-4 mb-4">
        <div class="flex-1">
            <flux:input
                    type="search"
                    placeholder="Search employee by name..."
                    wire:model.live="employeeNameFilter"
                    class="w-full"
            >
                <x-slot:prefix>
                    <flux:icon name="magnifying-glass" class="w-4 h-4 text-gray-400"/>
                </x-slot:prefix>
                @if($employeeNameFilter)
                    <x-slot:suffix>
                        <flux:button
                                wire:click="$set('employeeNameFilter', '')"
                                variant="ghost"
                                size="xs"
                                icon="x-mark"
                                class="text-gray-400 hover:text-gray-600"
                        />
                    </x-slot:suffix>
                @endif
            </flux:input>
        </div>
        <div class="w-64">
            <flux:select
                    wire:model.live="selectedDepartment"
                    placeholder="Filter by Department"
            >
                <option value="">All Departments</option>
                @foreach($this->departments as $department)
                    <option value="{{ $department->id }}">{{ $department->title }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Legend -->
    <flux:card class="bg-white border border-zinc-200 dark:bg-white/10 dark:border-white/10 mb-1 p-6 px-2 py-1 rounded-xl">
        <div class="text-sm text-gray-600 dark:text-gray-300 flex flex-wrap gap-3">
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                Present (P) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                </svg>
                Absent (A) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 536 512">
                    <path d="M508.55 171.51L362.18 150.2 296.77 17.81C290.89 5.98 279.42 0 267.95 0c-11.4 0-22.79 5.9-28.69 17.81l-65.43 132.38-146.38 21.29c-26.25 3.8-36.77 36.09-17.74 54.59l105.89 103-25.06 145.48C86.98 495.33 103.57 512 122.15 512c4.93 0 10-1.17 14.87-3.75l130.95-68.68 130.94 68.7c4.86 2.55 9.92 3.71 14.83 3.71 18.6 0 35.22-16.61 31.66-37.4l-25.03-145.49 105.91-102.98c19.04-18.5 8.52-50.8-17.73-54.6zm-121.74 123.2l-18.12 17.62 4.28 24.88 19.52 113.45-102.13-53.59-22.38-11.74.03-317.19 51.03 103.29 11.18 22.63 25.01 3.64 114.23 16.63-82.65 80.38z"/>
                </svg>
                Half Day (HD) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                    <path d="M12 7v5l2.5 2.5.75-1.25-1.75-1.75V7H12z" fill="white"/>
                </svg>
                Partial Working (PW) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 640 512">
                    <path d="M624 448H16c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h608c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16zM80.55 341.27c6.28 6.84 15.1 10.72 24.33 10.71l130.54-.18a65.62 65.62 0 0 0 29.64-7.12l290.96-147.65c26.74-13.57 50.71-32.94 67.02-58.31 18.31-28.48 20.3-49.09 13.07-63.65-7.21-14.57-24.74-25.27-58.25-27.45-29.85-1.94-59.54 5.92-86.28 19.48l-98.51 49.99-218.7-82.06a17.799 17.799 0 0 0-18-1.11L90.62 67.29c-10.67 5.41-13.25 19.65-5.17 28.53l156.22 98.1-103.21 52.38-72.35-36.47a17.804 17.804 0 0 0-16.07.02L9.91 230.22c-10.44 5.3-13.19 19.12-5.57 28.08l76.21 82.97z"/>
                </svg>
                Leave (L) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
                Work from Remote (WFR) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-pink-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                Compensatory Work (CW) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-teal-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                On Duty (OD) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 8.69V4h-4.69L12 .69 8.69 4H4v4.69L.69 12 4 15.31V20h4.69L12 23.31 15.31 20H20v-4.69L23.31 12 20 8.69zM12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6zm0-10c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"/>
                </svg>
                Holiday (H) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/>
                </svg>
                Week Off (W) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
                Suspended (S) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z"/>
                </svg>
                Present on Work Off (POW) |
            </span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M504 256c0 136.997-111.043 248-248 248S8 392.997 8 256C8 119.083 119.043 8 256 8s248 111.083 248 248zm-248 50c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"/>
                </svg>
                Late Marked (LM)
            </span>
        </div>
    </flux:card>

    <!-- Attendance Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-xs">Employee</th>
                @foreach($activeStatuses as $status)
                    <th class="px-4 py-3 text-center text-xs">
                        {{ $listsForFields['attendance_statuses'][$status] ?? $status }}
                    </th>
                @endforeach
                <th class="px-4 py-3 text-center text-xs">Not Marked</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($attendanceData as $employee)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-1">
                        <flux:text class="font-medium flex text-xs">{{ $employee['name'] }}</flux:text>
                        <flux:tooltip toggleable>
                            <flux:button icon="information-circle" size="xs" variant="ghost"/>
                            <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                <p><strong>Employee Code:</strong> {{ $employee['employee_code'] }}</p>
                                <p><strong>Email:</strong> {{ $employee['email'] }}</p>
                                @if(isset($employee['phone']) && $employee['phone'])
                                    <p><strong>Phone:</strong> {{ $employee['phone'] }}</p>
                                @endif
                                @if(isset($employee['department']) && $employee['department'])
                                    <p><strong>Department:</strong> {{ $employee['department'] }}</p>
                                @endif
                                @if(isset($employee['designation']) && $employee['designation'])
                                    <p><strong>Designation:</strong> {{ $employee['designation'] }}</p>
                                @endif
                            </flux:tooltip.content>
                        </flux:tooltip>
                    </td>

                    @foreach($activeStatuses as $status)
                        <td class="px-4 py-3 text-center">
                            <button
                                class="w-full text-center focus:outline-none hover:underline"
                                wire:click="showStatusDates({{ $employee['id'] }}, '{{ $status }}')"
                                @if(($employee['status_counts'][(string) $status] ?? 0) == 0) disabled style="color: #aaa; cursor: not-allowed;" @endif
                            >
                                {{ $employee['status_counts'][(string) $status] ?? 0 }}
                            </button>
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-center">
                        <button
                                class="w-full text-center focus:outline-none hover:underline"
                                wire:click="showStatusDates({{ $employee['id'] }}, 'no_status')"
                                @if(($employee['no_status_count'] ?? 0) == 0) disabled style="color: #aaa; cursor: not-allowed;" @endif
                        >
                            {{ $employee['no_status_count'] ?? 0 }}
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Status Dates Modal -->
    <flux:modal name="status-dates-modal" class="">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Status Dates</flux:heading>
                <flux:text class="text-gray-500">
                    {{ $selectedEmployeeName ?? 'Employee' }} -
                    <span class="font-semibold">{{ $selectedStatusLabel ?? '' }}</span>
                    ({{ \Carbon\Carbon::parse($dateRange['start'])->format('d M, Y') }} - {{ \Carbon\Carbon::parse($dateRange['end'])->format('d M, Y') }})
                </flux:text>
            </div>
            <flux:separator/>
            @if(isset($statusDates) && count($statusDates) > 0)
                <div class="max-h-[60vh] overflow-y-auto">
                    <ul class="list-disc pl-6 space-y-2">
                        @foreach($statusDates as $date)
                            <li class="text-sm">{{ \Carbon\Carbon::parse($date)->format('d M, Y (l)') }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="text-center py-8">
                    <flux:icon name="calendar-days" class="w-12 h-12 mx-auto text-gray-400 mb-4"/>
                    <div class="text-gray-500">No dates found for this status.</div>
                </div>
            @endif
        </div>
    </flux:modal>
</div> 