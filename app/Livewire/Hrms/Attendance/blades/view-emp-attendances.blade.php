<div class="space-y-6" xmlns:flux="http://www.w3.org/1999/html">
    <!-- Heading Start -->
    <div class="flex justify-between">
        <div class="flex items-center gap-4">
            <h2 class="text-xl font-semibold">Employee Attendance</h2>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-pink-400/20 border flex items-center py-1.5 rounded-2xl space-x-2">
                <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="red" class="size-6">
                        <path fill-rule="evenodd"
                              d="M10.72 11.47a.75.75 0 0 0 0 1.06l7.5 7.5a.75.75 0 1 0 1.06-1.06L12.31 12l6.97-6.97a.75.75 0 0 0-1.06-1.06l-7.5 7.5Z"
                              clip-rule="evenodd"/>
                        <path fill-rule="evenodd"
                              d="M4.72 11.47a.75.75 0 0 0 0 1.06l7.5 7.5a.75.75 0 1 0 1.06-1.06L6.31 12l6.97-6.97a.75.75 0 0 0-1.06-1.06l-7.5 7.5Z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>

                <div class="flex items-center space-x-2">
                    <flux:select
                            wire:model.live="month"
                            wire:change="setMonth($event.target.value)"
                            class="w-32"
                    >
                        @foreach($availableMonths as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select
                            wire:model.live="year"
                            wire:change="setYear($event.target.value)"
                            class="w-24"
                    >
                        @foreach($this->getAvailableYears() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="red" class="size-6">
                        <path fill-rule="evenodd"
                              d="M13.28 11.47a.75.75 0 0 1 0 1.06l-7.5 7.5a.75.75 0 0 1-1.06-1.06L11.69 12 4.72 5.03a.75.75 0 0 1 1.06-1.06l7.5 7.5Z"
                              clip-rule="evenodd"/>
                        <path fill-rule="evenodd"
                              d="M19.28 11.47a.75.75 0 0 1 0 1.06l-7.5 7.5a.75.75 0 1 1-1.06-1.06L17.69 12l-6.97-6.97a.75.75 0 0 1 1.06-1.06l7.5 7.5Z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <flux:modal.trigger name="mdl-mark-attendance" class="flex justify-end">
                <flux:button variant="primary" icon="plus" class="bg-blue-500 mt-auto text-white px-4 py-2 rounded-md">
                    Mark Attendance
                </flux:button>
            </flux:modal.trigger>
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
    <flux:card
            class="bg-white border border-zinc-200 dark:bg-white/10 dark:border-white/10 mb-1 p-6 px-2 py-1 rounded-xl">
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
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10V2z"/>
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
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                    <path d="M12 6v2m0 8v2M6 12h2m8 0h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Not Marked |
            </span>
            <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z"/>
            </svg>
                Persent on Work Off (POW)
            </span>
        </div>
    </flux:card>

    <!-- Attendance Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-xs">Employee</th>
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    <th class="p-1 text-center text-xs">
                        {{ $day }}<br>
                        {{ Carbon\Carbon::create($year, $month, $day)->format('D') }}
                    </th>
                @endfor
                <th class="px-4 py-3 text-center">Present</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($attendanceData as $employee)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-1">
                        <flux:text class="font-medium flex text-xs">{{ $employee['name'] }}</flux:text>
                        <flux:tooltip toggleable>
                            <flux:button icon="information-circle" size="xs"
                                         variant="ghost"/>
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
                    @foreach ($employee['attendance'] as $day)
                        <td class="px-2 py-3 text-center">
                            <span class="inline-flex items-center justify-center cursor-pointer hover:scale-110 transition-transform"
                                  wire:click="showAttendanceDetails({{ $day['employee_id'] }}, '{{ $day['date'] }}')">
                                @if(isset($day['status']) && $day['status'])
                                    @switch($day['status'])
                                        @case('P')
                                        <flux:tooltip content="Present">
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('A')
                                        <flux:tooltip content="Absent">
                                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                        </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('HD')
                                        <flux:tooltip content="Half Day">
                                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 536 512">
                                            <path d="M508.55 171.51L362.18 150.2 296.77 17.81C290.89 5.98 279.42 0 267.95 0c-11.4 0-22.79 5.9-28.69 17.81l-65.43 132.38-146.38 21.29c-26.25 3.8-36.77 36.09-17.74 54.59l105.89 103-25.06 145.48C86.98 495.33 103.57 512 122.15 512c4.93 0 10-1.17 14.87-3.75l130.95-68.68 130.94 68.7c4.86 2.55 9.92 3.71 14.83 3.71 18.6 0 35.22-16.61 31.66-37.4l-25.03-145.49 105.91-102.98c19.04-18.5 8.52-50.8-17.73-54.6zm-121.74 123.2l-18.12 17.62 4.28 24.88 19.52 113.45-102.13-53.59-22.38-11.74.03-317.19 51.03 103.29 11.18 22.63 25.01 3.64 114.23 16.63-82.65 80.38z"/>
                                        </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('PW')
                                        <flux:tooltip content="Partial Working">
                                            <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10V2z"/>
                                                <path d="M12 7v5l2.5 2.5.75-1.25-1.75-1.75V7H12z" fill="white"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('L')
                                        <flux:tooltip content="Leave">
                                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 640 512">
                                                <path d="M624 448H16c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h608c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16zM80.55 341.27c6.28 6.84 15.1 10.72 24.33 10.71l130.54-.18a65.62 65.62 0 0 0 29.64-7.12l290.96-147.65c26.74-13.57 50.71-32.94 67.02-58.31 18.31-28.48 20.3-49.09 13.07-63.65-7.21-14.57-24.74-25.27-58.25-27.45-29.85-1.94-59.54 5.92-86.28 19.48l-98.51 49.99-218.7-82.06a17.799 17.799 0 0 0-18-1.11L90.62 67.29c-10.67 5.41-13.25 19.65-5.17 28.53l156.22 98.1-103.21 52.38-72.35-36.47a17.804 17.804 0 0 0-16.07.02L9.91 230.22c-10.44 5.3-13.19 19.12-5.57 28.08l76.21 82.97z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('WFR')
                                        <flux:tooltip content="Work from Remote">
                                            <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('CW')
                                        <flux:tooltip content="Compensatory Work">
                                            <svg class="w-4 h-4 text-pink-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('OD')
                                        <flux:tooltip content="On Duty">
                                            <svg class="w-4 h-4 text-teal-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('H')
                                        <flux:tooltip content="Holiday">
                                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M20 8.69V4h-4.69L12 .69 8.69 4H4v4.69L.69 12 4 15.31V20h4.69L12 23.31 15.31 20H20v-4.69L23.31 12 20 8.69zM12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6zm0-10c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('W')
                                        <flux:tooltip content="Week Off">
                                            <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('S')
                                        <flux:tooltip content="Suspended">
                                            <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @case('POW')
                                        <flux:tooltip content="Persent on Work Off">
                                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z"/>
                                            </svg>
                                        </flux:tooltip>
                                        @break
                                        @default
                                        <flux:tooltip content="Not Marked">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                                <path d="M12 6v2m0 8v2M6 12h2m8 0h2" stroke="currentColor" stroke-width="2"
                                                      stroke-linecap="round"/>
                                            </svg>
                                        </flux:tooltip>
                                    @endswitch
                                @else
                                    <flux:tooltip content="Not Marked">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                        <path d="M12 6v2m0 8v2M6 12h2m8 0h2" stroke="currentColor" stroke-width="2"
                                              stroke-linecap="round"/>
                                    </svg>
                                    </flux:tooltip>
                                @endif
                            </span>
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-center font-medium">
                        {{ $employee['present_count'] }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $employees->links() }}
    </div>

    <!-- Batch List Section -->


    <!-- Mark Attendance Modal -->
    <flux:modal name="mdl-mark-attendance" @cancel="resetBulkForm" position="right" class="max-w-6xl" variant="flyout">
        <form wire:submit.prevent="saveBulkAttendance">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Mark Bulk Attendance</flux:heading>
                    <flux:text class="text-gray-500">Mark attendance for multiple employees</flux:text>
                </div>

                <flux:separator/>

                <!-- Status and Date Selection -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:select
                                label="Attendance Status"
                                wire:model.live="selectedStatus"
                                required
                        >
                            <option value="">Select Status</option>
                            @foreach($listsForFields['attendance_statuses'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:date-picker
                                label="Attendance Period"
                                with-today
                                mode="range"
                                with-presets
                                wire:model.live="dateRange"
                                start-key="start"
                                end-key="end"
                                required
                        />
                    </div>
                </div>

                <!-- Employee Selection Section -->
                <div class="mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-sm font-medium text-gray-700">Select Employees</label>
                        <div class="flex space-x-2">
                            <flux:button size="xs" variant="outline" wire:click="selectAllEmployeesGlobal">Select All
                            </flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deselectAllEmployeesGlobal">Deselect
                                All
                            </flux:button>
                        </div>
                    </div>

                    <!-- Employee Search -->
                    <div class="mb-4">
                        <flux:input
                                type="search"
                                placeholder="Search employees by name, email or phone..."
                                wire:model.live="employeeSearch"
                                class="w-full"
                        >
                            <x-slot:prefix>
                                <flux:icon name="magnifying-glass" class="w-4 h-4 text-gray-400"/>
                            </x-slot:prefix>
                            @if($employeeSearch)
                                <x-slot:suffix>
                                    <flux:button
                                            wire:click="$set('employeeSearch', '')"
                                            variant="ghost"
                                            size="xs"
                                            icon="x-mark"
                                            class="text-gray-400 hover:text-gray-600"
                                    />
                                </x-slot:suffix>
                            @endif
                        </flux:input>
                    </div>

                    <div class="space-y-4 overflow-y-auto max-h-[60vh] pr-2">
                        <flux:accordion class="w-full">
                            @forelse($departments as $department)
                                <flux:accordion.item>
                                    <flux:accordion.heading>
                                        <div class="flex justify-between items-center w-full">
                                            <span>{{ $department['title'] }}</span>
                                            <span class="text-sm text-gray-500">({{ count($department['employees']) }} employees)</span>
                                        </div>
                                    </flux:accordion.heading>
                                    <flux:accordion.content class="pl-4">
                                        <div class="flex justify-end space-x-2 mb-2">
                                            <flux:button size="xs" variant="outline"
                                                         wire:click="selectAllEmployees('{{ $department['id'] }}')">
                                                Select All
                                            </flux:button>
                                            <flux:button size="xs" variant="ghost"
                                                         wire:click="deselectAllEmployees('{{ $department['id'] }}')">
                                                Deselect
                                            </flux:button>
                                        </div>

                                        <flux:checkbox.group class="space-y-1">
                                            @foreach($department['employees'] as $employee)
                                                <div class="flex items-center justify-between space-x-2 mb-2">
                                                    <flux:checkbox
                                                            wire:model="selectedEmployees"
                                                            class="w-full truncate"
                                                            label="{{ $employee['fname'] }} {{ $employee['lname'] }}"
                                                            value="{{ $employee['id'] }}"
                                                            id="employee-{{ $employee['id'] }}"
                                                    />
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="xs"
                                                                     variant="ghost"/>
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                            <p><strong>Email:</strong> {{ $employee['email'] }}</p>
                                                            <p><strong>Phone:</strong> {{ $employee['phone'] }}</p>
                                                            <p><strong>ID:</strong> {{ $employee['id'] }}</p>
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                </div>
                                            @endforeach
                                        </flux:checkbox.group>
                                    </flux:accordion.content>
                                </flux:accordion.item>
                            @empty
                                <div class="text-center py-4 text-gray-500">
                                    @if($employeeSearch)
                                        No employees found matching "{{ $employeeSearch }}"
                                    @else
                                        No departments or employees available
                                    @endif
                                </div>
                            @endforelse
                        </flux:accordion>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-2 pt-4">
                    <flux:button x-on:click="$flux.modal('mdl-mark-attendance').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Mark Attendance
                    </flux:button>
                </div>
            </div>
        </form>
        <div class="mt-8">
            <flux:card size="sm" class="bg-white dark:bg-gray-800 w-[448px]">
                <div class="mb-4">
                    <flux:heading size="lg">Attendance Batches</flux:heading>
                    <flux:text class="text-gray-500">View and manage attendance marking batches</flux:text>
                </div>
                <div class="overflow-x-auto">
                    <flux:table class="w-full">
                        <flux:table.columns>
                            <flux:table.column>Batch ID</flux:table.column>
                            <flux:table.column>Title</flux:table.column>
                            <flux:table.column>Created At</flux:table.column>
                            <flux:table.column>Actions</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($batches ?? [] as $batch)
                                <flux:table.row :key="$batch->id">
                                    <flux:table.cell class="table-cell-wrap">{{ $batch->id }}</flux:table.cell>
                                    <flux:table.cell class="table-cell-wrap">{{ $batch->title }}</flux:table.cell>
                                    <flux:table.cell
                                            class="table-cell-wrap">{{ $batch->created_at->format('d M Y H:i:s') }}</flux:table.cell>
                                    <flux:table.cell class="table-cell-wrap">
                                        <div class="flex gap-2">
                                            <flux:modal.trigger name="batch-details">
                                                <flux:button size="sm" variant="primary" icon="eye"
                                                             wire:click="selectBatch({{ $batch->id }})">
                                                    View
                                                </flux:button>
                                            </flux:modal.trigger>
                                            <flux:modal.trigger name="rollback-batch-{{ $batch->id }}">
                                                <flux:button size="sm" variant="danger" icon="trash">Rollback
                                                </flux:button>
                                            </flux:modal.trigger>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>
        </div>
    </flux:modal>

    <!-- Batch List -->
    <flux:modal name="batch-details" title="Batch Information" class="max-w-3xl">
        @if(isset($selectedBatch) && $selectedBatch)
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <h3>Batch Information</h3>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    Batch ID: {{ $selectedBatch->id }} | Created: {{ $selectedBatch->created_at->format('d M Y H:i') }}
                </div>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Operation</flux:table.column>
                        <flux:table.column>Model Type</flux:table.column>
                        <flux:table.column>Model ID</flux:table.column>
                        <flux:table.column>Created At</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($selectedBatch->items as $item)
                            <flux:table.row :key="$item->id">
                                <flux:table.cell>{{ ucfirst($item->operation) }}</flux:table.cell>
                                <flux:table.cell>{{ class_basename($item->model_type) }}</flux:table.cell>
                                <flux:table.cell>{{ $item->model_id }}</flux:table.cell>
                                <flux:table.cell>{{ $item->created_at->format('d M Y H:i:s') }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <div class="p-4 text-center text-gray-500">
                No batch details available
            </div>
        @endif
    </flux:modal>

    <!-- Rollback Confirmation Modal -->
    @if(isset($batches))
        @foreach($batches as $batch)
            <flux:modal name="rollback-batch-{{ $batch->id }}" class="min-w-[22rem]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Rollback Batch?</flux:heading>
                        <flux:text class="mt-2">
                            <p>You're about to rollback Batch ID: {{ $batch->id }}</p>
                            <p class="text-red-500">This action cannot be reversed.</p>
                        </flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer/>
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button variant="danger" icon="trash" wire:click="rollbackBatchById({{ $batch->id }})">
                            Rollback
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
    @endforeach
@endif

<!-- Combined Attendance Modal -->
    <flux:modal name="attendance-modal" variant="flyout">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">View Attendance Detail</flux:heading>
                <flux:text class="text-gray-500">View Attendance Detail
                    ({{ Carbon\Carbon::parse($selectedDate)->format('d M, Y') }})
                </flux:text>
            </div>

            <flux:separator/>
        @if($selectedAttendance)
            <!-- Employee Info Section -->
                <div class="bg-teal-400/20 rounded-lg p-1 mb-1">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <flux:icon name="user-circle" class="w-12 h-12 text-gray-400"/>
                        </div>
                        <div class="">
                            <div class="text-xl font-semibold">
                                {{ $selectedAttendance->employee->fname }} {{ $selectedAttendance->employee->lname }}
                            </div>
                            <div class="text-gray-600">
                                Employee ID: {{ $selectedAttendance->employee->id }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-1 mb-1">
                    <flux:badge size="lg" color="lime">
                        {{ Carbon\Carbon::parse($selectedDate)->format('d M, Y') }}
                    </flux:badge>
                    <flux:badge color="yellow"
                                icon="user-circle">{{ $listsForFields['attendance_statuses'][$formData['attendance_status_main']] ?? 'Not Marked' }}</flux:badge>
                </div>
                <!-- Form Section with Accordion -->

                <flux:accordion class="w-full">
                    <flux:accordion.item>
                        <flux:accordion.heading
                                class="[&>svg]:ml-6 bg-violet-400/20 cursor-pointer dark:text-white flex font-medium group/accordion-heading items-center justify-between p-2 pb-4 pb-[.3125rem] pt-4 pt-[.3125rem] rounded-[.3rem] text-left text-sm text-zinc-800 w-full">
                            <div class="flex justify-between items-center w-full">
                                <span class="text-lg font-semibold">{{ $isNewAttendance ? 'Mark Attendance' : 'Update Attendance' }}</span>
                            </div>
                        </flux:accordion.heading>
                        <flux:accordion.content>
                            <div class="space-y-4 p-4">
                                <flux:select
                                        label="Attendance Status"
                                        wire:model="formData.attendance_status_main"
                                        required
                                >
                                    <option value="">Select Status</option>
                                    @foreach($listsForFields['attendance_statuses'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </flux:select>

                                <flux:textarea
                                        label="Remarks"
                                        wire:model="formData.attend_remarks"
                                        rows="2"
                                />

                                <div class="flex justify-end space-x-2 pt-4">
                                    <flux:button wire:click="closeAttendanceModal" variant="ghost">
                                        Cancel
                                    </flux:button>
                                    <flux:button wire:click="saveAttendance" variant="primary">
                                        {{ $isNewAttendance ? 'Mark Attendance' : 'Update Attendance' }}
                                    </flux:button>
                                </div>
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                </flux:accordion>

                <!-- Punches Section -->
                <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b flex justify-between items-center">
                        <h3 class="text-lg font-semibold">Punch Records</h3>
                        <flux:button
                                wire:click="updatePunches({{ $selectedAttendance->employee->id }}, '{{ $selectedDate }}')"
                                variant="primary"
                                icon="plus"
                        >
                            Add Punch
                        </flux:button>
                    </div>
                    <div class="p-4">
                        @if(count($punches) > 0)
                            <div class="relative space-y-4">
                                <!-- Timeline Line -->
                                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                                @foreach($punches as $punch)
                                    <div class="relative flex items-start space-x-4 pl-5">
                                        <!-- Timeline Dot -->
                                        <div class="flex justify-between">
                                            <div class="absolute left-5 top-4 -ml-[0.5rem] h-4 w-4 rounded-full border-2 border-white bg-{{ $punch['type'] === 'in' ? 'green' : 'red' }}-500"></div>
                                        </div>
                                        <!-- Punch Card -->
                                        <div class="flex-1">
                                            <flux:card class="hover:shadow-md transition-shadow">
                                                <!-- Punch Header -->
                                                <div class="p-4 {{ $punch['type'] === 'in' ? 'bg-green-50' : 'bg-red-50' }} rounded-t-lg">
                                                    <div class="flex justify-between items-center">
                                                        <div class="flex items-center space-x-3">
                                                            <flux:badge
                                                                    color="{{ $punch['type'] === 'in' ? 'green' : 'red' }}"
                                                                    size="lg">
                                                                {{ strtoupper($punch['type']) }}
                                                            </flux:badge>
                                                            <div>
                                                                <div class="text-lg font-semibold">
                                                                    {{ \Carbon\Carbon::parse($punch['datetime'])->format('h:i A') }}
                                                                </div>
                                                                <div class="text-sm text-gray-600">
                                                                    {{ \Carbon\Carbon::parse($punch['datetime'])->format('d M, Y') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            @if($punch['is_final'])
                                                                <flux:badge color="green" size="sm">Final</flux:badge>
                                                            @endif
                                                            <flux:button wire:click="editPunch({{ $punch['id'] }})"
                                                                         class="ms-5" variant="filled" size="sm"
                                                                         icon="pencil">
                                                                Edit
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Punch Details -->
                                                <div class="p-4 space-y-4">
                                                    <!-- Location Details -->
                                                    @if(isset($punch['geo_location']) && !empty($punch['geo_location']))
                                                        <div class="space-y-3">
                                                            <div class="flex items-center justify-between">
                                                                <div class="space-y-1">
                                                                    <div class="flex items-center space-x-2">
                                                                        <flux:icon name="map-pin"
                                                                                   class="w-4 h-4 text-gray-400"/>
                                                                        <div class="text-sm font-medium text-gray-700">
                                                                            Location Details<br>
                                                                            {{--                                                                            <div class="text-sm font-medium text-gray-700">--}}
                                                                            {{--                                                                                {{ $punch['location'] }}--}}
                                                                            {{--                                                                            </div>--}}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @if(isset($punch['geo_location']['latitude']) && isset($punch['geo_location']['longitude']))
                                                                <div class="relative w-full h-48 rounded-lg overflow-hidden border border-gray-200">
                                                                    <iframe
                                                                            width="100%"
                                                                            height="100%"
                                                                            frameborder="0"
                                                                            style="border:0"
                                                                            src="https://maps.google.com/maps?q={{ $punch['geo_location']['latitude'] }},{{ $punch['geo_location']['longitude'] }}&z=15&output=embed"
                                                                            allowfullscreen
                                                                    ></iframe>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </flux:card>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <flux:icon name="clock" class="w-12 h-12 mx-auto text-gray-400 mb-4"/>
                                <div class="text-gray-500">No punch records found for this attendance.</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>

    <!-- Punch Modal -->
    <flux:modal name="punch-modal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditingPunch ? 'Edit Punch' : 'Add Punch' }}</flux:heading>
                <flux:text
                        class="text-gray-500">{{ $isEditingPunch ? 'Update punch details' : 'Add new punch record' }}</flux:text>
            </div>

            <flux:separator/>

            <form wire:submit.prevent="savePunch">
                <div class="space-y-4">
                    <!-- Punch Type -->
                    <div>
                        <flux:select
                                label="Punch Type"
                                wire:model="punchForm.in_out"
                                required
                        >
                            <option value="">Select Type</option>
                            <option value="in">IN</option>
                            <option value="out">OUT</option>
                        </flux:select>
                    </div>

                    <!-- Punch Time -->
                    <div>
                        <flux:input
                                type="time"
                                label="Punch Time"
                                wire:model="punchForm.punch_time"
                                required
                        />
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-2 pt-4">
                        <flux:button wire:click="closePunchModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ $isEditingPunch ? 'Update Punch' : 'Add Punch' }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
